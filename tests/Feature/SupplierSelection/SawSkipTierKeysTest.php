<?php

use App\Models\Needlist;
use App\Models\SawKriteria;
use App\Models\SawNilaiHistoris;
use App\Models\SawNilaiHistorisDetail;
use App\Models\SawPerhitungan;
use App\Models\SawPerhitunganDetail;
use App\Models\SawRekomendasi;
use App\Models\Supplier;
use App\Models\SupplierInquiry;
use App\Models\SupplierInquiryItem;
use App\Models\Variasi;
use App\Services\NeedlistSelectionGrouper;
use App\Services\SawBatchCalculator;
use App\Services\SawService;

/*
|--------------------------------------------------------------------------
| SawBatchCalculator::determineSkipTierKeys() — dirty-check pengganti
| tombol "Hitung Ulang" manual (dihapus). Kelompok yang sudah dikonfirmasi
| selalu di-skip; kelompok yang belum dikonfirmasi di-skip HANYA kalau
| datanya (harga penawaran, nilai kinerja supplier, bobot kriteria) belum
| berubah sejak kalkulasi terakhir.
|--------------------------------------------------------------------------
*/

function batchCalculatorUntukTest(): SawBatchCalculator
{
    return new SawBatchCalculator(app(SawService::class), app(NeedlistSelectionGrouper::class));
}

function buatPerhitunganUntukSkipTest(Needlist $needlist, Variasi $variasi, Supplier $supplier, \Carbon\Carbon $calculatedAt): SawPerhitungan
{
    $tierKey = md5((string) $variasi->id_variasi);

    $perhitungan = SawPerhitungan::factory()->create([
        'needlist_id'   => $needlist->id,
        'id_variasi'    => $variasi->id_variasi,
        'tier_key'      => $tierKey,
        'calculated_at' => $calculatedAt,
    ]);

    SawPerhitunganDetail::factory()->create([
        'perhitungan_id' => $perhitungan->id,
        'supplier_id'    => $supplier->id_supplier,
        'id_variasi'     => $variasi->id_variasi,
    ]);

    return $perhitungan;
}

it('never includes a tier key that has not been calculated yet', function () {
    $needlist = Needlist::factory()->create();

    expect(batchCalculatorUntukTest()->determineSkipTierKeys($needlist))->toBeEmpty();
});

it('skips an unconfirmed tier key when nothing changed since calculated_at', function () {
    $needlist = Needlist::factory()->create();
    $variasi  = Variasi::factory()->create();
    $supplier = Supplier::factory()->create();

    $inquiry = SupplierInquiry::factory()->create(['needlist_id' => $needlist->id, 'supplier_id' => $supplier->id_supplier]);
    SupplierInquiryItem::factory()->create(['inquiry_id' => $inquiry->id, 'id_variasi' => $variasi->id_variasi]);
    SawNilaiHistoris::factory()->create(['supplier_id' => $supplier->id_supplier]);

    $perhitungan = buatPerhitunganUntukSkipTest($needlist, $variasi, $supplier, now()->addMinute());

    expect(batchCalculatorUntukTest()->determineSkipTierKeys($needlist))->toContain($perhitungan->tier_key);
});

it('does not skip when supplier inquiry price was updated after calculated_at', function () {
    $needlist = Needlist::factory()->create();
    $variasi  = Variasi::factory()->create();
    $supplier = Supplier::factory()->create();

    $inquiry = SupplierInquiry::factory()->create(['needlist_id' => $needlist->id, 'supplier_id' => $supplier->id_supplier]);
    $item    = SupplierInquiryItem::factory()->create(['inquiry_id' => $inquiry->id, 'id_variasi' => $variasi->id_variasi]);

    $perhitungan = buatPerhitunganUntukSkipTest($needlist, $variasi, $supplier, now()->subMinute());

    $item->touch(); // simulasi harga konfirmasi baru diedit setelah kalkulasi terakhir

    expect(batchCalculatorUntukTest()->determineSkipTierKeys($needlist))->not->toContain($perhitungan->tier_key);
});

it('does not skip when supplier historis nilai was updated after calculated_at', function () {
    $needlist = Needlist::factory()->create();
    $variasi  = Variasi::factory()->create();
    $supplier = Supplier::factory()->create();
    $kriteria = SawKriteria::factory()->create(['kode' => 'C2']);
    $historis = SawNilaiHistoris::factory()->create(['supplier_id' => $supplier->id_supplier]);
    $detail   = SawNilaiHistorisDetail::create([
        'historis_id' => $historis->id, 'kriteria_id' => $kriteria->id, 'nilai' => 3,
    ]);

    $perhitungan = buatPerhitunganUntukSkipTest($needlist, $variasi, $supplier, now()->subMinute());

    $detail->touch(); // simulasi nilai kinerja supplier baru diedit di UC-02

    expect(batchCalculatorUntukTest()->determineSkipTierKeys($needlist))->not->toContain($perhitungan->tier_key);
});

it('does not skip when any kriteria weight was updated after calculated_at', function () {
    $needlist = Needlist::factory()->create();
    $variasi  = Variasi::factory()->create();
    $supplier = Supplier::factory()->create();
    $kriteria = SawKriteria::factory()->create();

    $perhitungan = buatPerhitunganUntukSkipTest($needlist, $variasi, $supplier, now()->subMinute());

    $kriteria->touch(); // simulasi bobot/kriteria baru diedit di UC-01

    expect(batchCalculatorUntukTest()->determineSkipTierKeys($needlist))->not->toContain($perhitungan->tier_key);
});

it('always skips a confirmed tier key even if data changed afterward', function () {
    $needlist = Needlist::factory()->create();
    $variasi  = Variasi::factory()->create();
    $supplier = Supplier::factory()->create();

    $inquiry = SupplierInquiry::factory()->create(['needlist_id' => $needlist->id, 'supplier_id' => $supplier->id_supplier]);
    $item    = SupplierInquiryItem::factory()->create(['inquiry_id' => $inquiry->id, 'id_variasi' => $variasi->id_variasi]);

    $perhitungan = buatPerhitunganUntukSkipTest($needlist, $variasi, $supplier, now()->subMinute());

    SawRekomendasi::factory()->create([
        'needlist_id'     => $needlist->id,
        'id_variasi'      => $variasi->id_variasi,
        'perhitungan_id'  => $perhitungan->id,
        'supplier_id_saw' => $supplier->id_supplier,
    ]);

    $item->touch(); // data berubah SETELAH dikonfirmasi — tidak boleh mempengaruhi

    expect(batchCalculatorUntukTest()->determineSkipTierKeys($needlist))->toContain($perhitungan->tier_key);
});
