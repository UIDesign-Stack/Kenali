<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Criteria;
use App\Models\CriteriaWeight;
use App\Models\SubCriteria;
use App\Services\AhpService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AhpController extends Controller
{
    public function __construct(protected AhpService $ahpService) {}

    public function criteriaIndex()
    {
        $criteria = Criteria::orderBy('id')->get(['id', 'code', 'name']);

        return Inertia::render('Admin/Ahp/CriteriaMatrix', [
            'items' => $criteria,
        ]);
    }

    public function criteriaStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'matrix'         => ['required', 'array'],
            'matrix.*'       => ['required', 'array'],
            'criteria_ids'   => ['required', 'array', 'min:2'],
            'criteria_ids.*' => ['required', 'integer', 'exists:criteria,id'],
        ]);

        $ids = array_map('intval', $validated['criteria_ids']);

        $criteriaModels = Criteria::whereIn('id', $ids)
            ->orderByRaw('FIELD(id, '.implode(',', $ids).')')
            ->get();

        return $this->processAhpMatrix(
            $validated['matrix'],
            $criteriaModels,
            successMessage: 'Bobot kriteria utama berhasil disimpan.',
            onSuccess: function (array $result) use ($criteriaModels, $request) {

                CriteriaWeight::whereIn('criteria_id', $criteriaModels->pluck('id'))
                    ->update(['is_active' => false]);

                foreach ($criteriaModels as $criteria) {
                    CriteriaWeight::create([
                        'criteria_id' => $criteria->id,
                        'weight'      => $result['weights'][$criteria->code],
                        'cr_value'    => $result['cr'],
                        'set_by'      => $request->user()->id,
                        'is_active'   => true,
                    ]);
                }
            }
        );
    }

    public function subCriteriaIndex(Criteria $criteria)
    {
        $subCriteria = $criteria->subCriteria()->orderBy('id')->get(['id', 'code', 'name', 'criteria_id']);

        return Inertia::render('Admin/Ahp/SubCriteriaMatrix', [
            'criteria' => $criteria->only('id', 'name', 'code'),
            'items'    => $subCriteria,
        ]);
    }

    public function subCriteriaStore(Request $request, Criteria $criteria): JsonResponse
    {
        $validated = $request->validate([
            'matrix'             => ['required', 'array'],
            'matrix.*'           => ['required', 'array'],
            'sub_criteria_ids'   => ['required', 'array', 'min:2'],
            'sub_criteria_ids.*' => ['required', 'integer', 'exists:sub_criteria,id'],
        ]);

        $ids = array_map('intval', $validated['sub_criteria_ids']);

        $subCriteriaModels = SubCriteria::whereIn('id', $ids)
            ->where('criteria_id', $criteria->id)
            ->orderByRaw('FIELD(id, '.implode(',', $ids).')')
            ->get();

        if ($subCriteriaModels->count() !== count($ids)) {
            return response()->json([
                'message' => 'Salah satu sub-kriteria tidak ditemukan atau bukan milik kriteria ini.',
                'errors'  => ['sub_criteria_ids' => ['ID sub-kriteria tidak valid untuk kriteria ini.']],
            ], 422);
        }

        return $this->processAhpMatrix(
            $validated['matrix'],
            $subCriteriaModels,
            successMessage: 'Bobot sub-kriteria berhasil disimpan.',
            onSuccess: function (array $result) use ($subCriteriaModels) {
                foreach ($subCriteriaModels as $sub) {
                    $sub->update(['local_weight' => $result['weights'][$sub->code]]);
                }
            }
        );
    }

    private function processAhpMatrix(
        array $matrix,
        Collection $models,
        string $successMessage,
        callable $onSuccess
    ): JsonResponse {
        $labels = $models->pluck('code')->toArray();

        $expectedSize = count($labels);
        if (count($matrix) !== $expectedSize || collect($matrix)->contains(fn ($row) => count($row) !== $expectedSize)) {
            return response()->json([
                'message' => "Ukuran matriks harus {$expectedSize}x{$expectedSize} sesuai jumlah item yang dipilih.",
                'errors'  => ['matrix' => ['Ukuran matriks tidak sesuai jumlah item.']],
            ], 422);
        }

        $result = $this->ahpService->calculate($matrix, $labels);

        if (! $result['is_consistent']) {
            return response()->json([
                'message'     => "Matriks tidak konsisten (CR = {$result['cr']}, harus ≤ 0.1). Silakan revisi penilaian perbandingan.",
                'errors'      => ['matrix' => ["Matriks tidak konsisten (CR = {$result['cr']}, harus ≤ 0.1)."]],
                'ahp_preview' => $result,
            ], 422);
        }

        DB::transaction(fn () => $onSuccess($result));

        return response()->json([
            'message'    => $successMessage,
            'ahp_result' => $result,
        ]);
    }
}