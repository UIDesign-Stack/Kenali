<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\TestSession;
use App\Services\TopsisService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TestController extends Controller
{
    public function __construct(protected TopsisService $topsisService) {}

    /**
     * Riwayat semua sesi tes milik user, terbaru dulu.
     */
    public function index(Request $request)
    {
        $sessions = $request->user()
            ->testSessions()
            ->with(['result.details' => fn ($q) => $q->orderBy('rank')->limit(1)->with('alternative:id,name')])
            ->latest('started_at')
            ->get();

        return Inertia::render('Tests/Index', [
            'sessions' => $sessions,
        ]);
    }

    /**
     * Halaman awal: cek apakah user punya sesi tes yang belum selesai,
     * atau tampilkan pilihan untuk mulai tes baru.
     */
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

    /**
     * Buat sesi tes baru untuk fase hidup yang dipilih.
     */
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

    /**
     * Halaman pengisian soal untuk satu sesi tes.
     */
    public function show(TestSession $testSession, Request $request)
    {
        abort_if($testSession->user_id !== $request->user()->id, 403);

        // Kalau sesi sudah selesai, tampilkan halaman hasil
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

    /**
     * Simpan/perbarui satu jawaban (dipanggil tiap kali user memilih skala).
     */
    public function saveAnswer(Request $request, TestSession $testSession)
    {
        abort_if($testSession->user_id !== $request->user()->id, 403);
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

    /**
     * Coba hitung ulang hasil TOPSIS untuk sesi yang sudah selesai
     * tapi gagal dihitung sebelumnya (misal karena bobot AHP belum lengkap saat itu).
     */
    public function recalculate(Request $request, TestSession $testSession)
    {
        abort_if($testSession->user_id !== $request->user()->id, 403);
        abort_if($testSession->status !== 'completed', 422, 'Sesi ini belum selesai dikerjakan.');

        // Hapus hasil lama kalau ada (misal hasil kosong dari percobaan gagal sebelumnya)
        $testSession->result?->delete();

        try {
            $this->generateResult($testSession);

            return redirect()->route('tests.show', $testSession->id)
                ->with('success', 'Hasil berhasil dihitung ulang.');
        } catch (\RuntimeException $e) {
            return back()->withErrors([
                'recalculate' => 'Masih gagal: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Tandai sesi tes selesai, lalu hitung hasil TOPSIS.
     */
    public function complete(Request $request, TestSession $testSession)
    {
        abort_if($testSession->user_id !== $request->user()->id, 403);

        $totalActiveQuestions = Question::where('is_active', true)->count();
        $totalAnswered = $testSession->answers()->count();

        if ($totalAnswered < $totalActiveQuestions) {
            return back()->withErrors([
                'complete' => "Masih ada soal yang belum dijawab ({$totalAnswered}/{$totalActiveQuestions}).",
            ]);
        }

        $testSession->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);

        try {
            $this->generateResult($testSession);
        } catch (\RuntimeException $e) {
            // Kalau data bobot/alternatif belum lengkap, tes tetap ditandai selesai,
            // tapi hasil belum bisa dihitung -- ini akan terlihat sebagai "hasil belum tersedia".
            \Log::warning('TOPSIS gagal dihitung untuk sesi #'.$testSession->id.': '.$e->getMessage());
        }

        return redirect()->route('tests.show', $testSession->id)
            ->with('success', 'Tes selesai! Berikut hasil rekomendasimu.');
    }

    /**
     * Jalankan TOPSIS dan simpan hasilnya ke database.
     */
    protected function generateResult(TestSession $testSession): void
    {
        $rawResults = $this->topsisService->calculate($testSession);

        // Urutkan berdasarkan skor tertinggi -> beri rank
        usort($rawResults, fn ($a, $b) => $b['score'] <=> $a['score']);

        $testResult = $testSession->result()->create([]);

        foreach ($rawResults as $index => $row) {
            $testResult->details()->create([
                'alternative_id' => $row['alternative_id'],
                'score'          => $row['score'],
                'rank'           => $index + 1,
            ]);
        }
    }
}