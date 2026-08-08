<?php

use App\Models\ItemCategorizationLog;
use App\Models\ItemDuplicateMerge;
use App\Models\MBarang;
use App\Models\Supplier;
use App\Models\SupplierVariasi;
use App\Models\User;
use App\Models\Variasi;
use App\Services\DuplicateItemDetectionService;

beforeEach(function () {
    $this->user = User::factory()->role('gudang')->create();
    $this->service = new DuplicateItemDetectionService();
});

/*
|--------------------------------------------------------------------------
| DuplicateItemDetectionService::detect() — clustering rules
|--------------------------------------------------------------------------
*/

it('clusters items whose normalized word signature and grade tag both match', function () {
    Variasi::factory()->create(['nama_variasi' => "Kampas Rem 'G' Depan XYZ", 'is_active' => true]);
    Variasi::factory()->create(['nama_variasi' => "Depan XYZ Kampas Rem 'G'", 'is_active' => true]); // same words, reordered

    $clusters = $this->service->detect(false);

    expect($clusters)->toHaveCount(1);
    expect($clusters[0])->toHaveCount(2);
});

it('never clusters items whose grade tag differs even if the rest of the name is identical', function () {
    Variasi::factory()->create(['nama_variasi' => "Kampas Rem 'G' Depan XYZ", 'is_active' => true]);
    Variasi::factory()->create(['nama_variasi' => "Kampas Rem 'B' Depan XYZ", 'is_active' => true]);

    $clusters = $this->service->detect(false);

    expect($clusters)->toHaveCount(0);
});

it('extracts the grade tag from a quoted single letter and defaults to AFTERMARKET otherwise', function () {
    expect($this->service->extractGradeTag("Kampas Rem 'G' Depan"))->toBe('G');
    expect($this->service->extractGradeTag("Kampas Rem 'b' Depan"))->toBe('B');
    expect($this->service->extractGradeTag('Kampas Rem Depan Tanpa Tag'))->toBe('AFTERMARKET');
});

/*
|--------------------------------------------------------------------------
| DuplicateItemDetectionService::merge() — stock must move exactly once,
| never lost or duplicated, and the merged item must end up inactive.
|--------------------------------------------------------------------------
*/

it('moves all stock from the merged item to the target and deactivates the merged item', function () {
    $target = Variasi::factory()->create(['nama_variasi' => "Kampas Rem 'G' Depan", 'stock' => 10, 'is_active' => true, 'tier' => null]);
    $merged = Variasi::factory()->create(['nama_variasi' => "Kampas Rem 'G' Depan Duplikat", 'stock' => 5, 'is_active' => true, 'tier' => null]);

    $result = $this->service->merge($target->id_variasi, [$merged->id_variasi], $this->user->id);

    expect((float) $result->stock)->toEqual(15.0);
    expect((float) $merged->fresh()->stock)->toEqual(0.0);
    expect($merged->fresh()->is_active)->toBeFalse();
    expect($target->fresh()->tier)->toBe('Original'); // 'G' -> Original, applied automatically

    $this->assertDatabaseHas('item_duplicate_merges', [
        'target_id_variasi' => $target->id_variasi,
        'merged_id_variasi' => $merged->id_variasi,
        'stock_moved' => 5,
    ]);
});

it('merges stock from multiple items without losing or double counting any of it', function () {
    $target = Variasi::factory()->create(['stock' => 3]);
    $mergedA = Variasi::factory()->create(['stock' => 4]);
    $mergedB = Variasi::factory()->create(['stock' => 6]);

    $result = $this->service->merge($target->id_variasi, [$mergedA->id_variasi, $mergedB->id_variasi]);

    expect((float) $result->stock)->toEqual(13.0); // 3 + 4 + 6
    expect((float) $mergedA->fresh()->stock)->toEqual(0.0);
    expect((float) $mergedB->fresh()->stock)->toEqual(0.0);
});

it('copies a merged supplier price row to the target only when the target lacks that supplier', function () {
    $target = Variasi::factory()->create();
    $merged = Variasi::factory()->create();

    $existingSupplier = Supplier::factory()->create();
    $newSupplier = Supplier::factory()->create();

    // Target already buys from $existingSupplier at 1000 — must NOT be overwritten.
    SupplierVariasi::factory()->create([
        'id_variasi' => $target->id_variasi, 'id_supplier' => $existingSupplier->id_supplier, 'harga_beli' => 1000,
    ]);
    // Merged item has a cheaper quote from the SAME existing supplier, and a quote from a new supplier.
    SupplierVariasi::factory()->create([
        'id_variasi' => $merged->id_variasi, 'id_supplier' => $existingSupplier->id_supplier, 'harga_beli' => 500,
    ]);
    SupplierVariasi::factory()->create([
        'id_variasi' => $merged->id_variasi, 'id_supplier' => $newSupplier->id_supplier, 'harga_beli' => 700,
    ]);

    $this->service->merge($target->id_variasi, [$merged->id_variasi]);

    expect(SupplierVariasi::where('id_variasi', $target->id_variasi)
        ->where('id_supplier', $existingSupplier->id_supplier)->count())->toBe(1);
    expect((int) SupplierVariasi::where('id_variasi', $target->id_variasi)
        ->where('id_supplier', $existingSupplier->id_supplier)->value('harga_beli'))->toBe(1000);

    expect(SupplierVariasi::where('id_variasi', $target->id_variasi)
        ->where('id_supplier', $newSupplier->id_supplier)->exists())->toBeTrue();
});

it('throws when no merge candidates are given', function () {
    $target = Variasi::factory()->create();

    expect(fn () => $this->service->merge($target->id_variasi, []))
        ->toThrow(InvalidArgumentException::class);
});

/*
|--------------------------------------------------------------------------
| DuplicateItemController — HTTP endpoints
|--------------------------------------------------------------------------
*/

it('lists duplicate item clusters on the index page', function () {
    Variasi::factory()->create(['nama_variasi' => "Filter Oli 'G' Model A", 'is_active' => true]);
    Variasi::factory()->create(['nama_variasi' => "Model A Filter Oli 'G'", 'is_active' => true]);

    $this->actingAs($this->user)
        ->get(route('duplikat-item.index'))
        ->assertStatus(200);
});

it('applies a merge via the terapkanGrup endpoint and moves stock end to end', function () {
    $target = Variasi::factory()->create(['stock' => 10, 'is_active' => true]);
    $merged = Variasi::factory()->create(['stock' => 5, 'is_active' => true]);

    $response = $this->actingAs($this->user)->post(route('duplikat-item.terapkan'), [
        'target_id_variasi' => $target->id_variasi,
        'merge_ids' => [$merged->id_variasi],
        'mode' => 'merge',
    ]);

    $response->assertSessionHas('success');
    expect((float) $target->fresh()->stock)->toEqual(15.0);
    expect((float) $merged->fresh()->stock)->toEqual(0.0);
    expect($merged->fresh()->is_active)->toBeFalse();
});

it('categorizes items without merging them when mode is categorize_only', function () {
    $target = Variasi::factory()->create(['stock' => 10, 'is_active' => true]);
    $other = Variasi::factory()->create(['stock' => 5, 'is_active' => true]);

    $response = $this->actingAs($this->user)->post(route('duplikat-item.terapkan'), [
        'target_id_variasi' => $target->id_variasi,
        'merge_ids' => [$other->id_variasi],
        'mode' => 'categorize_only',
        'nama_barang' => 'Kampas Rem Baru',
    ]);

    $response->assertSessionHas('success');

    // Both items stay active and keep their own stock — no merge happened.
    expect($target->fresh()->is_active)->toBeTrue();
    expect($other->fresh()->is_active)->toBeTrue();
    expect((float) $target->fresh()->stock)->toEqual(10.0);
    expect((float) $other->fresh()->stock)->toEqual(5.0);

    $mbarang = MBarang::where('nama_barang', 'Kampas Rem Baru')->first();
    expect($mbarang)->not->toBeNull();
    expect($target->fresh()->id_barang)->toBe($mbarang->id_barang);
    expect($other->fresh()->id_barang)->toBe($mbarang->id_barang);

    expect(ItemCategorizationLog::where('id_variasi', $target->id_variasi)->exists())->toBeTrue();
    expect(ItemCategorizationLog::where('id_variasi', $other->id_variasi)->exists())->toBeTrue();
});

it('shows the merge history page', function () {
    ItemDuplicateMerge::factory()->create();

    $this->actingAs($this->user)
        ->get(route('duplikat-item.riwayat'))
        ->assertStatus(200);
});

it('shows the categorization history page', function () {
    ItemCategorizationLog::factory()->create();

    $this->actingAs($this->user)
        ->get(route('duplikat-item.riwayat-kategorisasi'))
        ->assertStatus(200);
});
