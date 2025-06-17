<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class BarangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('barang')->insert([
            [
                'nama_barang' => 'Keripik Singkong',
                'jenis_barang' => 'Keripik',
                'harga' => 5000,
                'stok' => 100,
                'id_kategori' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_barang' => 'Teh Botol Sosro',
                'jenis_barang' => 'Teh',
                'harga' => 3000,
                'stok' => 200,
                'id_kategori' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_barang' => 'Sabun Giv',
                'jenis_barang' => 'Sabun',
                'harga' => 15000,
                'stok' => 50,
                'id_kategori' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
