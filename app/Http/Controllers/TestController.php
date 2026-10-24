<?php

namespace App\Http\Controllers;

use App\Enums\LifePhase;
use App\Enums\TestSessionStatus;
use App\Models\Question;
use App\Models\TestAnswer;
use App\Models\TestSession;
use App\Models\User;
use App\Services\TopsisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class TestController extends Controller
{
    /** Batas sesi tes yang boleh berjalan bersamaan untuk satu pengguna. */
    private const MAX_IN_PROGRESS = 3;

    public function __construct(protected TopsisService $topsisService) {}

    public function index(Request $request)
    {
        $sessions = $request->user()
            ->testSessions()
            ->with([
                'result.details' => fn ($q) => $q->orderBy('rank')->with('alternative:id,name'),
            ])
            ->latest('started_at')
            ->get()
            ->each(function ($session) {
                if ($session->result) {
                    $session->result->setRelation('topDetail', $session->result->details->first());
                    $session->result->unsetRelation('details');
                }
            });

        return Inertia::render('Tests/Index', [
            'sessions'         => $sessions,
            'lifePhaseOptions' => LifePhase::assignableToUserOptions(),
        ]);
    }

    public function create(Request $request)
    {
        $inProgressSession = $request->user()
            ->testSessions()
            ->where('status', TestSessionStatus::InProgress->value)
            ->latest()
            ->first();

        return Inertia::render('Tests/Create', [
            'inProgressSession' => $inProgressSession,
            'defaultLifePhase'  => $request->user()->life_phase,
            'lifePhaseOptions'  => LifePhase::assignableToUserOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'life_phase' => ['required', Rule::in(LifePhase::assignableToUserValues())],
        ]);

        $session = DB::transaction(function () use ($request, $validated) {

            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();

            $inProgress = $user->testSessions()
                ->where('status', TestSessionStatus::InProgress->value)
                ->count();

            if ($inProgress >= self::MAX_IN_PROGRESS) {
                return null;
            }

            $session = new TestSession();
            $session->user_id    = $user->id;
            $session->life_phase = $validated['life_phase'];
            $session->status     = TestSessionStatus::InProgress->value;
            $session->started_at = now();
            $session->save();

            return $session;
        });

        if ($session === null) {
            return back()->withErrors([
                'life_phase' => 'Selesaikan dulu tes yang sedang berjalan sebelum membuat yang baru.',
            ]);
        }

        return redirect()->route('tests.show', $session->id);
    }

    public function show(TestSession $testSession, Request $request)
    {
        $this->authorizeOwnership($testSession, $request);

        if ($testSession->status === TestSessionStatus::Completed->value) {
            $result = $testSession->result()
                ->with(['details' => fn ($q) => $q->with('alternative')->orderBy('rank')])
                ->first();

            return Inertia::render('Tests/Completed', [
                'testSession' => $testSession,
                'result'      => $result,
            ]);
        }

        $questions = Question::query()
            ->where('questions.is_active', true)
            ->whereHas('subCriteria')
            ->with('subCriteria:id,name,criteria_id')
            ->join('sub_criteria', 'questions.sub_criteria_id', '=', 'sub_criteria.id')
            ->join('criteria', 'sub_criteria.criteria_id', '=', 'criteria.id')
            ->orderBy('criteria.order')
            ->orderBy('sub_criteria.order')
            ->orderBy('questions.order')
            ->select('questions.*')
            ->get();

        $existingAnswers = $testSession->answers()
            ->pluck('answer_value', 'question_id');

        return Inertia::render('Tests/Show', [
            'testSession'     => $testSession,
            'questions'       => $questions,
            'existingAnswers' => $existingAnswers,
        ]);
    }

    public function saveAnswer(Request $request, TestSession $testSession)
    {
        $this->authorizeOwnership($testSession, $request);

        $validated = $request->validate([
            'question_id' => [
                'required',
                Rule::exists('questions', 'id')->where('is_active', true),
            ],
            'answer_value' => ['required', 'integer', 'min:1', 'max:5'],
        ], [
            'question_id.exists' => 'Soal ini sudah tidak aktif dan tidak bisa dijawab lagi.',
        ]);

        $saved = DB::transaction(function () use ($testSession, $validated) {

            $locked = TestSession::whereKey($testSession->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== TestSessionStatus::InProgress->value) {
                return false;
            }

            $answer = $locked->answers()->where('question_id', $validated['question_id'])->first();

            if (! $answer) {
                $answer = new TestAnswer();
                $answer->test_session_id = $locked->id;
                $answer->question_id     = $validated['question_id'];
            }

            $answer->answer_value = $validated['answer_value'];
            $answer->save();

            return true;
        });

        abort_unless($saved, 422, 'Sesi tes ini sudah tidak aktif.');

        return response()->json(['success' => true]);
    }

    public function recalculate(Request $request, TestSession $testSession)
    {
        $this->authorizeOwnership($testSession, $request);
        abort_if($testSession->status !== TestSessionStatus::Completed->value, 422, 'Sesi ini belum selesai dikerjakan.');

        try {
            DB::transaction(function () use ($testSession) {
                // Kunci sesi supaya dua permintaan bersamaan berjalan bergantian
                $locked = TestSession::whereKey($testSession->id)->lockForUpdate()->firstOrFail();

                if ($locked->status !== TestSessionStatus::Completed->value) {
                    return;
                }

                $locked->result?->delete();
                $this->generateResult($locked);
            });

            return redirect()->route('tests.show', $testSession->id)
                ->with('success', 'Hasil berhasil dihitung ulang.');
        } catch (\RuntimeException $e) {
            report($e);

            return back()->withErrors([
                'recalculate' => 'Hasil belum bisa dihitung ulang. Coba lagi nanti atau hubungi admin.',
            ]);
        }
    }

    public function complete(Request $request, TestSession $testSession)
    {
        $this->authorizeOwnership($testSession, $request);

        if ($testSession->status === TestSessionStatus::Completed->value) {
            return redirect()->route('tests.show', $testSession->id);
        }

        abort_if($testSession->status !== TestSessionStatus::InProgress->value, 422, 'Sesi ini tidak bisa diselesaikan.');

        [$missingCount, $resultFailed] = DB::transaction(function () use ($testSession) {
            $locked = TestSession::whereKey($testSession->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === TestSessionStatus::Completed->value) {
                return [0, false];
            }

            $missing = Question::where('is_active', true)->pluck('id')
                ->diff($locked->answers()->pluck('question_id'));

            if ($missing->isNotEmpty()) {
                return [$missing->count(), false];
            }

            $locked->forceFill([
                'status'       => TestSessionStatus::Completed->value,
                'completed_at' => now(),
            ])->save();

            $resultFailed = false;

            try {
                $this->generateResult($locked);
            } catch (\RuntimeException $e) {

                $resultFailed = true;
                Log::warning('TOPSIS gagal dihitung untuk sesi #'.$locked->id.': '.$e->getMessage());
            }

            return [0, $resultFailed];
        });

        if ($missingCount > 0) {
            return back()->withErrors([
                'complete' => "Ada {$missingCount} soal baru atau belum dijawab. Muat ulang halaman untuk melihatnya.",
            ]);
        }

        if ($resultFailed) {
            return redirect()->route('tests.show', $testSession->id)
                ->with('success', 'Tes selesai, tetapi hasil rekomendasi belum bisa dihitung. Coba tombol hitung ulang di halaman ini, atau hubungi admin.');
        }

        return redirect()->route('tests.show', $testSession->id)
            ->with('success', 'Tes selesai! Berikut hasil rekomendasimu.');
    }

    protected function generateResult(TestSession $testSession): void
    {
        $rawResults = $this->topsisService->calculate($testSession);

        usort($rawResults, fn ($a, $b) => $b['score'] <=> $a['score']);

        DB::transaction(function () use ($testSession, $rawResults) {
            $testResult = $testSession->result()->create([]);

            foreach ($rawResults as $index => $row) {
                $testResult->details()->create([
                    'alternative_id' => $row['alternative_id'],
                    'score'          => $row['score'],
                    'rank'           => $index + 1,
                ]);
            }
        });
    }

    /**
     * ID dibandingkan sebagai integer karena tipe kolom bisa terbaca sebagai string. 404 (bukan 403)
     * supaya orang lain tidak bisa memastikan ID sesi tes mana yang ada.
     */
    private function authorizeOwnership(TestSession $testSession, Request $request): void
    {
        abort_if((int) $testSession->user_id !== (int) $request->user()->id, 404);
    }
}