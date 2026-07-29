<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Kategori;

class KategoriSeeder extends Seeder
{
    public function run()
    {
        $kategoris = [
            [
                'kode_kategori' => 'KAT001',
                'nama_kategori' => 'Elektronik',
            ],
            [
                'kode_kategori' => 'KAT002',
                'nama_kategori' => 'Makanan',
            ],
            [
                'kode_kategori' => 'KAT003',
                'nama_kategori' => 'Minuman',
            ],
        ];

        foreach ($kategoris as $kategori) {
            Kategori::create($kategori);
        }
    }
}