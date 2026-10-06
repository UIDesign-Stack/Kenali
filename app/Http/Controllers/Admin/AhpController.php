<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Criteria;
use App\Models\CriteriaWeight;
use App\Models\SubCriteria;
use App\Services\AhpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Inertia\Inertia;

class AhpController extends Controller
{
    /**
     * Batas ukuran matriks. Harus sama dengan tabel Random Index (RI)
     * di AhpService (saat ini n = 1..10), kalau tidak request akan
     * lolos validasi lalu gagal dengan 500 di service.
     */
    private const MAX_ITEMS = 10;

    /**
     * Rentang skala Saaty: 1/9 sampai 9 (dibulatkan sedikit di bawah 1/9
     * agar nilai kebalikan seperti 0.1111 tidak ditolak).
     */
    private const MIN_VALUE = 0.11;
    private const MAX_VALUE = 9;

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
        $validated = $this->validateMatrixRequest($request, 'criteria_ids', 'criteria', 'id');

        $criteriaModels = $this->orderedModels(Criteria::class, $validated['criteria_ids']);

        if ($criteriaModels->count() !== Criteria::count()) {
            return $this->errorResponse('Semua kriteria harus ikut dinilai dalam matriks perbandingan.');
        }

        $labels = $criteriaModels->pluck('code')->all();

        if (! $this->labelsAreUnique($labels)) {
            return $this->errorResponse('Kode kriteria harus unik agar bobot dapat dipetakan dengan benar.');
        }

        $result = $this->ahpService->calculate($validated['matrix'], $labels);

        if (! $result['is_consistent']) {
            return $this->inconsistentResponse($result);
        }

        $userId = $request->user()->id;

        DB::transaction(function () use ($criteriaModels, $result, $userId) {
            foreach ($criteriaModels as $criteria) {
                CriteriaWeight::create([
                    'criteria_id' => $criteria->id,
                    'weight'      => $result['weights'][$criteria->code],
                    'cr_value'    => $result['cr'],
                    'set_by'      => $userId,
                ]);
            }
        });

        return response()->json([
            'message'    => 'Bobot kriteria utama berhasil disimpan.',
            'ahp_result' => $result,
        ]);
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
        $validated = $this->validateMatrixRequest(
            $request,
            'sub_criteria_ids',
            'sub_criteria',
            'id',
            fn (Exists $rule) => $rule->where('criteria_id', $criteria->id)
        );

        $subCriteriaModels = $this->orderedModels(SubCriteria::class, $validated['sub_criteria_ids']);

        if ($subCriteriaModels->count() !== $criteria->subCriteria()->count()) {
            return $this->errorResponse('Semua sub-kriteria dari kriteria ini harus ikut dinilai dalam matriks perbandingan.');
        }

        $labels = $subCriteriaModels->pluck('code')->all();

        if (! $this->labelsAreUnique($labels)) {
            return $this->errorResponse('Kode sub-kriteria harus unik agar bobot dapat dipetakan dengan benar.');
        }

        $result = $this->ahpService->calculate($validated['matrix'], $labels);

        if (! $result['is_consistent']) {
            return $this->inconsistentResponse($result);
        }

        DB::transaction(function () use ($subCriteriaModels, $result) {
            foreach ($subCriteriaModels as $sub) {
                $sub->forceFill(['local_weight' => $result['weights'][$sub->code]])->save();
            }
        });

        return response()->json([
            'message'    => 'Bobot sub-kriteria berhasil disimpan.',
            'ahp_result' => $result,
        ]);
    }

    /**
     * Validasi request matriks perbandingan berpasangan dalam satu validator.
     */
    protected function validateMatrixRequest(
        Request $request,
        string $idsField,
        string $table,
        string $column,
        ?\Closure $extraRule = null
    ): array {
        $existsRule = Rule::exists($table, $column);
        if ($extraRule) {
            $existsRule = $extraRule($existsRule);
        }

        $max = self::MAX_ITEMS;
        $min = self::MIN_VALUE;
        $top = self::MAX_VALUE;

        $validator = Validator::make($request->all(), [
            $idsField       => ['required', 'array', 'min:2', "max:{$max}"],
            "{$idsField}.*" => ['required', 'integer', 'distinct', $existsRule],
            'matrix'        => ['required', 'array', 'min:2', "max:{$max}"],
            'matrix.*'      => ['required', 'array', 'min:2', "max:{$max}"],
            'matrix.*.*'    => ['required', 'numeric', "between:{$min},{$top}"],
        ]);

        $validator->after(function ($v) use ($request, $idsField) {
            if ($v->errors()->any()) {
                return;
            }

            $ids    = $request->input($idsField);
            $matrix = $request->input('matrix');
            $n      = count($ids);

            if (! array_is_list($ids) || ! array_is_list($matrix) || count($matrix) !== $n) {
                $v->errors()->add('matrix', "Matriks harus berukuran {$n}x{$n} sesuai jumlah item.");
                return;
            }

            foreach ($matrix as $row) {
                if (! array_is_list($row) || count($row) !== $n) {
                    $v->errors()->add('matrix', "Setiap baris matriks harus berisi {$n} nilai.");
                    return;
                }
            }

            for ($i = 0; $i < $n; $i++) {
                if (abs($matrix[$i][$i] - 1) > 0.001) {
                    $v->errors()->add('matrix', 'Diagonal matriks harus bernilai 1.');
                    return;
                }
                for ($j = $i + 1; $j < $n; $j++) {
                    if (abs($matrix[$i][$j] * $matrix[$j][$i] - 1) > 0.03) {
                        $v->errors()->add('matrix', 'Matriks harus resiprokal: nilai [i][j] dan [j][i] harus saling berkebalikan.');
                        return;
                    }
                }
            }
        });

        return $validator->validate();
    }

    /**
     * Ambil model sesuai urutan ID dari request, tanpa FIELD() agar
     * tidak bergantung pada MySQL/MariaDB.
     *
     * @param  class-string  $modelClass
     */
    protected function orderedModels(string $modelClass, array $ids): Collection
    {
        $byId = $modelClass::whereIn('id', $ids)->get()->keyBy('id');

        return collect($ids)
            ->map(fn ($id) => $byId->get((int) $id))
            ->filter()
            ->values();
    }

    protected function labelsAreUnique(array $labels): bool
    {
        return count($labels) === count(array_unique($labels));
    }

    protected function inconsistentResponse(array $result): JsonResponse
    {
        $message = 'Matriks tidak konsisten (CR = '.$result['cr'].', harus ≤ 0.1).';

        return response()->json([
            'message'     => $message.' Silakan revisi penilaian perbandingan.',
            'errors'      => ['matrix' => [$message]],
            'ahp_preview' => $result,
        ], 422);
    }

    protected function errorResponse(string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'errors'  => ['matrix' => [$message]],
        ], 422);
    }
}
