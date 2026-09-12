<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Criteria;
use App\Models\Question;
use App\Models\SubCriteria;
use Illuminate\Http\Request;
use Inertia\Inertia;

class QuestionController extends Controller
{
    /**
     * Halaman ringkasan: daftar semua sub-kriteria dikelompokkan per kriteria,
     * beserta jumlah soal yang sudah dibuat untuk masing-masing.
     */
    public function index()
    {
        $criteria = Criteria::with([
            'subCriteria' => fn ($q) => $q->withCount('questions')->orderBy('order'),
        ])->orderBy('order')->get();

        return Inertia::render('Admin/Questions/Index', [
            'criteria' => $criteria,
        ]);
    }

    /**
     * Halaman kelola soal untuk satu sub-kriteria tertentu.
     */
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

    /**
     * Simpan soal baru untuk sub-kriteria tertentu.
     */
    public function store(Request $request, SubCriteria $subCriteria)
    {
        $validated = $request->validate([
            'question_text' => ['required', 'string', 'max:1000'],
        ]);

        $maxOrder = $subCriteria->questions()->max('order') ?? 0;

        $subCriteria->questions()->create([
            'question_text' => $validated['question_text'],
            'order'         => $maxOrder + 1,
            'is_active'     => true,
        ]);

        return back()->with('success', 'Soal berhasil ditambahkan.');
    }

    /**
     * Update teks soal.
     */
    public function update(Request $request, Question $question)
    {
        $validated = $request->validate([
            'question_text' => ['required', 'string', 'max:1000'],
        ]);

        $question->update($validated);

        return back()->with('success', 'Soal berhasil diperbarui.');
    }

    /**
     * Toggle status aktif/nonaktif soal (bukan dihapus permanen).
     */
    public function toggleActive(Question $question)
    {
        $question->update(['is_active' => ! $question->is_active]);

        return back()->with('success', 'Status soal diperbarui.');
    }

    /**
     * Hapus soal permanen.
     * Hanya boleh kalau soal belum pernah dijawab user (jaga integritas data test_answers).
     */
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

    /**
     * Update urutan soal (dipanggil setelah drag & drop di frontend).
     */
    public function reorder(Request $request, SubCriteria $subCriteria)
    {
        $validated = $request->validate([
            'question_ids'   => ['required', 'array'],
            'question_ids.*' => ['required', 'exists:questions,id'],
        ]);

        foreach ($validated['question_ids'] as $index => $id) {
            Question::where('id', $id)->update(['order' => $index + 1]);
        }

        return back()->with('success', 'Urutan soal diperbarui.');
    }
}