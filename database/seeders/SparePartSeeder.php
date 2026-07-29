<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Kategori;
use App\Models\Unit;
use App\Models\Supplier;
use App\Models\MBarang;
use App\Models\Variasi;
use App\Models\SupplierVariasi;
use App\Models\Vehicle;
use App\Models\VehicleGeneration;
use App\Models\ProductVariantCompatibility;

/**
 * Struktur data:
 *   20 master barang × 3 tier × 3 variasi = 180 variasi
 *   Setiap variasi punya 3 supplier → 540 supplier_barang records
 *
 * Barcode: 00001–00180, urutan per master barang:
 *   [OEM-v1, OEM-v2, OEM-v3, Orig-v1, Orig-v2, Orig-v3, KW-v1, KW-v2, KW-v3]
 *
 * Supplier pool (9 supplier, 3 per tier):
 *   OEM  tier → GNP/BKA/TBN sebagai OEM brand + MJY + NMT
 *   Orig tier → MJY + TSJ + IDX
 *   KW   tier → TSJ + SBR + ABS
 *
 * Setiap tier punya 3 supplier-set (A/B/C) rotasi antar variasi agar SAW bervariasi.
 */
class SparePartSeeder extends Seeder
{
    private function kode(int $harga): string
    {
        $map   = ['1'=>'A','2'=>'B','3'=>'C','4'=>'D','5'=>'E','6'=>'F','7'=>'G','8'=>'H','9'=>'I','0'=>'J'];
        $s     = (string) $harga;
        $zeros = strlen($s) - strlen(rtrim($s, '0'));
        $prefix= rtrim($s, '0');
        $kode  = implode('', array_map(fn($d) => $map[$d] ?? '', str_split($prefix)));
        if ($zeros > 0) { $kode .= 'J'; if ($zeros > 1) $kode .= $zeros; }
        return $kode;
    }

    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('product_variant_compatibility')->truncate();
        DB::table('supplier_barang')->truncate();
        DB::table('variasis')->truncate();
        DB::table('m_barangs')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // ── Kategori ──────────────────────────────────────────────────────
        foreach ([
            ['kode_kategori'=>'MSN','nama_kategori'=>'Mesin',              'slug'=>'mesin'],
            ['kode_kategori'=>'SUS','nama_kategori'=>'Suspensi & Setir',   'slug'=>'suspensi-setir'],
            ['kode_kategori'=>'REM','nama_kategori'=>'Sistem Rem',         'slug'=>'sistem-rem'],
            ['kode_kategori'=>'ELK','nama_kategori'=>'Kelistrikan',        'slug'=>'kelistrikan'],
            ['kode_kategori'=>'TRS','nama_kategori'=>'Transmisi & Kopling','slug'=>'transmisi'],
            ['kode_kategori'=>'FLT','nama_kategori'=>'Filter & Fluida',    'slug'=>'filter-fluida'],
            ['kode_kategori'=>'PDN','nama_kategori'=>'Pendingin & AC',     'slug'=>'pendingin-ac'],
            ['kode_kategori'=>'BBM','nama_kategori'=>'Sistem Bahan Bakar', 'slug'=>'bahan-bakar'],
            ['kode_kategori'=>'BDI','nama_kategori'=>'Bodi & Eksterior',   'slug'=>'bodi'],
            ['kode_kategori'=>'BAN','nama_kategori'=>'Ban & Velg',         'slug'=>'ban-velg'],
        ] as $k) Kategori::firstOrCreate(['kode_kategori'=>$k['kode_kategori']], $k);

        // ── Unit ──────────────────────────────────────────────────────────
        foreach ([
            ['kode_unit'=>'PCS',  'nama_unit'=>'Pcs'],
            ['kode_unit'=>'SET',  'nama_unit'=>'Set'],
            ['kode_unit'=>'LITER','nama_unit'=>'Liter'],
            ['kode_unit'=>'PSG',  'nama_unit'=>'Pasang'],
            ['kode_unit'=>'BTL',  'nama_unit'=>'Botol'],
            ['kode_unit'=>'ROLL', 'nama_unit'=>'Roll'],
            ['kode_unit'=>'MTR',  'nama_unit'=>'Meter'],
        ] as $u) Unit::firstOrCreate(['kode_unit'=>$u['kode_unit']], $u);

        // ── 9 Supplier ────────────────────────────────────────────────────
        foreach ([
            ['kode_supplier'=>'GNP','nama_supplier'=>'PT Genuine Parts Indo', 'no_telp'=>'021-5556006','alamat'=>'Kawasan EJIP Bekasi'],
            ['kode_supplier'=>'BKA','nama_supplier'=>'CV Berkah Abadi',       'no_telp'=>'022-5553003','alamat'=>'Jl. Honda Center No.8 Bandung'],
            ['kode_supplier'=>'TBN','nama_supplier'=>'Toko Bintang Motor',    'no_telp'=>'022-5555005','alamat'=>'Jl. Soekarno Hatta No.100 Bandung'],
            ['kode_supplier'=>'MJY','nama_supplier'=>'PT Maju Jaya Auto',     'no_telp'=>'021-5551001','alamat'=>'Jl. Otomotif No.1 Jakarta'],
            ['kode_supplier'=>'NMT','nama_supplier'=>'CV Nusantara Motor',    'no_telp'=>'021-5554004','alamat'=>'Jl. Raya Bekasi KM 12'],
            ['kode_supplier'=>'TSJ','nama_supplier'=>'Toko Sejahtera',        'no_telp'=>'022-5552002','alamat'=>'Jl. Pasar Induk No.5 Bandung'],
            ['kode_supplier'=>'IDX','nama_supplier'=>'PT Indo Parts Exchange','no_telp'=>'021-5557007','alamat'=>'Jl. Mangga Dua Raya No.22 Jakarta'],
            ['kode_supplier'=>'SBR','nama_supplier'=>'CV Sumber Rejeki',      'no_telp'=>'022-5558008','alamat'=>'Jl. Gatot Subroto No.45 Bandung'],
            ['kode_supplier'=>'ABS','nama_supplier'=>'PT Abadi Sukses',       'no_telp'=>'021-5559009','alamat'=>'Jl. Industri Raya Blok C No.7 Tangerang'],
        ] as $s) Supplier::firstOrCreate(['kode_supplier'=>$s['kode_supplier']], $s);

        // ── Kendaraan & generasi ──────────────────────────────────────────
        $genMap = [];
        foreach ([
            ['name'=>'Avanza',    'manufacturer'=>'Toyota','generations'=>[
                ['code'=>'F601','nickname'=>'Avanza Gen 1','start_year'=>2003,'end_year'=>2011],
                ['code'=>'F652','nickname'=>'Avanza Gen 2','start_year'=>2012,'end_year'=>2018],
                ['code'=>'F653','nickname'=>'Avanza New',  'start_year'=>2019,'end_year'=>null],
            ]],
            ['name'=>'Yaris',     'manufacturer'=>'Toyota','generations'=>[
                ['code'=>'XP90', 'nickname'=>'Yaris Gen 1','start_year'=>2006,'end_year'=>2013],
                ['code'=>'XP150','nickname'=>'Yaris Gen 2','start_year'=>2014,'end_year'=>2019],
            ]],
            ['name'=>'Agya',      'manufacturer'=>'Toyota','generations'=>[
                ['code'=>'AG10','nickname'=>'Agya Gen 1','start_year'=>2013,'end_year'=>2016],
                ['code'=>'AG20','nickname'=>'Agya New',  'start_year'=>2017,'end_year'=>null],
            ]],
            ['name'=>'Jazz',      'manufacturer'=>'Honda','generations'=>[
                ['code'=>'GD3','nickname'=>'Jazz Old','start_year'=>2004,'end_year'=>2007],
                ['code'=>'GE8','nickname'=>'Jazz RS', 'start_year'=>2008,'end_year'=>2013],
            ]],
            ['name'=>'City',      'manufacturer'=>'Honda','generations'=>[
                ['code'=>'GM2','nickname'=>'City Lama','start_year'=>2008,'end_year'=>2013],
                ['code'=>'GM6','nickname'=>'City Baru','start_year'=>2014,'end_year'=>2017],
            ]],
            ['name'=>'Brio',      'manufacturer'=>'Honda','generations'=>[
                ['code'=>'BDD', 'nickname'=>'Brio Satya','start_year'=>2012,'end_year'=>2016],
                ['code'=>'BDD2','nickname'=>'Brio New',  'start_year'=>2017,'end_year'=>null],
            ]],
            ['name'=>'NMAX',      'manufacturer'=>'Yamaha','generations'=>[
                ['code'=>'NM155G1','nickname'=>'NMAX Non ABS','start_year'=>2015,'end_year'=>2019],
                ['code'=>'NM155G2','nickname'=>'NMAX ABS',    'start_year'=>2020,'end_year'=>null],
            ]],
            ['name'=>'Aerox',     'manufacturer'=>'Yamaha','generations'=>[
                ['code'=>'AX155G1','nickname'=>'Aerox 155','start_year'=>2016,'end_year'=>2021],
                ['code'=>'AX155G2','nickname'=>'Aerox New', 'start_year'=>2022,'end_year'=>null],
            ]],
            ['name'=>'Vario 150', 'manufacturer'=>'Honda','generations'=>[
                ['code'=>'VR150K59','nickname'=>'Vario 150 eSP','start_year'=>2015,'end_year'=>2020],
                ['code'=>'VR150K9A','nickname'=>'Vario 150 New','start_year'=>2021,'end_year'=>null],
            ]],
        ] as $vData) {
            $vehicle = Vehicle::firstOrCreate(
                ['name'=>$vData['name'],'manufacturer'=>$vData['manufacturer']],
                ['name'=>$vData['name'],'manufacturer'=>$vData['manufacturer']]
            );
            foreach ($vData['generations'] as $g) {
                $gen = VehicleGeneration::firstOrCreate(
                    ['vehicle_id'=>$vehicle->id,'code'=>$g['code']],
                    array_merge($g, ['vehicle_id'=>$vehicle->id])
                );
                $genMap[$vData['name'].'|'.$g['code']] = $gen->id;
            }
        }
        $GLOBALS['genMapRef'] = $genMap;

        // ── Helper: simpan 1 variasi ──────────────────────────────────────
        $barcode = 1;
        $cat  = fn($k) => Kategori::where('kode_kategori',$k)->value('id_kategori');
        $unit = fn($k) => Unit::where('kode_unit',$k)->value('id_unit');
        $sup  = fn($k) => Supplier::where('kode_supplier',$k)->value('id_supplier');

        $addVariasi = function(
            int $idBarang, string $namav, string $tier, string $partNo,
            string $unitKode, int $hargaJual, array $suppliers, array $gens
        ) use (&$barcode, $unit, $sup) {
            $v = Variasi::create([
                'barcode'      => str_pad($barcode, 5, '0', STR_PAD_LEFT),
                'id_barang'    => $idBarang,
                'nama_variasi' => $namav,
                'id_unit'      => $unit($unitKode),
                'harga_jual'   => $hargaJual,
                'stock'        => rand(5, 50),
                'part_number'  => $partNo,
                'tier'         => $tier,
                'is_active'    => true,
                'status'       => 'active',
            ]);
            $barcode++;
            foreach ($suppliers as $sp) {
                SupplierVariasi::create([
                    'id_variasi'  => $v->id_variasi,
                    'id_supplier' => $sup($sp['kode']),
                    'harga_beli'  => $sp['beli'],
                    'harga_list'  => $sp['list'],
                    'kode_beli'   => $this->kode($sp['beli']),
                    'kode_list'   => $this->kode($sp['list']),
                    'diskon'      => 0,
                ]);
            }
            foreach ($gens as [$vName, $code]) {
                $genId = $GLOBALS['genMapRef'][$vName.'|'.$code] ?? null;
                if ($genId) ProductVariantCompatibility::create([
                    'id_variasi' => $v->id_variasi, 'vehicle_generation_id' => $genId, 'is_compatible' => true,
                ]);
            }
            return $v;
        };

        // ── 3 set supplier per tier (A/B/C rotasi antar variasi), MASING² 3 supplier ──
        // OEM  → {oemBrand, MJY, NMT} | {MJY, NMT, IDX} | {oemBrand, TSJ, IDX}
        // Orig → {MJY, TSJ, IDX}      | {TSJ, IDX, NMT}  | {MJY, IDX, SBR}
        // KW   → {TSJ, SBR, ABS}      | {SBR, ABS, IDX}  | {TSJ, ABS, NMT}
        // 3 supplier per variasi (multi-supplier) — tiap supplier beda harga beli/list
        // agar perbandingan & rekomendasi SAW punya variasi nyata untuk diuji.

        // Susun daftar kode supplier unik: $primary diutamakan, lalu fallback
        // (skip kalau sudah ada di set, mis. $oemKode kebetulan = salah satu fallback).
        $pickSet = function (?string $primary, array $fallback): array {
            $set = $primary ? [$primary] : [];
            foreach ($fallback as $kode) {
                if (count($set) >= 3) break;
                if (!in_array($kode, $set, true)) $set[] = $kode;
            }
            return $set;
        };

        // Ubah daftar kode supplier → array data supplier_barang dgn variasi harga
        // (supplier ke-2 & ke-3 sedikit lebih mahal drpd supplier ke-1, mensimulasikan
        // kondisi nyata: harga berbeda antar pemasok untuk part yang sama).
        $mkSuppliers = function (array $kodes, int $base, float $listMul): array {
            $out = [];
            foreach (array_values($kodes) as $i => $kode) {
                $factor  = 1 + $i * 0.04; // 1.00, 1.04, 1.08
                $out[] = [
                    'kode' => $kode,
                    'beli' => (int) round($base * $factor),
                    'list' => (int) round($base * $factor * $listMul),
                ];
            }
            return $out;
        };

        $oemA  = fn(string $o, int $b) => $mkSuppliers($pickSet($o, ['MJY','NMT','IDX']), $b, 1.23);
        $oemB  = fn(string $o, int $b) => $mkSuppliers(['MJY','NMT','IDX'],               $b, 1.26);
        $oemC  = fn(string $o, int $b) => $mkSuppliers($pickSet($o, ['TSJ','IDX','NMT']), $b, 1.29);
        $origA = fn(int $b)            => $mkSuppliers(['MJY','TSJ','IDX'],               $b, 1.30);
        $origB = fn(int $b)            => $mkSuppliers(['TSJ','IDX','NMT'],               $b, 1.34);
        $origC = fn(int $b)            => $mkSuppliers(['MJY','IDX','SBR'],               $b, 1.38);
        $kwA   = fn(int $b)            => $mkSuppliers(['TSJ','SBR','ABS'],               $b, 1.40);
        $kwB   = fn(int $b)            => $mkSuppliers(['SBR','ABS','IDX'],               $b, 1.45);
        $kwC   = fn(int $b)            => $mkSuppliers(['TSJ','ABS','NMT'],               $b, 1.50);

        // Helper: tambah 3 variasi per tier sekaligus (A→B→C supplier set)
        $addTier = function(
            int $idBarang, string $unitKode, string $tier, string $oemKode,
            array $variants, array $veh
        ) use ($addVariasi, $oemA,$oemB,$oemC,$origA,$origB,$origC,$kwA,$kwB,$kwC) {
            $sets = [
                'OEM'      => [fn($b)=>$oemA($oemKode,$b), fn($b)=>$oemB($oemKode,$b), fn($b)=>$oemC($oemKode,$b)],
                'Original' => [fn($b)=>$origA($b),         fn($b)=>$origB($b),          fn($b)=>$origC($b)],
                'KW'       => [fn($b)=>$kwA($b),           fn($b)=>$kwB($b),             fn($b)=>$kwC($b)],
            ];
            $tierSets = $sets[$tier] ?? $sets['KW'];
            foreach ($variants as $i => [$brand, $partno, $jual, $beli]) {
                $addVariasi($idBarang, $brand, $tier, $partno, $unitKode, $jual, $tierSets[$i % 3]($beli), $veh);
            }
        };

        // ── Kelompok kendaraan ────────────────────────────────────────────
        $vA = [['Avanza','F601'],['Avanza','F652'],['Agya','AG10']];           // Toyota kompak
        $vB = [['Jazz','GD3'],['Jazz','GE8'],['City','GM2']];                   // Honda kompak
        $vC = [['NMAX','NM155G1'],['NMAX','NM155G2'],['Aerox','AX155G1']];     // Yamaha matic
        $vD = [['Avanza','F601'],['Avanza','F652']];                            // Avanza only
        $vE = [['Avanza','F601'],['Jazz','GD3'],['Brio','BDD']];                // Mixed (lampu)
        $vF = [['Avanza','F601'],['Jazz','GE8'],['City','GM2']];                // Mixed (aki)
        $vH = [['Avanza','F652'],['Avanza','F653'],['Yaris','XP150']];          // Toyota terbaru

        // ================================================================
        // 20 MASTER BARANG — masing-masing 3 tier × 3 variasi = 9 variasi
        // ================================================================

        // ── MB001: Oli Mesin 10W-40 4L (Toyota) ─── barcode 00001–00009 ──
        $mb = MBarang::create(['kode_barang'=>'MB001','nama_barang'=>'Oli Mesin 10W-40 4L (Toyota)','id_kategori'=>$cat('MSN'),'description'=>'Pelumas mesin 10W-40 Toyota Avanza & Agya','is_active'=>true]);
        $addTier($mb->id_barang,'LITER','OEM','GNP',[
            ['Toyota Genuine 10W-40','OE-08880-10705',185000,130000],
            ['Aisin 10W-40',         'AIS-OIL-10W40', 178000,125000],
            ['Denso Pure Oil 10W-40','DNS-OIL-10W40', 192000,135000],
        ],$vA);
        $addTier($mb->id_barang,'LITER','Original','GNP',[
            ['Castrol GTX 10W-40',   'CST-10W40-4L',  145000, 90000],
            ['Total Quartz 10W-40',  'TQL-10W40-4L',  138000, 86000],
            ['Shell Helix HX5 10W-40','SHL-HX5-4L',   152000, 95000],
        ],$vA);
        $addTier($mb->id_barang,'LITER','KW','GNP',[
            ['Evro 10W-40',          'EVR-10W40-4L',   72000, 42000],
            ['Federal Oil 10W-40',   'FED-10W40-4L',   68000, 39000],
            ['Prestone 10W-40',      'PRS-10W40-4L',   76000, 44000],
        ],$vA);

        // ── MB002: Filter Oli (Toyota) ─────────────── barcode 00010–00018 ─
        $mb = MBarang::create(['kode_barang'=>'MB002','nama_barang'=>'Filter Oli (Toyota)','id_kategori'=>$cat('FLT'),'description'=>'Filter oli mesin Toyota Avanza & Agya','is_active'=>true]);
        $addTier($mb->id_barang,'PCS','OEM','GNP',[
            ['Toyota Genuine',   '90915-YZZD2',  85000,55000],
            ['Denso DO-002',     'DNS-DO-002',   80000,52000],
            ['Aisin FO-001',     'AIS-FO-001',   88000,58000],
        ],$vA);
        $addTier($mb->id_barang,'PCS','Original','GNP',[
            ['Sakura C-1803',    'SKR-C1803',    35000,18000],
            ['Wix WL7171',       'WIX-WL7171',   33000,17000],
            ['Purflux LS168',    'PRF-LS168',    37000,19000],
        ],$vA);
        $addTier($mb->id_barang,'PCS','KW','GNP',[
            ['GS Filter FO-12',  'GS-FO1234',    18000, 9500],
            ['Quantum QFO-12',   'QNT-QFO12',    17000, 9000],
            ['SAS FO-001',       'SAS-FO001',    19000,10000],
        ],$vA);

        // ── MB003: Kampas Rem Depan (Toyota) ────────── barcode 00019–00027 ─
        $mb = MBarang::create(['kode_barang'=>'MB003','nama_barang'=>'Kampas Rem Depan (Toyota)','id_kategori'=>$cat('REM'),'description'=>'Brake pad depan Toyota Avanza & Agya','is_active'=>true]);
        $addTier($mb->id_barang,'SET','OEM','GNP',[
            ['Toyota Genuine',   '04465-0D100',  380000,260000],
            ['Akebono AN-756K',  'AKB-AN756K',   362000,248000],
            ['Sumitomo SPD-001', 'SMT-SPD001',   398000,272000],
        ],$vA);
        $addTier($mb->id_barang,'SET','Original','GNP',[
            ['Bendix DB2293',    'BDX-DB2293',   240000,150000],
            ['Brembo P23040',    'BRM-P23040',   228000,142000],
            ['Ferodo FDB1343',   'FRD-FDB1343',  252000,158000],
        ],$vA);
        $addTier($mb->id_barang,'SET','KW','GNP',[
            ['QH BD-3012',       'QH-BD3012',    108000, 60000],
            ['Textar 2346401',   'TXT-2346401',  103000, 57000],
            ['TRW GDB3326',      'TRW-GDB3326',  114000, 63000],
        ],$vA);

        // ── MB004: Busi (Toyota) ─────────────────────── barcode 00028–00036 ─
        $mb = MBarang::create(['kode_barang'=>'MB004','nama_barang'=>'Busi (Toyota)','id_kategori'=>$cat('MSN'),'description'=>'Busi spark plug Toyota Avanza & Agya','is_active'=>true]);
        $addTier($mb->id_barang,'PCS','OEM','GNP',[
            ['Denso T16EPR-U15', 'DNS-T16EPRU15', 42000,28000],
            ['NGK K20PR-U11',    'NGK-K20PRU11',  40000,27000],
            ['Bosch FR78X',      'BSH-FR78X',     45000,30000],
        ],$vA);
        $addTier($mb->id_barang,'PCS','Original','GNP',[
            ['NGK BPR5EGP',      'NGK-BPR5EGP',  28000,17000],
            ['Iridium BPR5EIX',  'NGK-BPR5EIX',  30000,18500],
            ['Bosch SP30',       'BSH-SP30',      26000,16000],
        ],$vA);
        $addTier($mb->id_barang,'PCS','KW','GNP',[
            ['Champion RF7YC',   'CHP-RF7YC',     12000, 6500],
            ['Eyquem RFC52LZ',   'EYQ-RFC52LZ',   11500, 6200],
            ['AC Delco R42XL',   'ACD-R42XL',     12500, 7000],
        ],$vA);

        // ── MB005: Filter Udara (Toyota) ─────────────── barcode 00037–00045 ─
        $mb = MBarang::create(['kode_barang'=>'MB005','nama_barang'=>'Filter Udara (Toyota)','id_kategori'=>$cat('FLT'),'description'=>'Air filter Toyota Avanza & Agya','is_active'=>true]);
        $addTier($mb->id_barang,'PCS','OEM','GNP',[
            ['Toyota Genuine',   '17801-0D040',  110000,72000],
            ['Denso 143-3040',   'DNS-143-3040', 105000,68000],
            ['Aisin AFA-001',    'AIS-AFA001',   115000,76000],
        ],$vA);
        $addTier($mb->id_barang,'PCS','Original','GNP',[
            ['Sakura FA-1602',   'SKR-FA1602',    55000,28000],
            ['K&N 33-2031',      'KN-33-2031',    58000,30000],
            ['Wesfil WA5220',    'WSF-WA5220',    52000,26000],
        ],$vA);
        $addTier($mb->id_barang,'PCS','KW','GNP',[
            ['Uni Filter UF-12', 'UNI-AF1234',    22000,11000],
            ['GoodWill GF-001',  'GWL-GF001',     21000,10000],
            ['SAS AF-001',       'SAS-AF001',     23000,12000],
        ],$vA);

        // ── MB006: Oli Mesin 10W-40 4L (Honda) ───────── barcode 00046–00054 ─
        $mb = MBarang::create(['kode_barang'=>'MB006','nama_barang'=>'Oli Mesin 10W-40 4L (Honda)','id_kategori'=>$cat('MSN'),'description'=>'Pelumas mesin 10W-40 Honda Jazz & City','is_active'=>true]);
        $addTier($mb->id_barang,'LITER','OEM','BKA',[
            ['Honda Genuine 4L',   'HON-08C35-001A',195000,138000],
            ['Idemitsu 10W-40',    'IDM-10W40-4L',  188000,132000],
            ['Eneos 10W-40',       'ENE-10W40-4L',  205000,144000],
        ],$vB);
        $addTier($mb->id_barang,'LITER','Original','BKA',[
            ['Mobil 1 10W-40',     'MOB-10W40-4L',  155000, 97000],
            ['Fuchs Titan 10W-40', 'FCH-TIT-4L',    148000, 93000],
            ['Liqui Moly 10W-40',  'LQM-10W40-4L',  163000,102000],
        ],$vB);
        $addTier($mb->id_barang,'LITER','KW','BKA',[
            ['Enduro Racing 10W-40','END-10W40-4L',   78000, 44000],
            ['Pertamina Fastron',  'PTM-FST-4L',      74000, 42000],
            ['Mesran Super 10W-40','MSR-SUP-4L',       82000, 46000],
        ],$vB);

        // ── MB007: Filter Oli (Honda) ──────────────────── barcode 00055–00063 ─
        $mb = MBarang::create(['kode_barang'=>'MB007','nama_barang'=>'Filter Oli (Honda)','id_kategori'=>$cat('FLT'),'description'=>'Filter oli mesin Honda Jazz & City','is_active'=>true]);
        $addTier($mb->id_barang,'PCS','OEM','BKA',[
            ['Honda Genuine',    '15400-RTA-003',  92000,62000],
            ['Bosch F026407',    'BSH-F026407',    88000,59000],
            ['Mann W811/80',     'MNN-W81180',     96000,65000],
        ],$vB);
        $addTier($mb->id_barang,'PCS','Original','BKA',[
            ['K&N HP-1017',      'KN-HP1017',      48000,26000],
            ['Fram PH6017A',     'FRM-PH6017A',    44000,24000],
            ['Mahle OC198',      'MHL-OC198',      52000,28000],
        ],$vB);
        $addTier($mb->id_barang,'PCS','KW','BKA',[
            ['GS Filter FO-56',  'GS-FO5678',      20000,10500],
            ['Quantum FO-56H',   'QNT-FO56H',      19000,10000],
            ['SAS FO-Honda',     'SAS-FO-HON',     21000,11000],
        ],$vB);

        // ── MB008: Kampas Rem Depan (Honda) ──────────── barcode 00064–00072 ─
        $mb = MBarang::create(['kode_barang'=>'MB008','nama_barang'=>'Kampas Rem Depan (Honda)','id_kategori'=>$cat('REM'),'description'=>'Brake pad depan Honda Jazz & City','is_active'=>true]);
        $addTier($mb->id_barang,'SET','OEM','BKA',[
            ['Honda Genuine',    '45022-TF0-G51', 420000,290000],
            ['Akebono AN-450K',  'AKB-AN450K',    400000,276000],
            ['TRW MCB809',       'TRW-MCB809',    440000,304000],
        ],$vB);
        $addTier($mb->id_barang,'SET','Original','BKA',[
            ['Bendix DB1399',    'BDX-DB1399',    260000,165000],
            ['Brembo P28020',    'BRM-P28020',    248000,157000],
            ['Ferodo FDB1571',   'FRD-FDB1571',   273000,173000],
        ],$vB);
        $addTier($mb->id_barang,'SET','KW','BKA',[
            ['KTC KE-2200',      'KTC-KE2200',    120000, 65000],
            ['Textar 2157001',   'TXT-2157001',   114000, 62000],
            ['Safeline SL-Honda','SFL-SL-HON',    125000, 68000],
        ],$vB);

        // ── MB009: Timing Belt (Honda) ─────────────────── barcode 00073–00081 ─
        $mb = MBarang::create(['kode_barang'=>'MB009','nama_barang'=>'Timing Belt (Honda)','id_kategori'=>$cat('MSN'),'description'=>'Sabuk timing Honda Jazz & City','is_active'=>true]);
        $addTier($mb->id_barang,'PCS','OEM','BKA',[
            ['Honda Genuine',     '14400-RB1-004', 520000,370000],
            ['Contitech CT917',   'CTT-CT917',     495000,352000],
            ['Goodyear G1917RB',  'GDY-G1917RB',   545000,388000],
        ],$vB);
        $addTier($mb->id_barang,'PCS','Original','BKA',[
            ['Gates T163',        'GATES-T163',    380000,245000],
            ['Dayco 94789',       'DYC-94789',     362000,233000],
            ['Bando 196RU19',     'BND-196RU19',   400000,258000],
        ],$vB);
        $addTier($mb->id_barang,'PCS','KW','BKA',[
            ['GMB TB-1234',       'GMB-TB1234',    175000, 95000],
            ['QH QTB-Honda',      'QH-QTB-HON',   165000, 90000],
            ['Swag TB-CT917',     'SWG-CT917',     185000,100000],
        ],$vB);

        // ── MB010: Filter Udara (Honda) ───────────────── barcode 00082–00090 ─
        $mb = MBarang::create(['kode_barang'=>'MB010','nama_barang'=>'Filter Udara (Honda)','id_kategori'=>$cat('FLT'),'description'=>'Air filter Honda Jazz & City','is_active'=>true]);
        $addTier($mb->id_barang,'PCS','OEM','BKA',[
            ['Honda Genuine',    '17220-RB0-J30',  125000, 85000],
            ['Denso 143-2071',   'DNS-143-2071',   118000, 81000],
            ['Mann C24025',      'MNN-C24025',     130000, 89000],
        ],$vB);
        $addTier($mb->id_barang,'PCS','Original','BKA',[
            ['K&N E-3054',       'KN-E3054',        68000, 38000],
            ['Fram CA7682',      'FRM-CA7682',      64000, 36000],
            ['Mahle LX711',      'MHL-LX711',       72000, 40000],
        ],$vB);
        $addTier($mb->id_barang,'PCS','KW','BKA',[
            ['Sakura FA-5509',   'SKR-FA5509',      28000, 14000],
            ['GoodWill GF-Honda','GWL-GF-HON',      26000, 13000],
            ['SAS AF-Jazz',      'SAS-AF-JAZZ',     30000, 15000],
        ],$vB);

        // ── MB011: Oli Mesin Matic 10W-40 1L (Yamaha) ─── barcode 00091–00099 ─
        $mb = MBarang::create(['kode_barang'=>'MB011','nama_barang'=>'Oli Mesin Matic 10W-40 1L','id_kategori'=>$cat('MSN'),'description'=>'Pelumas mesin matic Yamaha NMAX & Aerox','is_active'=>true]);
        $addTier($mb->id_barang,'LITER','OEM','TBN',[
            ['Yamaha Yamalube 1L',   'YML-10W40-1L',  48000,32000],
            ['Ipone Full Power 1L',  'IPN-FP-10W40',  46000,30000],
            ['Motul Scooter 1L',     'MTL-SCT-10W40', 51000,34000],
        ],$vC);
        $addTier($mb->id_barang,'LITER','Original','TBN',[
            ['Motul 5100 10W-40',    'MTL-5100-1L',   38000,22000],
            ['Castrol Power 1',      'CST-PWR1-1L',   36000,21000],
            ['Repsol Moto 10W-40',   'RPS-MTO-1L',    40000,23000],
        ],$vC);
        $addTier($mb->id_barang,'LITER','KW','TBN',[
            ['Federal Oil Econo',    'FED-10W40-1L',  18000, 9000],
            ['Enduro Racing 1L',     'END-10W40-1L',  17000, 8500],
            ['Pertamina Prima XP',   'PTM-PXP-1L',    19000, 9500],
        ],$vC);

        // ── MB012: V-Belt CVT (Yamaha) ────────────────── barcode 00100–00108 ─
        $mb = MBarang::create(['kode_barang'=>'MB012','nama_barang'=>'V-Belt CVT (Yamaha)','id_kategori'=>$cat('TRS'),'description'=>'Belt CVT Yamaha NMAX & Aerox','is_active'=>true]);
        $addTier($mb->id_barang,'PCS','OEM','TBN',[
            ['Yamaha Genuine',   '2DP-E7641-00',  185000,130000],
            ['Bando CVT-8132',   'BND-CVT8132',   176000,124000],
            ['Dayco CVT-8132',   'DYC-CVT8132',   195000,137000],
        ],$vC);
        $addTier($mb->id_barang,'PCS','Original','TBN',[
            ['Gates 8132',       'GATES-8132',    140000, 87000],
            ['Continental CVT',  'CTT-CVT8132',   133000, 83000],
            ['Bosch 1987947709', 'BSH-1987947709',147000, 91000],
        ],$vC);
        $addTier($mb->id_barang,'PCS','KW','TBN',[
            ['DID CVT-8132',     'DID-CVT8132',    68000, 38000],
            ['GMB GBV8132',      'GMB-GBV8132',    65000, 36000],
            ['QH CVT-8132',      'QH-CVT8132',     72000, 40000],
        ],$vC);

        // ── MB013: Kampas Kopling CVT (Yamaha) ──────── barcode 00109–00117 ─
        $mb = MBarang::create(['kode_barang'=>'MB013','nama_barang'=>'Kampas Kopling CVT (Yamaha)','id_kategori'=>$cat('TRS'),'description'=>'Kampas kopling sentrifugal Yamaha NMAX & Aerox','is_active'=>true]);
        $addTier($mb->id_barang,'SET','OEM','TBN',[
            ['Yamaha Genuine',   '5TL-E6330-00',  215000,150000],
            ['Kyoto KVG-30',     'KYT-KVG30',     204000,142000],
            ['TZR OEM KK-155',   'TZR-KK155-OEM', 227000,158000],
        ],$vC);
        $addTier($mb->id_barang,'SET','Original','TBN',[
            ['DAIDO DKL-31',     'DID-DKL31',     130000, 80000],
            ['LHK CVT-31',       'LHK-CVT31',     124000, 76000],
            ['RK Clutch RK-31',  'RK-CL31',       137000, 84000],
        ],$vC);
        $addTier($mb->id_barang,'SET','KW','TBN',[
            ['TDR KK-155',       'TDR-KK155',      62000, 34000],
            ['UMA Racing KK-155','UMA-KK155',       58000, 32000],
            ['Racing Boy CVT',   'RCB-CVT155',      66000, 36000],
        ],$vC);

        // ── MB014: Busi Motor (Yamaha) ────────────────── barcode 00118–00126 ─
        $mb = MBarang::create(['kode_barang'=>'MB014','nama_barang'=>'Busi Motor (Yamaha)','id_kategori'=>$cat('MSN'),'description'=>'Busi Yamaha NMAX & Aerox','is_active'=>true]);
        $addTier($mb->id_barang,'PCS','OEM','TBN',[
            ['Yamaha Genuine Busi','YML-BUSI-155', 32000,22000],
            ['NGK CR8E-1 OEM',   'NGK-CR8E1-OEM', 30000,21000],
            ['Denso U24ESR-N',   'DNS-U24ESR',     34000,23000],
        ],$vC);
        $addTier($mb->id_barang,'PCS','Original','TBN',[
            ['NGK CPR8EA-9',     'NGK-CPR8EA9',   22000,13500],
            ['Iridium CR8EIX',   'NGK-CR8EIX',    24000,14500],
            ['Bosch X5DC',       'BSH-X5DC',      20000,12500],
        ],$vC);
        $addTier($mb->id_barang,'PCS','KW','TBN',[
            ['Denso IU24D local','DNS-IU24D-LOC',   9500, 5000],
            ['Champion RA6HC',   'CHP-RA6HC',       9000, 4700],
            ['AC Delco CR46',    'ACD-CR46',        10000, 5300],
        ],$vC);

        // ── MB015: Aki NS40 MF (Mixed Avanza/Jazz) ────── barcode 00127–00135 ─
        $mb = MBarang::create(['kode_barang'=>'MB015','nama_barang'=>'Aki NS40 MF','id_kategori'=>$cat('ELK'),'description'=>'Aki kering NS40 untuk Avanza, Jazz & City','is_active'=>true]);
        $addTier($mb->id_barang,'PCS','OEM','MJY',[
            ['GS Astra NS40 MF',  'GS-NS40-MF',   680000,470000],
            ['Motobatt MTX40',    'MTB-MTX40',     648000,448000],
            ['Century NS40 MF',   'CNT-NS40-MF',   714000,494000],
        ],$vF);
        $addTier($mb->id_barang,'PCS','Original','MJY',[
            ['Yuasa NS40 MF',    'YUA-NS40-MF',   580000,380000],
            ['Panasonic N-40B19L','PAN-N40B19L',  552000,362000],
            ['Amaron NS40 MF',   'AMR-NS40-MF',   608000,398000],
        ],$vF);
        $addTier($mb->id_barang,'PCS','KW','MJY',[
            ['Omega NS40 MF',    'OMG-NS40-MF',   380000,230000],
            ['Massiv NS40 MF',   'MSV-NS40-MF',   362000,219000],
            ['BMX NS40 MF',      'BMX-NS40-MF',   400000,242000],
        ],$vF);

        // ── MB016: Shock Absorber Depan (Avanza) ─────── barcode 00136–00144 ─
        $mb = MBarang::create(['kode_barang'=>'MB016','nama_barang'=>'Shock Absorber Depan (Avanza)','id_kategori'=>$cat('SUS'),'description'=>'Shock absorber depan Toyota Avanza F601/F652','is_active'=>true]);
        $addTier($mb->id_barang,'PSG','OEM','GNP',[
            ['Toyota Genuine',   '48510-0D370',  950000,680000],
            ['KYB OEM Avanza',   'KYB-OEM-AVZ',  903000,646000],
            ['Monroe OEM AVZ',   'MNR-OEM-AVZ',  998000,714000],
        ],$vD);
        $addTier($mb->id_barang,'PSG','Original','GNP',[
            ['KYB Excel-G',      'KYB-333402',   680000,455000],
            ['Monroe OESpectrum','MNR-OES-AVZ',  648000,433000],
            ['Kayaba Gas KYB',   'KYB-GAS-AVZ',  713000,478000],
        ],$vD);
        $addTier($mb->id_barang,'PSG','KW','GNP',[
            ['Monroe Reflex',    'MNR-R2800',    320000,180000],
            ['King Shock KS-001','KNG-KS001',    304000,171000],
            ['Strong Shock SS-1','STG-SS001',    336000,189000],
        ],$vD);

        // ── MB017: Bearing Roda Depan (Honda) ─────────── barcode 00145–00153 ─
        $mb = MBarang::create(['kode_barang'=>'MB017','nama_barang'=>'Bearing Roda Depan (Honda)','id_kategori'=>$cat('SUS'),'description'=>'Laher hub roda depan Honda Jazz & City','is_active'=>true]);
        $addTier($mb->id_barang,'PSG','OEM','BKA',[
            ['NSK 6204 DDU',     'NSK-6204-DDU', 210000,140000],
            ['SKF 6204-2RSH',    'SKF-6204-2RSH',199000,133000],
            ['Koyo 6204-2RS',    'KYO-6204-2RS', 220000,147000],
        ],$vB);
        $addTier($mb->id_barang,'PSG','Original','BKA',[
            ['SKF 6204-2RSH Orig','SKF-6204-ORG',185000,112000],
            ['FAG 6204-2RSR',    'FAG-6204-2RSR',175000,106000],
            ['NTN 6204LLU',      'NTN-6204LLU',  194000,118000],
        ],$vB);
        $addTier($mb->id_barang,'PSG','KW','BKA',[
            ['FAG 6204 local',   'FAG-6204-LOC',  88000, 48000],
            ['Asahi 6204',       'ASH-6204',       84000, 46000],
            ['SBS 6204 KW',      'SBS-6204-KW',    92000, 50000],
        ],$vB);

        // ── MB018: Koil Pengapian (Toyota) ────────────── barcode 00154–00162 ─
        $mb = MBarang::create(['kode_barang'=>'MB018','nama_barang'=>'Koil Pengapian (Toyota)','id_kategori'=>$cat('ELK'),'description'=>'Ignition coil Toyota Avanza F652/F653 & Yaris XP150','is_active'=>true]);
        $addTier($mb->id_barang,'PCS','OEM','GNP',[
            ['Toyota Genuine',   '90919-02240',  380000,265000],
            ['Denso 90919-equiv','DNS-90919-EQV',362000,252000],
            ['Standard UF400',   'STD-UF400',    400000,278000],
        ],$vH);
        $addTier($mb->id_barang,'PCS','Original','GNP',[
            ['Bosch 0986221013', 'BSH-0986221013',320000,210000],
            ['NGK U5041',        'NGK-U5041',     305000,200000],
            ['Delphi GN10236',   'DLP-GN10236',   337000,221000],
        ],$vH);
        $addTier($mb->id_barang,'PCS','KW','GNP',[
            ['Bremi 20396',      'BRM-20396',     148000, 85000],
            ['Hella 5DA193',     'HLA-5DA193',    141000, 81000],
            ['Siemens VDO',      'SMN-VDO-KOL',   155000, 89000],
        ],$vH);

        // ── MB019: Lampu Depan H4 60/55W (Mixed) ─────── barcode 00163–00171 ─
        $mb = MBarang::create(['kode_barang'=>'MB019','nama_barang'=>'Lampu Depan H4 60/55W','id_kategori'=>$cat('ELK'),'description'=>'Bohlam H4 halogen untuk Avanza, Jazz & Brio','is_active'=>true]);
        $addTier($mb->id_barang,'PSG','OEM','MJY',[
            ['Osram Original H4','OSR-64193',     120000, 78000],
            ['Philips Standard H4','PHL-12342STD',114000, 74000],
            ['GE H4 Standard',   'GE-H4-60W',    126000, 82000],
        ],$vE);
        $addTier($mb->id_barang,'PSG','Original','MJY',[
            ['Philips X-tremeVision','PHL-12342PRC1',95000,58000],
            ['Osram Night Breaker','OSR-64193NBR', 90000, 55000],
            ['Bosch Pure Light H4','BSH-1987302050',100000,61000],
        ],$vE);
        $addTier($mb->id_barang,'PSG','KW','MJY',[
            ['Narva H4 Economy', 'NRV-H4-55W',    35000, 18000],
            ['Koito H4 55W',     'KOI-H4-55W',    33000, 17000],
            ['Ring Standard H4', 'RNG-R459',       37000, 19000],
        ],$vE);

        // ── MB020: Kampas Rem Belakang (Toyota) ─────── barcode 00172–00180 ─
        $mb = MBarang::create(['kode_barang'=>'MB020','nama_barang'=>'Kampas Rem Belakang (Toyota)','id_kategori'=>$cat('REM'),'description'=>'Brake shoe tromol belakang Toyota Avanza & Agya','is_active'=>true]);
        $addTier($mb->id_barang,'SET','OEM','GNP',[
            ['Toyota Genuine',   '04495-0D060',  285000,195000],
            ['FBK BS539078',     'FBK-BS539078', 271000,185000],
            ['Sumitomo SPK-001', 'SMT-SPK001',   299000,205000],
        ],$vA);
        $addTier($mb->id_barang,'SET','Original','GNP',[
            ['Ferodo FSB749',    'FRD-FSB749',   210000,130000],
            ['Brembo P23004',    'BRM-P23004',   200000,124000],
            ['Bosch BS539078',   'BSH-BS539078', 222000,137000],
        ],$vA);
        $addTier($mb->id_barang,'SET','KW','GNP',[
            ['TBK RB-0452',      'TBK-RB0452',    88000, 48000],
            ['Textar 2009001',   'TXT-2009001',    84000, 46000],
            ['QH BT-001',        'QH-BT001',       92000, 50000],
        ],$vA);

        $totalVariasi = $barcode - 1;
        $this->command->info('✓ SparePartSeeder: '.$totalVariasi.' variasi (barcode 00001–'.str_pad($totalVariasi,5,'0',STR_PAD_LEFT).').');
        $this->command->info('  → 20 MB × 3 tier × 3 variasi × 3 supplier = '.$totalVariasi.' variasi, '.($totalVariasi * 3).' supplier_barang records.');
    }
}
