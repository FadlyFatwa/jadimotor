<?php

use App\Models\Needlist;
use App\Models\SawKriteria;
use App\Models\Supplier;
use App\Models\SupplierInquiry;
use App\Models\SupplierInquiryItem;
use App\Models\User;
use App\Models\Variasi;

beforeEach(function () {
    $this->user = User::factory()->role('procurement')->create();

    SawKriteria::factory()->create(['kode' => 'C1', 'jenis' => 'cost', 'bobot' => 0.30, 'urutan' => 1]);
    SawKriteria::factory()->create(['kode' => 'C2', 'jenis' => 'benefit', 'bobot' => 0.10, 'urutan' => 2]);
    SawKriteria::factory()->create(['kode' => 'C3', 'jenis' => 'cost', 'bobot' => 0.20, 'urutan' => 3]);
    SawKriteria::factory()->create(['kode' => 'C4', 'jenis' => 'benefit', 'bobot' => 0.15, 'urutan' => 4]);
    SawKriteria::factory()->create(['kode' => 'C5', 'jenis' => 'benefit', 'bobot' => 0.15, 'urutan' => 5]);
    SawKriteria::factory()->create(['kode' => 'C6', 'jenis' => 'benefit', 'bobot' => 0.10, 'urutan' => 6]);
});

it('lists needlists that are in the selection_in_progress stage', function () {
    Needlist::factory()->create(['status' => 'selection_in_progress']);
    Needlist::factory()->create(['status' => 'draft']);

    $this->actingAs($this->user)
        ->get(route('pemilihan-supplier.index'))
        ->assertStatus(200);
});

it('shows "Belum Ada Konfirmasi" when no supplier has confirmed a price yet', function () {
    $needlist = Needlist::factory()->create(['status' => 'selection_in_progress']);

    $this->actingAs($this->user)
        ->get(route('pemilihan-supplier.index'))
        ->assertSee('Belum Ada Konfirmasi');
});

it('shows "Belum Dipilih" when prices are confirmed but nothing selected yet', function () {
    $needlist = Needlist::factory()->create(['status' => 'selection_in_progress']);
    $variasi  = Variasi::factory()->create();
    $supplier = Supplier::factory()->create();

    $inquiry = SupplierInquiry::factory()->create(['needlist_id' => $needlist->id, 'supplier_id' => $supplier->id_supplier]);
    SupplierInquiryItem::factory()->create([
        'inquiry_id' => $inquiry->id, 'id_variasi' => $variasi->id_variasi,
        'harga_penawaran' => 100000, 'status' => 'pending',
    ]);

    $this->actingAs($this->user)
        ->get(route('pemilihan-supplier.index'))
        ->assertSee('Belum Dipilih');
});

it('shows "Sebagian Dipilih" when only some confirmed variasi are selected', function () {
    $needlist = Needlist::factory()->create(['status' => 'selection_in_progress']);
    $variasiA = Variasi::factory()->create();
    $variasiB = Variasi::factory()->create();
    $supplier = Supplier::factory()->create();

    $inquiry = SupplierInquiry::factory()->create(['needlist_id' => $needlist->id, 'supplier_id' => $supplier->id_supplier]);
    SupplierInquiryItem::factory()->create([
        'inquiry_id' => $inquiry->id, 'id_variasi' => $variasiA->id_variasi,
        'harga_penawaran' => 100000, 'status' => 'selected',
    ]);
    SupplierInquiryItem::factory()->create([
        'inquiry_id' => $inquiry->id, 'id_variasi' => $variasiB->id_variasi,
        'harga_penawaran' => 120000, 'status' => 'pending',
    ]);

    $this->actingAs($this->user)
        ->get(route('pemilihan-supplier.index'))
        ->assertSee('Sebagian Dipilih');
});

it('shows "Sudah Dipilih" when every confirmed variasi already has a selection', function () {
    $needlist = Needlist::factory()->create(['status' => 'selection_in_progress']);
    $variasi  = Variasi::factory()->create();
    $supplier = Supplier::factory()->create();

    $inquiry = SupplierInquiry::factory()->create(['needlist_id' => $needlist->id, 'supplier_id' => $supplier->id_supplier]);
    SupplierInquiryItem::factory()->create([
        'inquiry_id' => $inquiry->id, 'id_variasi' => $variasi->id_variasi,
        'harga_penawaran' => 100000, 'status' => 'selected',
    ]);

    $this->actingAs($this->user)
        ->get(route('pemilihan-supplier.index'))
        ->assertSee('Sudah Dipilih');
});

it('shows "Sudah Dipilih" even though the losing alternative in a comparison group stays pending', function () {
    // Regresi: dua supplier menawarkan VARIASI BERBEDA untuk kebutuhan yang sama
    // (dikelompokkan bersama karena kompatibel dengan generasi kendaraan yang
    // sama). Cuma satu yang dipilih — alternatif yang kalah wajar tetap
    // 'pending' selamanya. Menghitung progres per variasi (bukan per kelompok)
    // salah mengira needlist ini "Sebagian Dipilih".
    $needlist     = Needlist::factory()->create(['status' => 'selection_in_progress']);
    $masterBarang = \App\Models\MBarang::factory()->create();
    $generation   = \App\Models\VehicleGeneration::factory()->create();

    $variasiA = Variasi::factory()->create(['id_barang' => $masterBarang->id_barang]);
    $variasiB = Variasi::factory()->create(['id_barang' => $masterBarang->id_barang]);

    foreach ([$variasiA, $variasiB] as $v) {
        \App\Models\ProductVariantCompatibility::create([
            'id_variasi' => $v->id_variasi,
            'vehicle_generation_id' => $generation->id,
            'is_compatible' => true,
        ]);
    }

    $supplierA = Supplier::factory()->create();
    $supplierB = Supplier::factory()->create();

    $inquiryA = SupplierInquiry::factory()->create(['needlist_id' => $needlist->id, 'supplier_id' => $supplierA->id_supplier]);
    SupplierInquiryItem::factory()->create([
        'inquiry_id' => $inquiryA->id, 'id_variasi' => $variasiA->id_variasi,
        'harga_penawaran' => 100000, 'status' => 'selected',
    ]);

    $inquiryB = SupplierInquiry::factory()->create(['needlist_id' => $needlist->id, 'supplier_id' => $supplierB->id_supplier]);
    SupplierInquiryItem::factory()->create([
        'inquiry_id' => $inquiryB->id, 'id_variasi' => $variasiB->id_variasi,
        'harga_penawaran' => 120000, 'status' => 'pending',
    ]);

    $this->actingAs($this->user)
        ->get(route('pemilihan-supplier.index'))
        ->assertSee('Sudah Dipilih')
        ->assertDontSee('Sebagian Dipilih');
});

it('shows the ringkasan page for a needlist', function () {
    $needlist = Needlist::factory()->create(['status' => 'selection_in_progress']);

    $this->actingAs($this->user)
        ->get(route('pemilihan-supplier.ringkasan', $needlist->id))
        ->assertStatus(200);
});

it('shows the pemilihan supplier detail page and auto-runs the SAW batch calculation', function () {
    $needlist = Needlist::factory()->create(['status' => 'selection_in_progress']);
    $variasi = Variasi::factory()->create();

    $generation = \App\Models\VehicleGeneration::factory()->create();
    \App\Models\ProductVariantCompatibility::create([
        'id_variasi' => $variasi->id_variasi,
        'vehicle_generation_id' => $generation->id,
        'is_compatible' => true,
    ]);

    $supplierCheap = Supplier::factory()->create();
    $supplierExpensive = Supplier::factory()->create();

    // Identical secondary criteria for both suppliers so only price (C1) can
    // decide the winner — avoids depending on random factory historis values.
    $sharedHistoris = ['C2' => 3, 'C3' => 5, 'C4' => 95, 'C5' => 95, 'C6' => 4];

    $historisCheap = \App\Models\SawNilaiHistoris::factory()->create(['supplier_id' => $supplierCheap->id_supplier]);
    seedHistorisNilai($historisCheap, $sharedHistoris);

    $historisExpensive = \App\Models\SawNilaiHistoris::factory()->create(['supplier_id' => $supplierExpensive->id_supplier]);
    seedHistorisNilai($historisExpensive, $sharedHistoris);

    foreach ([$supplierCheap->id_supplier => 100000, $supplierExpensive->id_supplier => 130000] as $supplierId => $harga) {
        $inquiry = SupplierInquiry::factory()->create([
            'needlist_id' => $needlist->id, 'supplier_id' => $supplierId, 'status' => 'responded',
        ]);
        SupplierInquiryItem::factory()->create([
            'inquiry_id' => $inquiry->id, 'id_variasi' => $variasi->id_variasi,
            'harga_penawaran' => $harga, 'estimasi_pengiriman' => now()->addDays(5)->toDateString(),
        ]);
    }

    $response = $this->actingAs($this->user)->get(route('pemilihan-supplier.show', $needlist->id));

    $response->assertStatus(200);

    $this->assertDatabaseHas('saw_perhitungan', ['needlist_id' => $needlist->id]);
    $this->assertDatabaseHas('saw_perhitungan_detail', [
        'supplier_id' => $supplierCheap->id_supplier,
        'is_recommended' => 1,
    ]);
});

it('flags sudah-dipilih-lengkap on the show page when everything is already selected', function () {
    $needlist = Needlist::factory()->create(['status' => 'selection_in_progress']);
    $variasi  = Variasi::factory()->create();
    $supplier = Supplier::factory()->create();

    $inquiry = SupplierInquiry::factory()->create(['needlist_id' => $needlist->id, 'supplier_id' => $supplier->id_supplier]);
    SupplierInquiryItem::factory()->create([
        'inquiry_id' => $inquiry->id, 'id_variasi' => $variasi->id_variasi,
        'harga_penawaran' => 100000, 'status' => 'selected',
    ]);

    $this->actingAs($this->user)
        ->get(route('pemilihan-supplier.show', $needlist->id))
        ->assertSee('data-sudah-dipilih-lengkap="1"', false);
});

it('does not flag sudah-dipilih-lengkap on the show page when nothing is selected yet', function () {
    $needlist = Needlist::factory()->create(['status' => 'selection_in_progress']);
    $variasi  = Variasi::factory()->create();
    $supplier = Supplier::factory()->create();

    $inquiry = SupplierInquiry::factory()->create(['needlist_id' => $needlist->id, 'supplier_id' => $supplier->id_supplier]);
    SupplierInquiryItem::factory()->create([
        'inquiry_id' => $inquiry->id, 'id_variasi' => $variasi->id_variasi,
        'harga_penawaran' => 100000, 'status' => 'pending',
    ]);

    $this->actingAs($this->user)
        ->get(route('pemilihan-supplier.show', $needlist->id))
        ->assertSee('data-sudah-dipilih-lengkap="0"', false);
});

it('does not auto-run the SAW batch calculation once the needlist has an issued PO', function () {
    $needlist = Needlist::factory()->create(['status' => 'po_issued']);
    $variasi = Variasi::factory()->create();

    $supplierA = Supplier::factory()->create();
    $supplierB = Supplier::factory()->create();
    \App\Models\SawNilaiHistoris::factory()->create(['supplier_id' => $supplierA->id_supplier]);
    \App\Models\SawNilaiHistoris::factory()->create(['supplier_id' => $supplierB->id_supplier]);

    foreach ([$supplierA, $supplierB] as $supplier) {
        $inquiry = SupplierInquiry::factory()->create([
            'needlist_id' => $needlist->id, 'supplier_id' => $supplier->id_supplier, 'status' => 'responded',
        ]);
        SupplierInquiryItem::factory()->create([
            'inquiry_id' => $inquiry->id, 'id_variasi' => $variasi->id_variasi,
            'harga_penawaran' => 100000, 'estimasi_pengiriman' => now()->addDays(5)->toDateString(),
        ]);
    }

    $this->actingAs($this->user)->get(route('pemilihan-supplier.show', $needlist->id))->assertStatus(200);

    $this->assertDatabaseMissing('saw_perhitungan', ['needlist_id' => $needlist->id]);
});
