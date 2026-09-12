<?php

namespace Database\Seeders;

use App\Models\Criteria;
use App\Models\CriteriaWeight;
use App\Models\SubCriteria;
use App\Models\User;
use App\Services\AhpService;
use Illuminate\Database\Seeder;

class AhpDataSeeder extends Seeder
{
    public function run(): void
    {
        $ahpService = new AhpService();
        $admin = User::role('admin')->first();

        if (! $admin) {
            $this->command->warn('User admin belum ada, jalankan UsersTableSeeder dulu.');
            return;
        }

        // ================= KRITERIA UTAMA =================
        // Urutan prioritas: Minat > Bakat > Kepribadian
        $this->processGroup(
            ahpService: $ahpService,
            order: ['minat', 'bakat', 'kepribadian'],
            onResult: function ($result) use ($admin) {
                foreach ($result['weights'] as $code => $weight) {
                    $criteria = Criteria::where('code', $code)->first();
                    if (! $criteria) continue;

                    CriteriaWeight::create([
                        'criteria_id' => $criteria->id,
                        'weight'      => $weight,
                        'cr_value'    => $result['cr'],
                        'note'        => 'Diisi otomatis via AhpDataSeeder.',
                        'set_by'      => $admin->id,
                    ]);
                }
                $this->command->info("Kriteria utama: CR = {$result['cr']} (konsisten)");
            }
        );

        // ================= SUB-KRITERIA MINAT (RIASEC) =================
        // Urutan prioritas: Investigative > Realistic > Enterprising > Conventional > Social > Artistic
        $this->processGroup(
            ahpService: $ahpService,
            order: ['investigative', 'realistic', 'enterprising', 'conventional', 'social', 'artistic'],
            onResult: function ($result) {
                $this->saveSubCriteriaWeights($result);
                $this->command->info("Sub-kriteria Minat: CR = {$result['cr']} (konsisten)");
            }
        );

        // ================= SUB-KRITERIA BAKAT (DAT) =================
        // Urutan prioritas: Numerik > Verbal > Penalaran Abstrak > Spasial > Klerikal > Mekanikal
        $this->processGroup(
            ahpService: $ahpService,
            order: ['numerik', 'verbal', 'penalaran_abstrak', 'spasial', 'klerikal', 'mekanikal'],
            onResult: function ($result) {
                $this->saveSubCriteriaWeights($result);
                $this->command->info("Sub-kriteria Bakat: CR = {$result['cr']} (konsisten)");
            }
        );

        // ================= SUB-KRITERIA KEPRIBADIAN (Big Five) =================
        // Urutan prioritas: Conscientiousness > Stabilitas Emosi > Openness > Extraversion > Agreeableness
        $this->processGroup(
            ahpService: $ahpService,
            order: ['conscientiousness', 'stabilitas_emosi', 'openness', 'extraversion', 'agreeableness'],
            onResult: function ($result) {
                $this->saveSubCriteriaWeights($result);
                $this->command->info("Sub-kriteria Kepribadian: CR = {$result['cr']} (konsisten)");
            }
        );

        $this->command->info('Semua bobot AHP berhasil dihitung dan disimpan.');
    }

    /**
     * Bangun matriks perbandingan berpasangan berdasarkan urutan prioritas (rank),
     * lalu hitung bobotnya lewat AhpService. Skala mengikuti selisih peringkat:
     * selisih 1 = 3, selisih 2 = 5, selisih 3 = 7, selisih 4+ = 9.
     */
    protected function processGroup(AhpService $ahpService, array $order, \Closure $onResult): void
    {
        $n = count($order);

        $matrix = array_fill(0, $n, array_fill(0, $n, 1));

        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                $diff = $j - $i;
                $value = match (true) {
                    $diff === 1 => 3,
                    $diff === 2 => 5,
                    $diff === 3 => 7,
                    default     => 9,
                };
                $matrix[$i][$j] = $value;
                $matrix[$j][$i] = 1 / $value;
            }
        }

        $result = $ahpService->calculate($matrix, $order);

        if (! $result['is_consistent']) {
            $this->command->error('Kombinasi urutan untuk ['.implode(', ', $order).'] TIDAK KONSISTEN (CR = '.$result['cr'].'). Seeder dibatalkan untuk grup ini.');
            return;
        }

        $onResult($result);
    }

    protected function saveSubCriteriaWeights(array $result): void
    {
        foreach ($result['weights'] as $code => $weight) {
            SubCriteria::where('code', $code)->update(['local_weight' => $weight]);
        }
    }
}