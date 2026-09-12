<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alternative;
use App\Models\AlternativeProfile;
use App\Models\Criteria;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AlternativeProfileController extends Controller
{
    /**
     * Tampilkan form input skor ideal untuk semua sub-kriteria,
     * dikelompokkan per kriteria utama.
     */
    public function edit(Alternative $alternative)
    {
        $criteria = Criteria::with(['subCriteria' => fn ($q) => $q->orderBy('order')])
            ->orderBy('order')
            ->get();

        // Ambil skor yang sudah pernah diisi sebelumnya (kalau ada), key: sub_criteria_id
        $existingScores = $alternative->profiles()
            ->pluck('ideal_score', 'sub_criteria_id');

        return Inertia::render('Admin/Alternatives/ProfileEdit', [
            'alternative'    => $alternative->only('id', 'name'),
            'criteria'       => $criteria,
            'existingScores' => $existingScores,
        ]);
    }

    /**
     * Simpan/update semua skor ideal sekaligus.
     */
    public function update(Request $request, Alternative $alternative)
    {
        $validated = $request->validate([
            'scores'                  => ['required', 'array'],
            'scores.*.sub_criteria_id' => ['required', 'exists:sub_criteria,id'],
            'scores.*.ideal_score'     => ['required', 'numeric', 'min:1', 'max:5'],
        ]);

        foreach ($validated['scores'] as $score) {
            AlternativeProfile::updateOrCreate(
                [
                    'alternative_id'  => $alternative->id,
                    'sub_criteria_id' => $score['sub_criteria_id'],
                ],
                [
                    'ideal_score' => $score['ideal_score'],
                ]
            );
        }

        return redirect()
            ->route('admin.alternatives.index')
            ->with('success', "Profil ideal \"{$alternative->name}\" berhasil disimpan.");
    }
}