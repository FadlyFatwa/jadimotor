<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supplier;

class SupplierSeeder extends Seeder
{
    public function run()
    {
        $suppliers = [
            [
                'kode_supplier' => 'SUP001',
                'nama_supplier' => 'PT Maju Jaya',
                'no_telp' => '081234567890',
                'alamat' => 'Jl. Raya No. 1, Jakarta',
            ],
            [
                'kode_supplier' => 'SUP002',
                'nama_supplier' => 'CV Berkah Abadi',
                'no_telp' => '085678901234',
                'alamat' => 'Jl. Sudirman No. 10, Bandung',
            ],
            [
                'kode_supplier' => 'SUP003',
                'nama_supplier' => 'Toko Sejahtera',
                'no_telp' => '087890123456',
                'alamat' => 'Jl. Merdeka No. 5, Surabaya',
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }
    }
}
