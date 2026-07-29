<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VariasiSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('variasis')->insert([
            [
                'barcode' => '00001',
                'nama_variasi' => 'Kemeja Pria - L',
                'id_barang' => 1, // pastikan m_barang ID 1 ada
                'id_unit' => 1, // pastikan unit ID 1 ada
                'harga_jual' => 150000,
                'stock' => 10,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'barcode' => '00002',
                'nama_variasi' => 'Celana Jeans - M',
                'id_barang' => 2,
                'id_unit' => 1,
                'harga_jual' => 200000,
                'stock' => 5,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
