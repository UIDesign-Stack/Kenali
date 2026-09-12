<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Criteria;
use App\Models\CriteriaWeight;
use App\Models\SubCriteria;
use App\Services\AhpService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AhpController extends Controller
{
    public function __construct(protected AhpService $ahpService) {}

    /**
     * Tampilkan form input matriks perbandingan untuk KRITERIA UTAMA
     * (Minat, Bakat, Kepribadian).
     */
    public function criteriaIndex()
    {
        $criteria = Criteria::orderBy('id')->get(['id', 'code', 'name']);

        return Inertia::render('Admin/Ahp/CriteriaMatrix', [
            'items' => $criteria,
        ]);
    }

    /**
     * Hitung & simpan bobot AHP untuk kriteria utama.
     */
    public function criteriaStore(Request $request)
    {
        $validated = $request->validate([
            'matrix'         => ['required', 'array'],
            'matrix.*'       => ['required', 'array'],
            'criteria_ids'   => ['required', 'array', 'min:2'],
            'criteria_ids.*' => ['required', 'exists:criteria,id'],
        ]);

        $criteriaModels = Criteria::whereIn('id', $validated['criteria_ids'])
            ->orderByRaw('FIELD(id, '.implode(',', $validated['criteria_ids']).')')
            ->get();

        $labels = $criteriaModels->pluck('code')->toArray();
        $result = $this->ahpService->calculate($validated['matrix'], $labels);

        // Tolak simpan kalau tidak konsisten (CR > 0.1)
        if (! $result['is_consistent']) {
            return response()->json([
                'message'     => 'Matriks tidak konsisten (CR = '.$result['cr'].', harus ≤ 0.1). Silakan revisi penilaian perbandingan.',
                'errors'      => ['matrix' => ['Matriks tidak konsisten (CR = '.$result['cr'].', harus ≤ 0.1).']],
                'ahp_preview' => $result,
            ], 422);
        }

        foreach ($criteriaModels as $criteria) {
            CriteriaWeight::create([
                'criteria_id' => $criteria->id,
                'weight'      => $result['weights'][$criteria->code],
                'cr_value'    => $result['cr'],
                'set_by'      => $request->user()->id,
            ]);
        }

        return response()->json([
            'message'    => 'Bobot kriteria utama berhasil disimpan.',
            'ahp_result' => $result,
        ]);
    }

    /**
     * Tampilkan form input matriks perbandingan untuk SUB-KRITERIA
     * di dalam satu kriteria utama tertentu.
     */
    public function subCriteriaIndex(Criteria $criteria)
    {
        $subCriteria = $criteria->subCriteria()->orderBy('id')->get(['id', 'code', 'name', 'criteria_id']);

        return Inertia::render('Admin/Ahp/SubCriteriaMatrix', [
            'criteria' => $criteria->only('id', 'name', 'code'),
            'items'    => $subCriteria,
        ]);
    }

    /**
     * Hitung & simpan bobot AHP untuk sub-kriteria.
     * Catatan: bobot lokal sub-kriteria disimpan di kolom terpisah,
     * bukan di tabel criteria_weights (yang khusus kriteria utama).
     * Tambahkan kolom `local_weight` di tabel sub_criteria jika belum ada.
     */
    public function subCriteriaStore(Request $request, Criteria $criteria)
    {
        $validated = $request->validate([
            'matrix'            => ['required', 'array'],
            'matrix.*'          => ['required', 'array'],
            'sub_criteria_ids'   => ['required', 'array', 'min:2'],
            'sub_criteria_ids.*' => ['required', 'exists:sub_criteria,id'],
        ]);

        $subCriteriaModels = SubCriteria::whereIn('id', $validated['sub_criteria_ids'])
            ->orderByRaw('FIELD(id, '.implode(',', $validated['sub_criteria_ids']).')')
            ->get();

        $labels = $subCriteriaModels->pluck('code')->toArray();
        $result = $this->ahpService->calculate($validated['matrix'], $labels);

        if (! $result['is_consistent']) {
            return response()->json([
                'message'     => 'Matriks tidak konsisten (CR = '.$result['cr'].', harus ≤ 0.1). Silakan revisi penilaian perbandingan.',
                'errors'      => ['matrix' => ['Matriks tidak konsisten (CR = '.$result['cr'].', harus ≤ 0.1).']],
                'ahp_preview' => $result,
            ], 422);
        }

        foreach ($subCriteriaModels as $sub) {
            $sub->update(['local_weight' => $result['weights'][$sub->code]]);
        }

        return response()->json([
            'message'    => 'Bobot sub-kriteria berhasil disimpan.',
            'ahp_result' => $result,
        ]);
    }
}