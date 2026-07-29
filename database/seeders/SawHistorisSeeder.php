<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Supplier;
use App\Models\SawNilaiHistoris;

/**
 * Data historis performa supplier untuk kalkulasi SAW.
 *
 * Kriteria:
 *   C2  Termin Pembayaran   benefit  (hari — semakin panjang semakin baik bagi buyer)
 *   C3  Lead Time           cost     (hari — semakin pendek semakin baik)
 *   C4  Akurasi Kuantitas   benefit  (% — kesesuaian qty terima vs pesanan)
 *   C5  Tingkat Pemenuhan   benefit  (% — pesanan terpenuhi penuh)
 *   C6  Komunikasi          benefit  (skala 1–5)
 *
 * Profil 9 supplier (3 per tier):
 *   OEM  : GNP (Toyota), BKA (Honda), TBN (Yamaha)  → kualitas tinggi, termin lebih panjang
 *   Orig : MJY, NMT, TSJ                             → performa menengah, harga kompetitif
 *   KW   : IDX, SBR, ABS                             → harga murah, reliabilitas lebih rendah
 */
class SawHistorisSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('saw_nilai_historis')->truncate();

        $sup = fn(string $kode) => Supplier::where('kode_supplier', $kode)->value('id_supplier');

        // Periode historis: tahun lalu (Jan–Des 2025)
        $dari  = '2025-01-01';
        $sampai = '2025-12-31';

        $data = [
            // ── OEM suppliers ─────────────────────────────────────────────
            // GNP: performa terbaik, termin panjang, lead time sedang
            'GNP' => [
                'termin_pembayaran' => 30,
                'lead_time'         => 5,
                'akurasi_kuantitas' => 98.5,
                'tingkat_pemenuhan' => 99.0,
                'komunikasi'        => 4.5,
                'jumlah_transaksi'  => 24,
                'catatan'           => 'Supplier OEM resmi Toyota. Historis sangat konsisten.',
            ],
            // BKA: performa tinggi, spesialis Honda
            'BKA' => [
                'termin_pembayaran' => 30,
                'lead_time'         => 5,
                'akurasi_kuantitas' => 97.8,
                'tingkat_pemenuhan' => 98.5,
                'komunikasi'        => 4.5,
                'jumlah_transaksi'  => 20,
                'catatan'           => 'Supplier OEM resmi Honda. Responsif dan terorganisir.',
            ],
            // TBN: performa tinggi, spesialis Yamaha, termin lebih pendek
            'TBN' => [
                'termin_pembayaran' => 14,
                'lead_time'         => 4,
                'akurasi_kuantitas' => 96.5,
                'tingkat_pemenuhan' => 97.0,
                'komunikasi'        => 4.0,
                'jumlah_transaksi'  => 18,
                'catatan'           => 'Supplier OEM Yamaha. Lead time singkat, termin terbatas.',
            ],

            // ── Original (Aftermarket) suppliers ─────────────────────────
            // MJY: distributor besar, termin terpanjang, performa stabil
            'MJY' => [
                'termin_pembayaran' => 45,
                'lead_time'         => 3,
                'akurasi_kuantitas' => 95.0,
                'tingkat_pemenuhan' => 96.0,
                'komunikasi'        => 4.5,
                'jumlah_transaksi'  => 38,
                'catatan'           => 'Distributor utama. Termin terpanjang, stok selalu tersedia.',
            ],
            // NMT: performa baik, lead time cepat
            'NMT' => [
                'termin_pembayaran' => 30,
                'lead_time'         => 3,
                'akurasi_kuantitas' => 94.0,
                'tingkat_pemenuhan' => 95.0,
                'komunikasi'        => 4.0,
                'jumlah_transaksi'  => 28,
                'catatan'           => 'Distributor regional. Pengiriman cepat dan tepat waktu.',
            ],
            // TSJ: harga kompetitif, lead time cepat, termin pendek
            'TSJ' => [
                'termin_pembayaran' => 21,
                'lead_time'         => 2,
                'akurasi_kuantitas' => 92.0,
                'tingkat_pemenuhan' => 93.5,
                'komunikasi'        => 3.8,
                'jumlah_transaksi'  => 32,
                'catatan'           => 'Toko lokal aktif. Harga bersaing, pengiriman sangat cepat.',
            ],

            // ── KW / Economy suppliers ────────────────────────────────────
            // IDX: performa menengah-bawah, lead time cukup cepat
            'IDX' => [
                'termin_pembayaran' => 14,
                'lead_time'         => 3,
                'akurasi_kuantitas' => 90.0,
                'tingkat_pemenuhan' => 91.0,
                'komunikasi'        => 3.5,
                'jumlah_transaksi'  => 16,
                'catatan'           => 'Pemasok ekonomis. Sesekali ada ketidaksesuaian qty minor.',
            ],
            // SBR: performa lebih rendah, pengiriman cepat tapi sering kurang lengkap
            'SBR' => [
                'termin_pembayaran' => 7,
                'lead_time'         => 2,
                'akurasi_kuantitas' => 85.0,
                'tingkat_pemenuhan' => 87.0,
                'komunikasi'        => 3.0,
                'jumlah_transaksi'  => 22,
                'catatan'           => 'Harga sangat murah. Perlu konfirmasi stok sebelum order.',
            ],
            // ABS: performa terendah, harga paling murah
            'ABS' => [
                'termin_pembayaran' => 7,
                'lead_time'         => 2,
                'akurasi_kuantitas' => 83.0,
                'tingkat_pemenuhan' => 85.0,
                'komunikasi'        => 2.8,
                'jumlah_transaksi'  => 15,
                'catatan'           => 'Supplier ekonomis baru. Harga kompetitif tapi konsistensi masih perlu dimonitor.',
            ],
        ];

        foreach ($data as $kode => $row) {
            $supplierId = $sup($kode);
            if (!$supplierId) {
                $this->command->warn("Supplier $kode tidak ditemukan, skip.");
                continue;
            }

            SawNilaiHistoris::create([
                'supplier_id'              => $supplierId,
                'periode_mulai'            => $dari,
                'periode_akhir'            => $sampai,
                'termin_pembayaran'        => $row['termin_pembayaran'],
                'lead_time'                => $row['lead_time'],
                'lead_time_manual'         => $row['lead_time'],
                'akurasi_kuantitas'        => $row['akurasi_kuantitas'],
                'akurasi_kuantitas_manual' => $row['akurasi_kuantitas'],
                'tingkat_pemenuhan'        => $row['tingkat_pemenuhan'],
                'tingkat_pemenuhan_manual' => $row['tingkat_pemenuhan'],
                'komunikasi'               => $row['komunikasi'],
                'jumlah_transaksi'         => $row['jumlah_transaksi'],
                'jumlah_transaksi_manual'  => $row['jumlah_transaksi'],
                'catatan'                  => $row['catatan'],
            ]);
        }

        $this->command->info('✓ SawHistorisSeeder: '.count($data).' supplier dengan data historis.');
    }
}
