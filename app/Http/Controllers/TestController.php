<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\TestSession;
use App\Services\TopsisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TestController extends Controller
{
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
            'sessions' => $sessions,
        ]);
    }

    public function create(Request $request)
    {
        $inProgressSession = $request->user()
            ->testSessions()
            ->where('status', 'in_progress')
            ->latest()
            ->first();

        return Inertia::render('Tests/Create', [
            'inProgressSession' => $inProgressSession,
            'defaultLifePhase'  => $request->user()->life_phase,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'life_phase' => ['required', 'in:siswa,mahasiswa,pekerja'],
        ]);

        $session = $request->user()->testSessions()->create([
            'life_phase' => $validated['life_phase'],
            'status'     => 'in_progress',
            'started_at' => now(),
        ]);

        return redirect()->route('tests.show', $session->id);
    }

    public function show(TestSession $testSession, Request $request)
    {
        $this->authorizeOwnership($testSession, $request);

        if ($testSession->status === 'completed') {
            $result = $testSession->result()
                ->with(['details' => fn ($q) => $q->with('alternative')->orderBy('rank')])
                ->first();

            return Inertia::render('Tests/Completed', [
                'testSession' => $testSession,
                'result'      => $result,
            ]);
        }

        $questions = Question::query()
            ->where('is_active', true)
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
        abort_if($testSession->status !== 'in_progress', 422, 'Sesi tes ini sudah tidak aktif.');

        $validated = $request->validate([
            'question_id'  => ['required', 'exists:questions,id'],
            'answer_value' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        $testSession->answers()->updateOrCreate(
            ['question_id' => $validated['question_id']],
            ['answer_value' => $validated['answer_value']]
        );

        return response()->json(['success' => true]);
    }

    public function recalculate(Request $request, TestSession $testSession)
    {
        $this->authorizeOwnership($testSession, $request);
        abort_if($testSession->status !== 'completed', 422, 'Sesi ini belum selesai dikerjakan.');

        try {
            DB::transaction(function () use ($testSession) {
                $testSession->result?->delete();
                $this->generateResult($testSession);
            });

            return redirect()->route('tests.show', $testSession->id)
                ->with('success', 'Hasil berhasil dihitung ulang.');
        } catch (\RuntimeException $e) {
            return back()->withErrors([
                'recalculate' => 'Masih gagal: '.$e->getMessage(),
            ]);
        }
    }

    public function complete(Request $request, TestSession $testSession)
    {
        $this->authorizeOwnership($testSession, $request);

        if ($testSession->status === 'completed') {
            return redirect()->route('tests.show', $testSession->id);
        }

        abort_if($testSession->status !== 'in_progress', 422, 'Sesi ini tidak bisa diselesaikan.');

        $totalActiveQuestions = Question::where('is_active', true)->count();
        $totalAnswered = $testSession->answers()->count();

        if ($totalAnswered < $totalActiveQuestions) {
            return back()->withErrors([
                'complete' => "Masih ada soal yang belum dijawab ({$totalAnswered}/{$totalActiveQuestions}).",
            ]);
        }

        DB::transaction(function () use ($testSession) {
            $locked = TestSession::whereKey($testSession->id)->lockForUpdate()->first();

            if ($locked->status === 'completed') {
                return;
            }

            $locked->update([
                'status'       => 'completed',
                'completed_at' => now(),
            ]);

            try {
                $this->generateResult($locked);
            } catch (\RuntimeException $e) {
                Log::warning('TOPSIS gagal dihitung untuk sesi #'.$locked->id.': '.$e->getMessage());
            }
        });

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

    private function authorizeOwnership(TestSession $testSession, Request $request): void
    {
        abort_if($testSession->user_id !== $request->user()->id, 403);
    }
}