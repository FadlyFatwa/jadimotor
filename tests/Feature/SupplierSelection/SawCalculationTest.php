<?php

use App\Models\Needlist;
use App\Models\ProductVariantCompatibility;
use App\Models\SawKriteria;
use App\Models\SawNilaiHistoris;
use App\Models\SawPerhitungan;
use App\Models\SawPerhitunganDetail;
use App\Models\Supplier;
use App\Models\SupplierInquiry;
use App\Models\SupplierInquiryItem;
use App\Models\Variasi;
use App\Models\VehicleGeneration;
use App\Services\SawBatchCalculator;
use App\Services\SawService;

/*
|--------------------------------------------------------------------------
| SawService::calculate() — verifies the raw SAW math against a
| hand-computed expected result (standard SAW formula):
|   benefit: rij = xij / max(xij)
|   cost:    rij = min(xij) / xij
|   Vi = sum(Wj * Rij), ranked descending.
|--------------------------------------------------------------------------
*/

it('computes normalization, weighted sum and ranking per the standard SAW formula', function () {
    // Weights sum to 1.0000 exactly.
    SawKriteria::factory()->create(['kode' => 'C1', 'jenis' => 'cost', 'bobot' => 0.30, 'urutan' => 1]);
    SawKriteria::factory()->create(['kode' => 'C2', 'jenis' => 'benefit', 'bobot' => 0.10, 'urutan' => 2]);
    SawKriteria::factory()->create(['kode' => 'C3', 'jenis' => 'cost', 'bobot' => 0.20, 'urutan' => 3]);
    SawKriteria::factory()->create(['kode' => 'C4', 'jenis' => 'benefit', 'bobot' => 0.15, 'urutan' => 4]);
    SawKriteria::factory()->create(['kode' => 'C5', 'jenis' => 'benefit', 'bobot' => 0.15, 'urutan' => 5]);
    SawKriteria::factory()->create(['kode' => 'C6', 'jenis' => 'benefit', 'bobot' => 0.10, 'urutan' => 6]);

    $needlist = Needlist::factory()->create();
    $variasi = Variasi::factory()->create();

    $supplierA = Supplier::factory()->create();
    $supplierB = Supplier::factory()->create();
    $supplierC = Supplier::factory()->create();

    $supplierData = [
        [
            'supplier_id' => $supplierA->id_supplier,
            'id_variasi' => $variasi->id_variasi,
            'nama' => 'Supplier A',
            'C1' => 100000, 'C2' => 14, 'C3' => 5, 'C4' => 90, 'C5' => 95, 'C6' => 4,
            '_has_historis' => true, '_sumber_c1' => 'inquiry', '_sumber_c3' => 'historis',
        ],
        [
            'supplier_id' => $supplierB->id_supplier,
            'id_variasi' => $variasi->id_variasi,
            'nama' => 'Supplier B',
            'C1' => 120000, 'C2' => 30, 'C3' => 3, 'C4' => 95, 'C5' => 90, 'C6' => 5,
            '_has_historis' => true, '_sumber_c1' => 'inquiry', '_sumber_c3' => 'historis',
        ],
        [
            'supplier_id' => $supplierC->id_supplier,
            'id_variasi' => $variasi->id_variasi,
            'nama' => 'Supplier C',
            'C1' => 90000, 'C2' => 7, 'C3' => 7, 'C4' => 100, 'C5' => 100, 'C6' => 3,
            '_has_historis' => true, '_sumber_c1' => 'inquiry', '_sumber_c3' => 'historis',
        ],
    ];

    $result = (new SawService())->calculate($needlist->id, $variasi->id_variasi, $supplierData);

    // Hand-computed expectation (see report for the full derivation):
    // Vi(A) ≈ 0.794167, Vi(B) ≈ 0.9025, Vi(C) ≈ 0.769048 -> ranking B > A > C.
    $ranked = $result['ranked']->keyBy('supplier_id');

    expect((float) $ranked[$supplierB->id_supplier]['nilai_vi'])->toEqualWithDelta(0.9025, 0.0005);
    expect((float) $ranked[$supplierA->id_supplier]['nilai_vi'])->toEqualWithDelta(0.794167, 0.0005);
    expect((float) $ranked[$supplierC->id_supplier]['nilai_vi'])->toEqualWithDelta(0.769048, 0.0005);

    expect($ranked[$supplierB->id_supplier]['ranking'])->toBe(1);
    expect($ranked[$supplierA->id_supplier]['ranking'])->toBe(2);
    expect($ranked[$supplierC->id_supplier]['ranking'])->toBe(3);

    expect($ranked[$supplierB->id_supplier]['is_recommended'])->toBe(1);
    expect($ranked[$supplierA->id_supplier]['is_recommended'])->toBe(0);
    expect($ranked[$supplierC->id_supplier]['is_recommended'])->toBe(0);

    expect($result['rekomendasi']['supplier_id'])->toBe($supplierB->id_supplier);

    // Persisted rows must mirror the in-memory calculation.
    $perhitungan = SawPerhitungan::findOrFail($result['perhitungan_id']);
    expect($perhitungan->needlist_id)->toBe($needlist->id);
    expect($perhitungan->id_variasi)->toBe($variasi->id_variasi);
    expect($perhitungan->details)->toHaveCount(3);

    $winnerDetail = $perhitungan->details->firstWhere('supplier_id', $supplierB->id_supplier);
    expect($winnerDetail->ranking)->toBe(1);
    expect((bool) $winnerDetail->is_recommended)->toBeTrue();
    expect($winnerDetail->norm('C1'))->toEqualWithDelta(0.75, 0.0005); // cost: min(90000)/120000
    expect($winnerDetail->norm('C2'))->toEqualWithDelta(1.0, 0.0005); // benefit: 30/max(30)
});

it('throws when active kriteria weights do not sum to 1.0', function () {
    SawKriteria::factory()->create(['kode' => 'C1', 'jenis' => 'cost', 'bobot' => 0.50, 'urutan' => 1]);
    SawKriteria::factory()->create(['kode' => 'C2', 'jenis' => 'benefit', 'bobot' => 0.30, 'urutan' => 2]);

    $needlist = Needlist::factory()->create();
    $variasi = Variasi::factory()->create();
    $supplierA = Supplier::factory()->create();
    $supplierB = Supplier::factory()->create();

    $data = [
        ['supplier_id' => $supplierA->id_supplier, 'nama' => 'A', 'C1' => 1, 'C2' => 1],
        ['supplier_id' => $supplierB->id_supplier, 'nama' => 'B', 'C1' => 2, 'C2' => 2],
    ];

    expect(fn () => (new SawService())->calculate($needlist->id, $variasi->id_variasi, $data))
        ->toThrow(RuntimeException::class);
});

it('throws when fewer than 2 suppliers are provided', function () {
    SawKriteria::factory()->create(['kode' => 'C1', 'jenis' => 'cost', 'bobot' => 1.0, 'urutan' => 1]);

    $needlist = Needlist::factory()->create();
    $variasi = Variasi::factory()->create();
    $supplierA = Supplier::factory()->create();

    $data = [
        ['supplier_id' => $supplierA->id_supplier, 'nama' => 'A', 'C1' => 1],
    ];

    expect(fn () => (new SawService())->calculate($needlist->id, $variasi->id_variasi, $data))
        ->toThrow(InvalidArgumentException::class);
});

/*
|--------------------------------------------------------------------------
| SawBatchCalculator::calculateForNeedlist() — integration through real
| Needlist / SupplierInquiry / SawNilaiHistoris records.
|--------------------------------------------------------------------------
*/

beforeEach(function () {
    // Local helper (bound to $this, not a global function) so it can't collide
    // with same-named helpers other parallel test suites might define.
    $this->seedFullSawKriteria = function () {
        SawKriteria::factory()->create(['kode' => 'C1', 'jenis' => 'cost', 'bobot' => 0.30, 'urutan' => 1]);
        SawKriteria::factory()->create(['kode' => 'C2', 'jenis' => 'benefit', 'bobot' => 0.10, 'urutan' => 2]);
        SawKriteria::factory()->create(['kode' => 'C3', 'jenis' => 'cost', 'bobot' => 0.20, 'urutan' => 3]);
        SawKriteria::factory()->create(['kode' => 'C4', 'jenis' => 'benefit', 'bobot' => 0.15, 'urutan' => 4]);
        SawKriteria::factory()->create(['kode' => 'C5', 'jenis' => 'benefit', 'bobot' => 0.15, 'urutan' => 5]);
        SawKriteria::factory()->create(['kode' => 'C6', 'jenis' => 'benefit', 'bobot' => 0.10, 'urutan' => 6]);
    };
});

it('runs the full batch calculation for a needlist and ranks suppliers correctly', function () {
    ($this->seedFullSawKriteria)();

    $needlist = Needlist::factory()->create();
    $variasi = Variasi::factory()->create();

    // Give the item a vehicle-generation compatibility, like a properly categorized
    // master barang would have.
    $generation = VehicleGeneration::factory()->create();
    ProductVariantCompatibility::create([
        'id_variasi' => $variasi->id_variasi,
        'vehicle_generation_id' => $generation->id,
        'is_compatible' => true,
    ]);

    $supplierCheap = Supplier::factory()->create();
    $supplierExpensive = Supplier::factory()->create();

    // Identical secondary criteria (C2-C6) for both suppliers so they normalize
    // to the same value on every criterion except price (C1) — this isolates the
    // assertion to "cheaper price -> higher SAW score" instead of depending on
    // several criteria's relative weights cancelling each other out.
    $sharedHistoris = ['C2' => 3, 'C3' => 5, 'C4' => 95, 'C5' => 95, 'C6' => 4];

    $historisCheap = SawNilaiHistoris::factory()->create(['supplier_id' => $supplierCheap->id_supplier]);
    seedHistorisNilai($historisCheap, $sharedHistoris);

    $historisExpensive = SawNilaiHistoris::factory()->create(['supplier_id' => $supplierExpensive->id_supplier]);
    seedHistorisNilai($historisExpensive, $sharedHistoris);

    $inquiryCheap = SupplierInquiry::factory()->create([
        'needlist_id' => $needlist->id, 'supplier_id' => $supplierCheap->id_supplier, 'status' => 'responded',
    ]);
    SupplierInquiryItem::factory()->create([
        'inquiry_id' => $inquiryCheap->id, 'id_variasi' => $variasi->id_variasi,
        'harga_penawaran' => 100000, 'estimasi_pengiriman' => now()->addDays(5)->toDateString(),
    ]);

    $inquiryExpensive = SupplierInquiry::factory()->create([
        'needlist_id' => $needlist->id, 'supplier_id' => $supplierExpensive->id_supplier, 'status' => 'responded',
    ]);
    SupplierInquiryItem::factory()->create([
        'inquiry_id' => $inquiryExpensive->id, 'id_variasi' => $variasi->id_variasi,
        'harga_penawaran' => 120000, 'estimasi_pengiriman' => now()->addDays(3)->toDateString(),
    ]);

    $needlist->load(['details', 'supplierInquiries.supplier', 'supplierInquiries.items.variasi.m_barang']);

    $results = (new SawBatchCalculator(app(SawService::class), app(\App\Services\NeedlistSelectionGrouper::class)))
        ->calculateForNeedlist($needlist);

    expect($results)->toHaveCount(1);
    expect($results[0]['auto_assigned'])->toBeFalse();
    expect($results[0]['recommended']['supplier_id'])->toBe($supplierCheap->id_supplier);

    $this->assertDatabaseHas('saw_perhitungan', [
        'id' => $results[0]['perhitungan_id'],
        'needlist_id' => $needlist->id,
    ]);

    $winnerDetail = SawPerhitunganDetail::where('perhitungan_id', $results[0]['perhitungan_id'])
        ->where('is_recommended', 1)->first();
    expect($winnerDetail->supplier_id)->toBe($supplierCheap->id_supplier);
});

it('excludes suppliers without historis and auto-assigns when only one candidate remains', function () {
    ($this->seedFullSawKriteria)();

    $needlist = Needlist::factory()->create();
    $variasi = Variasi::factory()->create();

    $generation = VehicleGeneration::factory()->create();
    ProductVariantCompatibility::create([
        'id_variasi' => $variasi->id_variasi,
        'vehicle_generation_id' => $generation->id,
        'is_compatible' => true,
    ]);

    $supplierWithHistoris = Supplier::factory()->create();
    $supplierWithoutHistoris = Supplier::factory()->create();

    SawNilaiHistoris::factory()->create(['supplier_id' => $supplierWithHistoris->id_supplier]);
    // $supplierWithoutHistoris intentionally has no saw_nilai_historis row.

    $inquiry1 = SupplierInquiry::factory()->create([
        'needlist_id' => $needlist->id, 'supplier_id' => $supplierWithHistoris->id_supplier, 'status' => 'responded',
    ]);
    SupplierInquiryItem::factory()->create([
        'inquiry_id' => $inquiry1->id, 'id_variasi' => $variasi->id_variasi,
        'harga_penawaran' => 100000, 'estimasi_pengiriman' => now()->addDays(5)->toDateString(),
    ]);

    $inquiry2 = SupplierInquiry::factory()->create([
        'needlist_id' => $needlist->id, 'supplier_id' => $supplierWithoutHistoris->id_supplier, 'status' => 'responded',
    ]);
    SupplierInquiryItem::factory()->create([
        'inquiry_id' => $inquiry2->id, 'id_variasi' => $variasi->id_variasi,
        'harga_penawaran' => 90000, 'estimasi_pengiriman' => now()->addDays(2)->toDateString(),
    ]);

    $needlist->load(['details', 'supplierInquiries.supplier', 'supplierInquiries.items.variasi.m_barang']);

    $results = (new SawBatchCalculator(app(SawService::class), app(\App\Services\NeedlistSelectionGrouper::class)))
        ->calculateForNeedlist($needlist);

    expect($results)->toHaveCount(1);
    expect($results[0]['auto_assigned'])->toBeTrue();
    expect($results[0]['recommended']['supplier_id'])->toBe($supplierWithHistoris->id_supplier);
    expect($results[0]['perhitungan_id'])->toBeNull();
    expect($results[0]['excluded'])->toHaveCount(1);

    // Auto-assigned groups never create a SawPerhitungan row.
    expect(SawPerhitungan::where('needlist_id', $needlist->id)->count())->toBe(0);
});

it('compares two suppliers quoting the same universal (no vehicle-generation) item', function () {
    // Regression test for a bug that used to live in
    // NeedlistSelectionGrouper::clusterByVehicleGeneration(): it unioned rows only
    // when array_intersect() of their vehicle-generation ids was non-empty. Two rows
    // that BOTH have zero vehicle-generation links (e.g. an uncategorized master
    // barang — the norm for most of the real catalog) produced
    // array_intersect([], []) === [], which is falsy, so they were never merged into
    // the same cluster/group even though they were literally the same SKU (same
    // id_variasi) quoted by two different suppliers. Each ended up alone in its own
    // 1-row group, unique_supplier_count === 1, and SawBatchCalculator silently
    // skipped it (no SAW calculation, no auto-assignment at all).
    // Fixed by also unioning rows that share the same id_variasi, regardless of
    // vehicle-generation data — two rows quoting the identical SKU must always be
    // compared together.
    ($this->seedFullSawKriteria)();

    $needlist = Needlist::factory()->create();
    $variasi = Variasi::factory()->create(); // no ProductVariantCompatibility at all

    $supplierCheap = Supplier::factory()->create();
    $supplierExpensive = Supplier::factory()->create();

    $sharedHistoris = ['C2' => 3, 'C3' => 5, 'C4' => 95, 'C5' => 95, 'C6' => 4];

    $historisCheap = SawNilaiHistoris::factory()->create(['supplier_id' => $supplierCheap->id_supplier]);
    seedHistorisNilai($historisCheap, $sharedHistoris);

    $historisExpensive = SawNilaiHistoris::factory()->create(['supplier_id' => $supplierExpensive->id_supplier]);
    seedHistorisNilai($historisExpensive, $sharedHistoris);

    $inquiryCheap = SupplierInquiry::factory()->create([
        'needlist_id' => $needlist->id, 'supplier_id' => $supplierCheap->id_supplier, 'status' => 'responded',
    ]);
    SupplierInquiryItem::factory()->create([
        'inquiry_id' => $inquiryCheap->id, 'id_variasi' => $variasi->id_variasi,
        'harga_penawaran' => 100000, 'estimasi_pengiriman' => now()->addDays(5)->toDateString(),
    ]);

    $inquiryExpensive = SupplierInquiry::factory()->create([
        'needlist_id' => $needlist->id, 'supplier_id' => $supplierExpensive->id_supplier, 'status' => 'responded',
    ]);
    SupplierInquiryItem::factory()->create([
        'inquiry_id' => $inquiryExpensive->id, 'id_variasi' => $variasi->id_variasi,
        'harga_penawaran' => 120000, 'estimasi_pengiriman' => now()->addDays(3)->toDateString(),
    ]);

    $needlist->load(['details', 'supplierInquiries.supplier', 'supplierInquiries.items.variasi.m_barang']);

    $results = (new SawBatchCalculator(app(SawService::class), app(\App\Services\NeedlistSelectionGrouper::class)))
        ->calculateForNeedlist($needlist);

    // Fixed behavior: both suppliers land in the same group and get compared via SAW.
    expect($results)->toHaveCount(1);
    expect($results[0]['auto_assigned'])->toBeFalse();
    expect($results[0]['recommended']['supplier_id'])->toBe($supplierCheap->id_supplier);
});
