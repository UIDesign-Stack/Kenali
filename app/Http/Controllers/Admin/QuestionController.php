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
            'question_text' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        DB::transaction(function () use ($validated, $subCriteria) {
            // Kunci baris induk, supaya dua admin yang menambah soal bersamaan tidak mendapat order yang sama
            SubCriteria::whereKey($subCriteria->id)->lockForUpdate()->first();

            $maxOrder = $subCriteria->questions()->max('order') ?? 0;

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
            'question_text' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        if ($question->question_text !== $validated['question_text'] && $question->answers()->exists()) {
            return back()->withErrors([
                'question_text' => 'Soal ini sudah pernah dijawab. Nonaktifkan lalu buat soal baru agar hasil tes lama tetap valid.',
            ]);
        }

        $question->update($validated);

        return back()->with('success', 'Soal berhasil diperbarui.');
    }

    public function toggleActive(Question $question)
    {
        $isActive = DB::transaction(function () use ($question) {
            SubCriteria::whereKey($question->sub_criteria_id)->lockForUpdate()->first();
            $question->refresh();

            if ($question->is_active) {
                $activeCount = Question::where('sub_criteria_id', $question->sub_criteria_id)
                    ->where('is_active', true)
                    ->count();

                if ($activeCount <= 1) {
                    return null;
                }
            }

            $question->update(['is_active' => ! $question->is_active]);

            return (bool) $question->is_active;
        });

        if ($isActive === null) {
            return back()->withErrors([
                'question' => 'Setiap sub-kriteria harus punya minimal satu soal aktif.',
            ]);
        }

        return back()->with('success', $isActive
            ? 'Soal berhasil diaktifkan.'
            : 'Soal berhasil dinonaktifkan.');
    }

    public function destroy(Question $question)
    {
        $deleted = DB::transaction(function () use ($question) {
            $locked = Question::whereKey($question->id)->lockForUpdate()->first();

            if (! $locked || $locked->answers()->exists()) {
                return false;
            }

            $locked->delete();

            return true;
        });

        if (! $deleted) {
            return back()->withErrors([
                'question' => 'Soal ini tidak bisa dihapus karena sudah pernah dijawab user. Nonaktifkan saja soal ini.',
            ]);
        }

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
            $ownedIds = $subCriteria->questions()->lockForUpdate()->pluck('id')->all();

            // Harus berisi semua soal milik sub-kriteria ini, tidak kurang, tidak lebih
            if (count($ids) !== count($ownedIds)
                || array_diff($ids, $ownedIds)
                || array_diff($ownedIds, $ids)) {
                return false;
            }

            $caseStatements = collect($ids)
                ->map(fn ($id, $index) => "WHEN {$id} THEN ".($index + 1))
                ->implode(' ');

            DB::table('questions')
                ->where('sub_criteria_id', $subCriteria->id)
                ->whereIn('id', $ids)
                ->update(['order' => DB::raw("CASE id {$caseStatements} END")]);

            return true;
        });

        if (! $ok) {
            return back()->withErrors([
                'question_ids' => 'Daftar soal tidak lengkap atau berisi soal dari sub-kriteria lain. Muat ulang halaman lalu coba lagi.',
            ]);
        }

        return back()->with('success', 'Urutan soal diperbarui.');
    }
}