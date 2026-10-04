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
            'matrix'         => ['required', 'array', 'min:2', 'max:10'],
            'matrix.*'       => ['required', 'array', 'min:2', 'max:10'],
            'matrix.*.*'     => ['required', 'numeric', 'min:0.111', 'max:9'],
            'criteria_ids'   => ['required', 'array', 'min:2', 'max:10'],
            'criteria_ids.*' => ['required', 'integer', 'distinct', 'exists:criteria,id'],
        ]);

        $ids = array_map('intval', array_values($validated['criteria_ids']));

        if (count($ids) !== Criteria::count()) {
            return response()->json([
                'message' => 'Semua kriteria utama harus ikut dinilai.',
                'errors'  => ['criteria_ids' => ['Jumlah kriteria tidak lengkap.']],
            ], 422);
        }

        $criteriaModels = Criteria::whereIn('id', $ids)->get()
            ->sortBy(fn ($c) => array_search($c->id, $ids, true))
            ->values();

        return $this->processAhpMatrix(
            $validated['matrix'],
            $criteriaModels,
            successMessage: 'Bobot kriteria utama berhasil disimpan.',
            onSuccess: function (array $result) use ($criteriaModels, $request) {

                CriteriaWeight::where('is_active', true)->lockForUpdate()->get();

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
            'matrix'             => ['required', 'array', 'min:2', 'max:10'],
            'matrix.*'           => ['required', 'array', 'min:2', 'max:10'],
            'matrix.*.*'         => ['required', 'numeric', 'min:0.111', 'max:9'],
            'sub_criteria_ids'   => ['required', 'array', 'min:2', 'max:10'],
            'sub_criteria_ids.*' => ['required', 'integer', 'distinct', 'exists:sub_criteria,id'],
        ]);

        $ids = array_map('intval', array_values($validated['sub_criteria_ids']));

        $subCriteriaModels = SubCriteria::whereIn('id', $ids)
            ->where('criteria_id', $criteria->id)
            ->get()
            ->sortBy(fn ($s) => array_search($s->id, $ids, true))
            ->values();

        if ($subCriteriaModels->count() !== count($ids)) {
            return response()->json([
                'message' => 'Salah satu sub-kriteria tidak ditemukan atau bukan milik kriteria ini.',
                'errors'  => ['sub_criteria_ids' => ['ID sub-kriteria tidak valid untuk kriteria ini.']],
            ], 422);
        }

        if (count($ids) !== $criteria->subCriteria()->count()) {
            return response()->json([
                'message' => 'Semua sub-kriteria harus ikut dinilai.',
                'errors'  => ['sub_criteria_ids' => ['Jumlah sub-kriteria tidak lengkap.']],
            ], 422);
        }

        return $this->processAhpMatrix(
            $validated['matrix'],
            $subCriteriaModels,
            successMessage: 'Bobot sub-kriteria berhasil disimpan.',
            onSuccess: function (array $result) use ($criteriaModels, $request) {
                // Kunci kriteria supaya dua admin yang menyimpan bersamaan berjalan bergantian
                Criteria::whereIn('id', $criteriaModels->pluck('id'))->lockForUpdate()->get();

                foreach ($criteriaModels as $criteria) {
                    CriteriaWeight::create([
                        'criteria_id' => $criteria->id,
                        'weight'      => $result['weights'][$criteria->code],
                        'cr_value'    => $result['cr'],
                        'set_by'      => $request->user()->id,
                    ]);
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
        $labels = $models->pluck('code')->values()->toArray();

        $matrix = array_map(
            fn ($row) => array_map('floatval', array_values($row)),
            array_values($matrix)
        );

        $expectedSize = count($labels);
        if (count($matrix) !== $expectedSize || collect($matrix)->contains(fn ($row) => count($row) !== $expectedSize)) {
            return $this->invalidMatrix("Ukuran matriks harus {$expectedSize}x{$expectedSize} sesuai jumlah item yang dipilih.");
        }

        for ($i = 0; $i < $expectedSize; $i++) {
            if (abs($matrix[$i][$i] - 1) > 0.0001) {
                return $this->invalidMatrix('Diagonal matriks harus bernilai 1.');
            }
            for ($j = $i + 1; $j < $expectedSize; $j++) {
                if (abs($matrix[$i][$j] * $matrix[$j][$i] - 1) > 0.05) {
                    return $this->invalidMatrix('Matriks harus resiprokal (nilai a[i][j] x a[j][i] harus mendekati 1).');
                }
            }
        }

        try {
            $result = $this->ahpService->calculate($matrix, $labels);
        } catch (\InvalidArgumentException $e) {
            return $this->invalidMatrix($e->getMessage());
        }

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
    private function invalidMatrix(string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'errors'  => ['matrix' => [$message]],
        ], 422);
    }
}