<?php

namespace Database\Seeders;

use App\Models\Criteria;
use Illuminate\Database\Seeder;

class CriteriaSeeder extends Seeder
{
    public function run(): void
    {
        $criteria = [
            [
                'code'        => 'minat',
                'name'        => 'Minat',
                'description' => 'Kecenderungan ketertarikan seseorang terhadap suatu bidang aktivitas.',
                'order'       => 1,
            ],
            [
                'code'        => 'bakat',
                'name'        => 'Bakat',
                'description' => 'Kemampuan bawaan seseorang dalam suatu bidang tertentu.',
                'order'       => 2,
            ],
            [
                'code'        => 'kepribadian',
                'name'        => 'Kepribadian',
                'description' => 'Karakteristik pola pikir, perasaan, dan perilaku seseorang.',
                'order'       => 3,
            ],
        ];

        foreach ($criteria as $item) {
            Criteria::firstOrCreate(['code' => $item['code']], $item);
        }

        $this->command->info('Data kriteria utama berhasil ditambahkan.');
    }
}