<?php

namespace App\Services;

class AhpService
{
    /**
     * Random Index (RI) baku Saaty, berdasarkan jumlah item (n).
     */
    protected array $randomIndex = [
        1 => 0, 2 => 0, 3 => 0.58, 4 => 0.90, 5 => 1.12,
        6 => 1.24, 7 => 1.32, 8 => 1.41, 9 => 1.45, 10 => 1.49,
    ];

    /**
     * Hitung bobot AHP dari matriks perbandingan berpasangan.
     *
     * @param  array  $matrix  Matriks n x n, contoh: [[1, 0.5, 2], [2, 1, 3], [0.5, 0.333, 1]]
     * @param  array  $labels  Label tiap item, urutannya harus sama dengan baris/kolom matriks
     * @return array{weights: array, lambda_max: float, ci: float, cr: float, is_consistent: bool}
     */
    public function calculate(array $matrix, array $labels): array
    {
        $n = count($matrix);

        // 1. Jumlah tiap kolom
        $columnSums = array_fill(0, $n, 0);
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                $columnSums[$j] += $matrix[$i][$j];
            }
        }

        // 2. Normalisasi matriks (tiap sel dibagi jumlah kolomnya)
        $normalized = [];
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                $normalized[$i][$j] = $matrix[$i][$j] / $columnSums[$j];
            }
        }

        // 3. Bobot (priority vector) = rata-rata tiap baris matriks ternormalisasi
        $weights = [];
        for ($i = 0; $i < $n; $i++) {
            $weights[$i] = array_sum($normalized[$i]) / $n;
        }

        // 4. Hitung lambda max: (A . w) dibagi w, lalu dirata-rata
        $weightedSumVector = [];
        for ($i = 0; $i < $n; $i++) {
            $sum = 0;
            for ($j = 0; $j < $n; $j++) {
                $sum += $matrix[$i][$j] * $weights[$j];
            }
            $weightedSumVector[$i] = $sum;
        }

        $lambdaMax = 0;
        for ($i = 0; $i < $n; $i++) {
            $lambdaMax += $weightedSumVector[$i] / $weights[$i];
        }
        $lambdaMax /= $n;

        // 5. Consistency Index (CI) dan Consistency Ratio (CR)
        $ci = ($lambdaMax - $n) / ($n - 1 == 0 ? 1 : $n - 1);
        $ri = $this->randomIndex[$n] ?? 1.49;
        $cr = $ri == 0 ? 0 : $ci / $ri;

        // 6. Susun hasil bobot berdasarkan label
        $weightsByLabel = [];
        foreach ($labels as $index => $label) {
            $weightsByLabel[$label] = round($weights[$index], 4);
        }

        return [
            'weights'       => $weightsByLabel,
            'lambda_max'    => round($lambdaMax, 4),
            'ci'            => round($ci, 4),
            'cr'            => round($cr, 4),
            'is_consistent' => $cr <= 0.1,
        ];
    }
}