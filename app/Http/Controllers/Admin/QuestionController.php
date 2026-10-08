<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Criteria;
use App\Models\Question;
use App\Models\SubCriteria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class QuestionController extends Controller
{
    public function index()
    {
        $criteria = Criteria::with([
            'subCriteria' => fn ($q) => $q->withCount([
                'questions',
                'questions as active_questions_count' => fn ($q) => $q->where('is_active', true),
            ])->orderBy('order'),
        ])->orderBy('order')->get();

        return Inertia::render('Admin/Questions/Index', [
            'criteria' => $criteria,
        ]);
    }

    public function manage(SubCriteria $subCriteria)
    {
        $subCriteria->load('criteria');

        $questions = $subCriteria->questions()
            ->orderBy('order')
            ->orderBy('id')
            ->get();

        return Inertia::render('Admin/Questions/Manage', [
            'subCriteria' => $subCriteria,
            'questions'   => $questions,
        ]);
    }

    public function store(Request $request, SubCriteria $subCriteria)
    {
        $validated = $request->validate([
            'question_text' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $question = DB::transaction(function () use ($validated, $subCriteria) {

            SubCriteria::whereKey($subCriteria->id)->lockForUpdate()->firstOrFail();

            $maxOrder = $subCriteria->questions()->max('order') ?? 0;

            $question = new Question();
            $question->sub_criteria_id = $subCriteria->id;
            $question->question_text   = $validated['question_text'];
            $question->order           = $maxOrder + 1;
            $question->is_active       = true;
            $question->save();

            return $question;
        });

        Log::info('Soal ditambahkan', [
            'question_id'     => $question->id,
            'sub_criteria_id' => $subCriteria->id,
            'admin_id'        => $request->user()->id,
        ]);

        return back()->with('success', 'Soal berhasil ditambahkan.');
    }

    public function update(Request $request, Question $question)
    {
        $validated = $request->validate([
            'question_text' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $result = DB::transaction(function () use ($question, $validated) {

            $locked = Question::whereKey($question->id)->lockForUpdate()->firstOrFail();

            if ($locked->question_text === $validated['question_text']) {
                return 'unchanged';
            }

            if ($locked->answers()->exists()) {
                return 'has_answers';
            }

            $locked->question_text = $validated['question_text'];
            $locked->save();

            return 'updated';
        });

        if ($result === 'has_answers') {
            return back()->withErrors([
                'question_text' => 'Soal ini sudah pernah dijawab. Nonaktifkan lalu buat soal baru agar hasil tes lama tetap valid.',
            ]);
        }

        if ($result === 'updated') {
            Log::info('Teks soal diperbarui', [
                'question_id' => $question->id,
                'admin_id'    => $request->user()->id,
            ]);
        }

        return back()->with('success', 'Soal berhasil diperbarui.');
    }

    public function toggleActive(Request $request, Question $question)
    {
        $isActive = DB::transaction(function () use ($question) {

            SubCriteria::whereKey($question->sub_criteria_id)->lockForUpdate()->firstOrFail();
            $locked = Question::whereKey($question->id)->lockForUpdate()->firstOrFail();

            if ($locked->is_active && $this->activeQuestionCount($locked->sub_criteria_id) <= 1) {
                return null;
            }

            $locked->forceFill(['is_active' => ! $locked->is_active])->save();

            return (bool) $locked->is_active;
        });

        if ($isActive === null) {
            return back()->withErrors([
                'question' => 'Setiap sub-kriteria harus punya minimal satu soal aktif.',
            ]);
        }

        Log::info('Status soal diubah', [
            'question_id' => $question->id,
            'admin_id'    => $request->user()->id,
            'is_active'   => $isActive,
        ]);

        return back()->with('success', $isActive
            ? 'Soal berhasil diaktifkan.'
            : 'Soal berhasil dinonaktifkan.');
    }

    public function destroy(Request $request, Question $question)
    {
        $result = DB::transaction(function () use ($question) {
            SubCriteria::whereKey($question->sub_criteria_id)->lockForUpdate()->firstOrFail();
            $locked = Question::whereKey($question->id)->lockForUpdate()->first();

            if (! $locked) {
                return 'not_found';
            }

            if ($locked->answers()->exists()) {
                return 'has_answers';
            }

            if ($locked->is_active && $this->activeQuestionCount($locked->sub_criteria_id) <= 1) {
                return 'last_active';
            }

            $locked->delete();

            return 'deleted';
        });

        if ($result === 'has_answers') {
            return back()->withErrors([
                'question' => 'Soal ini tidak bisa dihapus karena sudah pernah dijawab user. Nonaktifkan saja soal ini.',
            ]);
        }

        if ($result === 'last_active') {
            return back()->withErrors([
                'question' => 'Setiap sub-kriteria harus punya minimal satu soal aktif. Tambah soal aktif lain dulu sebelum menghapus soal ini.',
            ]);
        }

        if ($result === 'not_found') {
            return back()->withErrors(['question' => 'Soal tidak ditemukan.']);
        }

        Log::info('Soal dihapus', [
            'question_id'     => $question->id,
            'sub_criteria_id' => $question->sub_criteria_id,
            'admin_id'        => $request->user()->id,
        ]);

        return back()->with('success', 'Soal berhasil dihapus.');
    }

    public function reorder(Request $request, SubCriteria $subCriteria)
    {
        $validated = $request->validate([
            'question_ids'   => ['required', 'array', 'min:1', 'max:500'],
            'question_ids.*' => ['required', 'integer', 'distinct'],
        ]);

        $ids = array_map('intval', array_values($validated['question_ids']));

        $ok = DB::transaction(function () use ($ids, $subCriteria) {

            SubCriteria::whereKey($subCriteria->id)->lockForUpdate()->firstOrFail();

            $ownedIds = $subCriteria->questions()->lockForUpdate()->pluck('id')->all();

            if (count($ids) !== count($ownedIds)
                || array_diff($ids, $ownedIds)
                || array_diff($ownedIds, $ids)) {
                return false;
            }

            $caseStatements = collect($ids)
                ->map(fn ($id, $index) => "WHEN {$id} THEN ".($index + 1))
                ->implode(' ');

            Question::where('sub_criteria_id', $subCriteria->id)
                ->whereIn('id', $ids)
                ->update(['order' => DB::raw("CASE id {$caseStatements} END")]);

            return true;
        });

        if (! $ok) {
            return back()->withErrors([
                'question_ids' => 'Daftar soal tidak lengkap atau berisi soal dari sub-kriteria lain. Muat ulang halaman lalu coba lagi.',
            ]);
        }

        Log::info('Urutan soal diubah', [
            'sub_criteria_id' => $subCriteria->id,
            'admin_id'        => $request->user()->id,
        ]);

        return back()->with('success', 'Urutan soal diperbarui.');
    }

    protected function activeQuestionCount(int $subCriteriaId): int
    {
        return Question::where('sub_criteria_id', $subCriteriaId)
            ->where('is_active', true)
            ->count();
    }
}
