<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Unit;

class UnitSeeder extends Seeder
{
    public function run()
    {
        $units = [
            [
                'kode_unit' => 'UNT001',
                'nama_unit' => 'PCS',
            ],
            [
                'kode_unit' => 'UNT002',
                'nama_unit' => 'KG',
            ],
            [
                'kode_unit' => 'UNT003',
                'nama_unit' => 'LITER',
            ],
        ];

        foreach ($units as $unit) {
            Unit::create($unit);
        }
    }
}
