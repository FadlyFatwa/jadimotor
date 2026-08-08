<?php

use App\Models\Needlist;
use App\Models\ProductVariantCompatibility;
use App\Models\SawPerhitungan;
use App\Models\SawPerhitunganDetail;
use App\Models\SawRekomendasi;
use App\Models\Supplier;
use App\Models\SupplierInquiry;
use App\Models\SupplierInquiryItem;
use App\Models\User;
use App\Models\Variasi;
use App\Models\VehicleGeneration;

beforeEach(function () {
    $this->user = User::factory()->role('procurement')->create();

    // Builds one needlist with a single item quoted by two suppliers (sharing a
    // vehicle-generation link so NeedlistSelectionGrouper clusters them into a
    // single group), plus a SawPerhitungan that recommends $recommended.
    // Bound to $this (not a global function) to avoid colliding with same-named
    // helpers other parallel test suites might define.
    $this->buildConfirmationScenario = function () {
        $needlist = Needlist::factory()->create(['status' => 'selection_in_progress']);
        $variasi = Variasi::factory()->create();

        $generation = VehicleGeneration::factory()->create();
        ProductVariantCompatibility::create([
            'id_variasi' => $variasi->id_variasi,
            'vehicle_generation_id' => $generation->id,
            'is_compatible' => true,
        ]);

        $recommended = Supplier::factory()->create();
        $other = Supplier::factory()->create();

        $inquiryRecommended = SupplierInquiry::factory()->create([
            'needlist_id' => $needlist->id, 'supplier_id' => $recommended->id_supplier, 'status' => 'responded',
        ]);
        $itemRecommended = SupplierInquiryItem::factory()->create([
            'inquiry_id' => $inquiryRecommended->id, 'id_variasi' => $variasi->id_variasi,
            'harga_penawaran' => 100000, 'estimasi_pengiriman' => now()->addDays(5)->toDateString(),
        ]);

        $inquiryOther = SupplierInquiry::factory()->create([
            'needlist_id' => $needlist->id, 'supplier_id' => $other->id_supplier, 'status' => 'responded',
        ]);
        $itemOther = SupplierInquiryItem::factory()->create([
            'inquiry_id' => $inquiryOther->id, 'id_variasi' => $variasi->id_variasi,
            'harga_penawaran' => 120000, 'estimasi_pengiriman' => now()->addDays(3)->toDateString(),
        ]);

        $perhitungan = SawPerhitungan::factory()->create([
            'needlist_id' => $needlist->id, 'id_variasi' => $variasi->id_variasi,
        ]);
        SawPerhitunganDetail::factory()->create([
            'perhitungan_id' => $perhitungan->id, 'supplier_id' => $recommended->id_supplier,
            'id_variasi' => $variasi->id_variasi, 'ranking' => 1, 'is_recommended' => 1, 'nilai_vi' => 0.9,
        ]);
        SawPerhitunganDetail::factory()->create([
            'perhitungan_id' => $perhitungan->id, 'supplier_id' => $other->id_supplier,
            'id_variasi' => $variasi->id_variasi, 'ranking' => 2, 'is_recommended' => 0, 'nilai_vi' => 0.7,
        ]);

        return compact('needlist', 'variasi', 'recommended', 'other', 'itemRecommended', 'itemOther');
    };
});

it('marks mengikuti_rekomendasi = true when the user follows the SAW recommendation', function () {
    $s = ($this->buildConfirmationScenario)();

    $response = $this->actingAs($this->user)->post(route('supplier.selection.save', $s['needlist']->id), [
        'selected_items' => [$s['itemRecommended']->id],
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect($s['itemRecommended']->fresh()->status)->toBe('selected');
    expect($s['itemOther']->fresh()->status)->toBe('pending');

    $rekomendasi = SawRekomendasi::where('needlist_id', $s['needlist']->id)
        ->where('id_variasi', $s['variasi']->id_variasi)->first();

    expect($rekomendasi)->not->toBeNull();
    expect($rekomendasi->supplier_id_saw)->toBe($s['recommended']->id_supplier);
    expect($rekomendasi->supplier_id_dipilih)->toBe($s['recommended']->id_supplier);
    expect((int) $rekomendasi->mengikuti_rekomendasi)->toBe(1);
});

it('marks mengikuti_rekomendasi = false when the user overrides the SAW recommendation', function () {
    $s = ($this->buildConfirmationScenario)();

    $this->actingAs($this->user)->post(route('supplier.selection.save', $s['needlist']->id), [
        'selected_items' => [$s['itemOther']->id],
    ]);

    $rekomendasi = SawRekomendasi::where('needlist_id', $s['needlist']->id)
        ->where('id_variasi', $s['variasi']->id_variasi)->first();

    expect($rekomendasi->supplier_id_saw)->toBe($s['recommended']->id_supplier);
    expect($rekomendasi->supplier_id_dipilih)->toBe($s['other']->id_supplier);
    expect((int) $rekomendasi->mengikuti_rekomendasi)->toBe(0);
});

it('rejects selecting more than one supplier item within the same comparison group', function () {
    $s = ($this->buildConfirmationScenario)();

    $response = $this->actingAs($this->user)->post(route('supplier.selection.save', $s['needlist']->id), [
        'selected_items' => [$s['itemRecommended']->id, $s['itemOther']->id],
    ]);

    $response->assertSessionHas('error');
    expect($s['itemRecommended']->fresh()->status)->toBe('pending');
    expect($s['itemOther']->fresh()->status)->toBe('pending');
});

it('rejects saving when a group with offers has no item selected at all', function () {
    $s = ($this->buildConfirmationScenario)();

    $response = $this->actingAs($this->user)->post(route('supplier.selection.save', $s['needlist']->id), [
        'selected_items' => [],
    ]);

    $response->assertSessionHas('error');
    expect($s['itemRecommended']->fresh()->status)->toBe('pending');
});

it('forbids changing the selection once the needlist already has an issued PO', function () {
    $s = ($this->buildConfirmationScenario)();
    $s['needlist']->update(['status' => 'po_issued']);

    $this->actingAs($this->user)->post(route('supplier.selection.save', $s['needlist']->id), [
        'selected_items' => [$s['itemRecommended']->id],
    ])->assertForbidden();
});
