<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupplierBarangSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('supplier_barang')->insert([
            [
                'id_variasi' => 1, // pastikan variasi ID 1 ada
                'id_supplier' => 1, // pastikan supplier ID 1 ada
                'harga_list' => 140000,
                'kode_list' => 'LST001',
                'harga_beli' => 130000,
                'kode_beli' => 'BEL001',
                'diskon' => 5.0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_variasi' => 2,
                'id_supplier' => 1,
                'harga_list' => 190000,
                'kode_list' => 'LST002',
                'harga_beli' => 180000,
                'kode_beli' => 'BEL002',
                'diskon' => 2.0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
