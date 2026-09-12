<?php

namespace Database\Seeders;

use App\Models\Alternative;
use App\Models\SubCriteria;
use Illuminate\Database\Seeder;

class AlternativeSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'name'        => 'Sains & Teknik',
                'description' => 'Bidang yang menekankan analisis, riset, dan pemecahan masalah teknis — cocok untuk yang suka memahami cara kerja sesuatu secara mendalam.',
                'icon'        => 'flask',
                'life_phase'  => 'umum',
                'scores'      => [
                    'realistic' => 4, 'investigative' => 5, 'artistic' => 2, 'social' => 2, 'enterprising' => 2, 'conventional' => 3,
                    'verbal' => 3, 'numerik' => 5, 'spasial' => 4, 'penalaran_abstrak' => 5, 'klerikal' => 3, 'mekanikal' => 4,
                    'openness' => 4, 'conscientiousness' => 4, 'extraversion' => 2, 'agreeableness' => 3, 'stabilitas_emosi' => 3,
                ],
            ],
            [
                'name'        => 'Seni & Desain',
                'description' => 'Bidang yang mengutamakan kreativitas dan ekspresi visual — cocok untuk yang senang menciptakan sesuatu yang orisinal dan estetis.',
                'icon'        => 'palette',
                'life_phase'  => 'umum',
                'scores'      => [
                    'realistic' => 2, 'investigative' => 3, 'artistic' => 5, 'social' => 3, 'enterprising' => 3, 'conventional' => 2,
                    'verbal' => 3, 'numerik' => 2, 'spasial' => 5, 'penalaran_abstrak' => 3, 'klerikal' => 2, 'mekanikal' => 2,
                    'openness' => 5, 'conscientiousness' => 3, 'extraversion' => 3, 'agreeableness' => 3, 'stabilitas_emosi' => 3,
                ],
            ],
            [
                'name'        => 'Sosial & Pendidikan',
                'description' => 'Bidang yang berfokus pada membantu, mengajar, dan mengembangkan orang lain — cocok untuk yang senang berinteraksi dan berkontribusi ke masyarakat.',
                'icon'        => 'users',
                'life_phase'  => 'umum',
                'scores'      => [
                    'realistic' => 2, 'investigative' => 3, 'artistic' => 3, 'social' => 5, 'enterprising' => 3, 'conventional' => 3,
                    'verbal' => 5, 'numerik' => 2, 'spasial' => 2, 'penalaran_abstrak' => 3, 'klerikal' => 3, 'mekanikal' => 2,
                    'openness' => 4, 'conscientiousness' => 4, 'extraversion' => 4, 'agreeableness' => 5, 'stabilitas_emosi' => 4,
                ],
            ],
            [
                'name'        => 'Bisnis & Kepemimpinan',
                'description' => 'Bidang yang menekankan inisiatif, persuasi, dan pengambilan keputusan — cocok untuk yang senang memimpin dan melihat peluang.',
                'icon'        => 'trending-up',
                'life_phase'  => 'umum',
                'scores'      => [
                    'realistic' => 2, 'investigative' => 3, 'artistic' => 2, 'social' => 4, 'enterprising' => 5, 'conventional' => 3,
                    'verbal' => 4, 'numerik' => 4, 'spasial' => 2, 'penalaran_abstrak' => 4, 'klerikal' => 3, 'mekanikal' => 2,
                    'openness' => 4, 'conscientiousness' => 4, 'extraversion' => 5, 'agreeableness' => 3, 'stabilitas_emosi' => 4,
                ],
            ],
            [
                'name'        => 'Administrasi & Keuangan',
                'description' => 'Bidang yang menekankan ketelitian, keteraturan, dan pengelolaan data atau angka — cocok untuk yang senang bekerja secara sistematis dan akurat.',
                'icon'        => 'clipboard-list',
                'life_phase'  => 'umum',
                'scores'      => [
                    'realistic' => 2, 'investigative' => 3, 'artistic' => 2, 'social' => 3, 'enterprising' => 3, 'conventional' => 5,
                    'verbal' => 3, 'numerik' => 5, 'spasial' => 2, 'penalaran_abstrak' => 3, 'klerikal' => 5, 'mekanikal' => 2,
                    'openness' => 2, 'conscientiousness' => 5, 'extraversion' => 2, 'agreeableness' => 3, 'stabilitas_emosi' => 4,
                ],
            ],
        ];

        foreach ($data as $item) {
            $alternative = Alternative::firstOrCreate(
                ['name' => $item['name']],
                [
                    'description' => $item['description'],
                    'icon'        => $item['icon'],
                    'life_phase'  => $item['life_phase'],
                ]
            );

            foreach ($item['scores'] as $subCode => $idealScore) {
                $subCriteria = SubCriteria::where('code', $subCode)->first();

                if (! $subCriteria) {
                    $this->command->warn("Sub-kriteria '{$subCode}' tidak ditemukan, lewati.");
                    continue;
                }

                $alternative->profiles()->updateOrCreate(
                    ['sub_criteria_id' => $subCriteria->id],
                    ['ideal_score' => $idealScore]
                );
            }

            $this->command->info("Alternatif \"{$item['name']}\" berhasil diisi ({$alternative->profiles()->count()}/17 profil ideal).");
        }

        $this->command->info('Semua data alternatif berhasil ditambahkan.');
    }
}