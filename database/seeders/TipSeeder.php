<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table("tips")->insert([
            [
                'title' => 'Cara menyimpan uang yang baik',
                'thumbnail' => 'nabung.jpg',
                'url' => 'https://bankmandiri.co.id/nabung-untung-2023',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'title' => 'Cara berinvestasi emas',
                'thumbnail' => 'emas.jpeg',
                'url' => 'https://www.cimbniaga.co.id/id/inspirasi/perencanaan/5-cara-investasi-emas-yang-mudah-bagi-pemula',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'title' => 'Cara nabung',
                'thumbnail' => 'saham.jpeg',
                'url' => 'https://www.cimbniaga.co.id/id/inspirasi/investasi/6-cara-main-saham-pemula-yang-baik-dan-bijak',
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);
    }
}
