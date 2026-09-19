<?php

namespace App\Services;

use App\Models\Alternative;
use App\Models\SubCriteria;
use App\Models\TestSession;

class TopsisService
{

    public function calculate(TestSession $testSession): array
    {
        // 1. Skor rata-rata user per sub-kriteria
        $userScores = $this->getUserScoresPerSubCriteria($testSession);

        // Bobot global tiap sub-kriteria (kriteria utama x lokal)
        $globalWeights = $this->getGlobalWeights();

        // Hanya proses sub-kriteria yang punya bobot valid DAN ada jawaban user
        $subCriteriaIds = array_keys(array_intersect_key($globalWeights, $userScores));

        if (empty($subCriteriaIds)) {
            throw new \RuntimeException('Tidak ada data bobot atau jawaban yang cukup untuk menghitung TOPSIS.');
        }

        // Alternatif yang punya profil ideal lengkap untuk sub-kriteria yang dipakai
        $alternatives = Alternative::where('is_active', true)
            ->with(['profiles' => fn ($q) => $q->whereIn('sub_criteria_id', $subCriteriaIds)])
            ->get()
            ->filter(fn ($alt) => $alt->profiles->count() === count($subCriteriaIds));

        if ($alternatives->isEmpty()) {
            throw new \RuntimeException('Tidak ada alternatif dengan profil ideal lengkap untuk dihitung.');
        }

        // 2 & 3. Bangun matriks keputusan (match score) per alternatif per sub-kriteria
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

        // 4. Normalisasi (vector normalization) per kolom
        $columnDenominators = [];
        foreach ($subCriteriaIds as $subId) {
            $sumSquares = 0;
            foreach ($decisionMatrix as $row) {
                $sumSquares += $row[$subId] ** 2;
            }
            $columnDenominators[$subId] = sqrt($sumSquares) ?: 1; // hindari bagi nol
        }

        $normalizedMatrix = [];
        foreach ($decisionMatrix as $altId => $row) {
            foreach ($row as $subId => $value) {
                $normalizedMatrix[$altId][$subId] = $value / $columnDenominators[$subId];
            }
        }

        // 5. Kalikan dengan bobot global -> weighted normalized matrix
        $weightedMatrix = [];
        foreach ($normalizedMatrix as $altId => $row) {
            foreach ($row as $subId => $value) {
                $weightedMatrix[$altId][$subId] = $value * $globalWeights[$subId];
            }
        }

        // 6. Solusi ideal positif (A+) dan negatif (A-) per kolom
        $idealPositive = [];
        $idealNegative = [];
        foreach ($subCriteriaIds as $subId) {
            $columnValues = array_column($weightedMatrix, $subId);
            $idealPositive[$subId] = max($columnValues);
            $idealNegative[$subId] = min($columnValues);
        }

        // 7 & 8. Jarak ke solusi ideal dan skor akhir (closeness coefficient)
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
     * Skala jawaban 1-5 langsung dipakai sebagai skor sub-kriteria.
     */
    protected function getUserScoresPerSubCriteria(TestSession $testSession): array
    {
        return $testSession->answers()
            ->join('questions', 'test_answers.question_id', '=', 'questions.id')
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