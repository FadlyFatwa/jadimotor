<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SawKriteriaSeeder extends Seeder
{
    public function run(): void
    {
        // Hapus data lama agar idempotent
        DB::table('saw_kriteria')->truncate();

        DB::table('saw_kriteria')->insert([
            [
                'kode'      => 'C1',
                'nama'      => 'Total Biaya (Harga Penawaran)',
                'jenis'     => 'cost',
                'bobot'     => 0.2500,
                'satuan'    => 'Rp',
                'is_active' => 1,
                'urutan'    => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'kode'      => 'C2',
                'nama'      => 'Termin Pembayaran',
                'jenis'     => 'benefit',
                'bobot'     => 0.1500,
                'satuan'    => 'Hari',
                'is_active' => 1,
                'urutan'    => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'kode'      => 'C3',
                'nama'      => 'Lead Time',
                'jenis'     => 'cost',
                'bobot'     => 0.2000,
                'satuan'    => 'Hari',
                'is_active' => 1,
                'urutan'    => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'kode'      => 'C4',
                'nama'      => 'Akurasi Kuantitas',
                'jenis'     => 'benefit',
                'bobot'     => 0.1500,
                'satuan'    => '%',
                'is_active' => 1,
                'urutan'    => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'kode'      => 'C5',
                'nama'      => 'Tingkat Pemenuhan',
                'jenis'     => 'benefit',
                'bobot'     => 0.1500,
                'satuan'    => '%',
                'is_active' => 1,
                'urutan'    => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'kode'      => 'C6',
                'nama'      => 'Komunikasi',
                'jenis'     => 'benefit',
                'bobot'     => 0.1000,
                'satuan'    => 'Skala 1-5',
                'is_active' => 1,
                'urutan'    => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
