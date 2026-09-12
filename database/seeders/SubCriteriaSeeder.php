<?php

namespace Database\Seeders;

use App\Models\Criteria;
use App\Models\SubCriteria;
use Illuminate\Database\Seeder;

class SubCriteriaSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'minat' => [
                ['code' => 'realistic', 'name' => 'Realistic'],
                ['code' => 'investigative', 'name' => 'Investigative'],
                ['code' => 'artistic', 'name' => 'Artistic'],
                ['code' => 'social', 'name' => 'Social'],
                ['code' => 'enterprising', 'name' => 'Enterprising'],
                ['code' => 'conventional', 'name' => 'Conventional'],
            ],
            'bakat' => [
                ['code' => 'verbal', 'name' => 'Verbal'],
                ['code' => 'numerik', 'name' => 'Numerik'],
                ['code' => 'spasial', 'name' => 'Spasial'],
                ['code' => 'penalaran_abstrak', 'name' => 'Penalaran Abstrak'],
                ['code' => 'klerikal', 'name' => 'Kecepatan & Ketelitian Klerikal'],
                ['code' => 'mekanikal', 'name' => 'Mekanikal'],
            ],
            'kepribadian' => [
                ['code' => 'openness', 'name' => 'Openness'],
                ['code' => 'conscientiousness', 'name' => 'Conscientiousness'],
                ['code' => 'extraversion', 'name' => 'Extraversion'],
                ['code' => 'agreeableness', 'name' => 'Agreeableness'],
                ['code' => 'stabilitas_emosi', 'name' => 'Stabilitas Emosi'],
            ],
        ];

        foreach ($data as $criteriaCode => $subs) {
            $criteria = Criteria::where('code', $criteriaCode)->first();

            if (! $criteria) {
                $this->command->warn("Kriteria '{$criteriaCode}' belum ada, jalankan CriteriaSeeder dulu.");
                continue;
            }

            foreach ($subs as $index => $sub) {
                SubCriteria::firstOrCreate(
                    ['code' => $sub['code']],
                    [
                        'criteria_id' => $criteria->id,
                        'name'        => $sub['name'],
                        'order'       => $index + 1,
                    ]
                );
            }
        }

        $this->command->info('Data sub-kriteria berhasil ditambahkan.');
    }
}