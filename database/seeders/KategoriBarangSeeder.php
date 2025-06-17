<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class KategoriBarangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('kategori_barang')->insert([
            ['nama_kategori' => 'Makanan Ringan'],
            ['nama_kategori' => 'Minuman'],
            ['nama_kategori' => 'Kebutuhan Harian'],
        ]);
    }
}
