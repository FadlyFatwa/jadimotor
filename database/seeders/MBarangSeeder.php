<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MBarangSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('m_barangs')->insert([
            [
                'kode_barang' => 'BRG001',
                'nama_barang' => 'Kemeja Pria',
                'id_kategori' => 1, // pastikan kategori ID 1 ada
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'kode_barang' => 'BRG002',
                'nama_barang' => 'Celana Jeans',
                'id_kategori' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
