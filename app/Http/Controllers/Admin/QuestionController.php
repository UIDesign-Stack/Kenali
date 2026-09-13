<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Criteria;
use App\Models\Question;
use App\Models\SubCriteria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class QuestionController extends Controller
{
    public function index()
    {
        $criteria = Criteria::with([
            'subCriteria' => fn ($q) => $q->withCount('questions')->orderBy('order'),
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
            'question_text' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($validated, $subCriteria) {

            $maxOrder = $subCriteria->questions()
                ->lockForUpdate()
                ->max('order') ?? 0;

            $subCriteria->questions()->create([
                'question_text' => $validated['question_text'],
                'order'         => $maxOrder + 1,
                'is_active'     => true,
            ]);
        });

        return back()->with('success', 'Soal berhasil ditambahkan.');
    }

    public function update(Request $request, Question $question)
    {
        $validated = $request->validate([
            'question_text' => ['required', 'string', 'max:1000'],
        ]);

        $question->update($validated);

        return back()->with('success', 'Soal berhasil diperbarui.');
    }

    public function toggleActive(Question $question)
    {
        $question->update(['is_active' => ! $question->is_active]);

        return back()->with('success', $question->is_active
            ? 'Soal berhasil diaktifkan.'
            : 'Soal berhasil dinonaktifkan.');
    }

    public function destroy(Question $question)
    {
        if ($question->answers()->exists()) {
            return back()->withErrors([
                'question' => 'Soal ini tidak bisa dihapus karena sudah pernah dijawab user. Nonaktifkan saja soal ini.',
            ]);
        }

        $question->delete();

        return back()->with('success', 'Soal berhasil dihapus.');
    }

    public function reorder(Request $request, SubCriteria $subCriteria)
    {
        $validated = $request->validate([
            'question_ids'   => ['required', 'array', 'min:1'],
            'question_ids.*' => ['required', 'integer', 'distinct', 'exists:questions,id'],
        ]);

        $ids = array_map('intval', $validated['question_ids']);

        $ownedIds = $subCriteria->questions()->pluck('id')->all();
        $foreignIds = array_diff($ids, $ownedIds);

        if (! empty($foreignIds)) {
            return back()->withErrors([
                'question_ids' => 'Terdapat soal yang bukan milik sub-kriteria ini.',
            ]);
        }

        DB::transaction(function () use ($ids, $subCriteria) {
            $caseStatements = collect($ids)
                ->map(fn ($id, $index) => "WHEN {$id} THEN ".($index + 1))
                ->implode(' ');

            DB::table('questions')
                ->where('sub_criteria_id', $subCriteria->id)
                ->whereIn('id', $ids)
                ->update([
                    'order' => DB::raw("CASE id {$caseStatements} END"),
                ]);
        });

        return back()->with('success', 'Urutan soal diperbarui.');
    }
}