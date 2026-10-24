<?php

namespace App\Services;

use App\Enums\LifePhase;
use App\Models\Alternative;
use App\Models\SubCriteria;
use App\Models\TestSession;
use Illuminate\Support\Facades\Log;

class TopsisService
{
    public function calculate(TestSession $testSession): array
    {

        $userScores = $this->getUserScoresPerSubCriteria($testSession);

        $globalWeights = $this->getGlobalWeights();

        $allSubIds = SubCriteria::pluck('id')->all();
        $missingWeights = array_diff($allSubIds, array_keys($globalWeights));

        if (! empty($missingWeights)) {
            throw new \RuntimeException('Bobot AHP belum lengkap untuk semua sub-kriteria.');
        }

        $subCriteriaIds = array_keys(array_intersect_key($globalWeights, $userScores));

        if (empty($subCriteriaIds)) {
            throw new \RuntimeException('Tidak ada data bobot atau jawaban yang cukup untuk menghitung TOPSIS.');
        }

        $globalWeights = array_intersect_key($globalWeights, array_flip($subCriteriaIds));
        $totalWeight = array_sum($globalWeights);
        if ($totalWeight > 0) {
            $globalWeights = array_map(fn ($w) => $w / $totalWeight, $globalWeights);
        }

        $all = Alternative::where('is_active', true)
            ->where(function ($query) use ($testSession) {
                $query->where('life_phase', LifePhase::Umum->value)
                    ->orWhere('life_phase', $testSession->life_phase);
            })
            ->with(['profiles' => fn ($q) => $q->whereIn('sub_criteria_id', $subCriteriaIds)])
            ->get();

        $alternatives = $all->filter(fn ($alt) => $alt->profiles->count() === count($subCriteriaIds));

        if ($all->count() !== $alternatives->count()) {
            Log::warning('Alternatif dilewati karena profil tidak lengkap', [
                'ids' => $all->diff($alternatives)->pluck('id')->all(),
            ]);
        }

        if ($alternatives->isEmpty()) {
            throw new \RuntimeException('Tidak ada alternatif dengan profil ideal lengkap untuk dihitung.');
        }

        if ($alternatives->count() < 2) {
            throw new \RuntimeException('Minimal dua alternatif aktif dengan profil lengkap diperlukan.');
        }

        $decisionMatrix = [];
        foreach ($alternatives as $alt) {
            $row = [];
            foreach ($subCriteriaIds as $subId) {
                $idealScore = $alt->profiles->firstWhere('sub_criteria_id', $subId)->ideal_score;
                $userScore = $userScores[$subId];
                $row[$subId] = max(0, 5 - abs($userScore - $idealScore));
            }
            $decisionMatrix[$alt->id] = $row;
        }

        $columnDenominators = [];
        foreach ($subCriteriaIds as $subId) {
            $sumSquares = 0;
            foreach ($decisionMatrix as $row) {
                $sumSquares += $row[$subId] ** 2;
            }
            $columnDenominators[$subId] = sqrt($sumSquares) ?: 1;
        }

        $normalizedMatrix = [];
        foreach ($decisionMatrix as $altId => $row) {
            foreach ($row as $subId => $value) {
                $normalizedMatrix[$altId][$subId] = $value / $columnDenominators[$subId];
            }
        }

        $weightedMatrix = [];
        foreach ($normalizedMatrix as $altId => $row) {
            foreach ($row as $subId => $value) {
                $weightedMatrix[$altId][$subId] = $value * $globalWeights[$subId];
            }
        }

        $idealPositive = [];
        $idealNegative = [];
        foreach ($subCriteriaIds as $subId) {
            $columnValues = array_column($weightedMatrix, $subId);
            $idealPositive[$subId] = max($columnValues);
            $idealNegative[$subId] = min($columnValues);
        }


        $results = [];
        foreach ($weightedMatrix as $altId => $row) {
            $distancePositive = 0;
            $distanceNegative = 0;

            foreach ($subCriteriaIds as $subId) {
                $distancePositive += ($row[$subId] - $idealPositive[$subId]) ** 2;
                $distanceNegative += ($row[$subId] - $idealNegative[$subId]) ** 2;
            }

            $distancePositive = sqrt($distancePositive);
            $distanceNegative = sqrt($distanceNegative);

            $denominator = $distancePositive + $distanceNegative;
            $closeness = $denominator > 0 ? $distanceNegative / $denominator : 0;

            $results[] = [
                'alternative_id' => $altId,
                'score'          => round($closeness, 4),
            ];
        }

        return $results;
    }

    /**
     * Ambil skor rata-rata jawaban user untuk tiap sub-kriteria dalam satu sesi.
     * Hanya soal yang masih aktif yang dihitung.
     */
    protected function getUserScoresPerSubCriteria(TestSession $testSession): array
    {
        return $testSession->answers()
            ->join('questions', 'test_answers.question_id', '=', 'questions.id')
            ->where('questions.is_active', true)
            ->selectRaw('questions.sub_criteria_id, AVG(test_answers.answer_value) as avg_score')
            ->groupBy('questions.sub_criteria_id')
            ->pluck('avg_score', 'sub_criteria_id')
            ->map(fn ($val) => (float) $val)
            ->toArray();
    }

    /**
     * Ambil bobot global (kriteria utama x lokal sub-kriteria) untuk semua sub-kriteria
     * yang sudah punya bobot AHP lengkap.
     */
    protected function getGlobalWeights(): array
    {
        $subCriteriaList = SubCriteria::whereNotNull('local_weight')
            ->with(['criteria' => fn ($q) => $q->with('latestWeight')])
            ->get();

        $weights = [];
        foreach ($subCriteriaList as $sub) {
            $criteriaWeight = $sub->criteria?->latestWeight?->weight;

            if ($criteriaWeight === null) {
                continue; // kriteria utama belum punya bobot, skip
            }

            $weights[$sub->id] = (float) $criteriaWeight * (float) $sub->local_weight;
        }

        return $weights;
    }
}