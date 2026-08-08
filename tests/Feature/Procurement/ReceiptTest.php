<?php

use App\Models\Needlist;
use App\Models\PurchaseOrder;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\Variasi;
use Tests\Feature\Procurement\Concerns\ProcurementTestHelpers;

uses(ProcurementTestHelpers::class);

test('store creates a receipt, increments qty_received and stock, and marks the PO partial_received', function () {
    $user = $this->procurementUser();
    $stockBefore = 20;
    $data = $this->poReadyForReceipt($user, qtyOrder: 10, harga: 50000);
    $data['variasi']->update(['stock' => $stockBefore]);

    $response = $this->actingAs($user)->post(route('receipts.store', $data['po']->id), [
        'tanggal_terima' => now()->format('Y-m-d'),
        'items' => [
            $data['poItem']->id => ['qty_received' => 4],
        ],
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseCount('receipts', 1);
    $poItem = $data['poItem']->fresh();
    expect($poItem->qty_received)->toBe(4);
    expect($data['variasi']->fresh()->stock)->toEqualWithDelta($stockBefore + 4, 0.001);
    expect($data['po']->fresh()->status)->toBe('partial_received');
    expect($data['needlist']->fresh()->status)->not->toBe('completed');
});

test('store marks the PO completed and the needlist completed when fully received in one go', function () {
    $user = $this->procurementUser();
    $data = $this->poReadyForReceipt($user, qtyOrder: 10, harga: 50000);

    $this->actingAs($user)->post(route('receipts.store', $data['po']->id), [
        'tanggal_terima' => now()->format('Y-m-d'),
        'items' => [
            $data['poItem']->id => ['qty_received' => 10],
        ],
    ]);

    $po = $data['po']->fresh();
    expect($po->status)->toBe('completed');
    expect($po->closed_at)->not->toBeNull();
    expect($data['needlist']->fresh()->status)->toBe('completed');
});

test('store accumulates across multiple partial receipts until the PO is completed', function () {
    $user = $this->procurementUser();
    $data = $this->poReadyForReceipt($user, qtyOrder: 10, harga: 50000);

    $this->actingAs($user)->post(route('receipts.store', $data['po']->id), [
        'tanggal_terima' => now()->format('Y-m-d'),
        'items' => [$data['poItem']->id => ['qty_received' => 6]],
    ]);
    expect($data['po']->fresh()->status)->toBe('partial_received');

    $this->actingAs($user)->post(route('receipts.store', $data['po']->id), [
        'tanggal_terima' => now()->format('Y-m-d'),
        'items' => [$data['poItem']->id => ['qty_received' => 4]],
    ]);

    expect($data['poItem']->fresh()->qty_received)->toBe(10);
    expect($data['po']->fresh()->status)->toBe('completed');
});

test('store rejects a qty_received that exceeds the remaining outstanding qty', function () {
    $user = $this->procurementUser();
    $data = $this->poReadyForReceipt($user, qtyOrder: 10, harga: 50000);

    $response = $this->actingAs($user)->post(route('receipts.store', $data['po']->id), [
        'tanggal_terima' => now()->format('Y-m-d'),
        'items' => [$data['poItem']->id => ['qty_received' => 11]],
    ]);

    $response->assertSessionHasErrors();
    $this->assertDatabaseCount('receipts', 0);
    expect($data['poItem']->fresh()->qty_received)->toBe(0);
});

test('store rejects a second receipt whose qty would push the total over qty_order', function () {
    $user = $this->procurementUser();
    $data = $this->poReadyForReceipt($user, qtyOrder: 10, harga: 50000);

    $this->actingAs($user)->post(route('receipts.store', $data['po']->id), [
        'tanggal_terima' => now()->format('Y-m-d'),
        'items' => [$data['poItem']->id => ['qty_received' => 7]],
    ]);

    $response = $this->actingAs($user)->post(route('receipts.store', $data['po']->id), [
        'tanggal_terima' => now()->format('Y-m-d'),
        'items' => [$data['poItem']->id => ['qty_received' => 5]],
    ]);

    $response->assertSessionHasErrors();
    expect($data['poItem']->fresh()->qty_received)->toBe(7);
});

test('store requires at least one item with qty > 0', function () {
    $user = $this->procurementUser();
    $data = $this->poReadyForReceipt($user, qtyOrder: 10);

    $response = $this->actingAs($user)->post(route('receipts.store', $data['po']->id), [
        'tanggal_terima' => now()->format('Y-m-d'),
        'items' => [$data['poItem']->id => ['qty_received' => 0]],
    ]);

    $response->assertSessionHas('error');
    $this->assertDatabaseCount('receipts', 0);
});

test('store refuses to add a receipt to an already completed PO', function () {
    $user = $this->procurementUser();
    $data = $this->poReadyForReceipt($user, qtyOrder: 10);
    $data['po']->update(['status' => 'completed']);

    $response = $this->actingAs($user)->post(route('receipts.store', $data['po']->id), [
        'tanggal_terima' => now()->format('Y-m-d'),
        'items' => [$data['poItem']->id => ['qty_received' => 1]],
    ]);

    $response->assertSessionHas('error');
    $this->assertDatabaseCount('receipts', 0);
});

// ---------------------------------------------------------------------
// tutup (force close)
// ---------------------------------------------------------------------

test('tutup force-closes a partial_received PO and completes the needlist when it is the only PO', function () {
    $user = $this->procurementUser();
    $data = $this->poReadyForReceipt($user, qtyOrder: 10);
    $data['po']->update(['status' => 'partial_received']);

    $response = $this->actingAs($user)->post(route('receipts.tutup', $data['po']->id), [
        'catatan_tutup' => 'Sisa barang tidak tersedia dari supplier.',
    ]);

    $response->assertRedirect(route('receipts.index'));

    $po = $data['po']->fresh();
    expect($po->status)->toBe('completed');
    expect($po->is_force_closed)->toBeTrue();
    expect($po->closed_at)->not->toBeNull();
    expect($po->catatan_tutup)->toBe('Sisa barang tidak tersedia dari supplier.');
    expect($data['needlist']->fresh()->status)->toBe('completed');
});

test('tutup refuses a PO that is not currently partial_received', function () {
    $user = $this->procurementUser();
    $data = $this->poReadyForReceipt($user, qtyOrder: 10);
    // status defaults to 'open'

    $response = $this->actingAs($user)->post(route('receipts.tutup', $data['po']->id), [
        'catatan_tutup' => 'Coba tutup paksa dari status open.',
    ]);

    $response->assertSessionHas('error');
    expect($data['po']->fresh()->status)->toBe('open');
});

test('tutup requires a catatan_tutup', function () {
    $user = $this->procurementUser();
    $data = $this->poReadyForReceipt($user, qtyOrder: 10);
    $data['po']->update(['status' => 'partial_received']);

    $response = $this->actingAs($user)->post(route('receipts.tutup', $data['po']->id), []);

    $response->assertSessionHasErrors('catatan_tutup');
    expect($data['po']->fresh()->status)->toBe('partial_received');
});

// ---------------------------------------------------------------------
// Read-only smoke tests
// ---------------------------------------------------------------------

test('receipts index lists open and partial_received purchase orders', function () {
    $user = $this->procurementUser();
    $data = $this->poReadyForReceipt($user, qtyOrder: 10);

    $this->actingAs($user)->get(route('receipts.index'))->assertOk();
});

test('receipts create renders for an open PO', function () {
    $user = $this->procurementUser();
    $data = $this->poReadyForReceipt($user, qtyOrder: 10);

    $this->actingAs($user)->get(route('receipts.create', $data['po']->id))->assertOk();
});

test('receipts create redirects away for a completed PO', function () {
    $user = $this->procurementUser();
    $data = $this->poReadyForReceipt($user, qtyOrder: 10);
    $data['po']->update(['status' => 'completed']);

    $this->actingAs($user)->get(route('receipts.create', $data['po']->id))
        ->assertRedirect(route('receipts.index'));
});

test('receipts show renders a receipt detail', function () {
    $user = $this->procurementUser();
    $data = $this->poReadyForReceipt($user, qtyOrder: 10);

    $this->actingAs($user)->post(route('receipts.store', $data['po']->id), [
        'tanggal_terima' => now()->format('Y-m-d'),
        'items' => [$data['poItem']->id => ['qty_received' => 3]],
    ]);

    $receipt = Receipt::where('purchase_order_id', $data['po']->id)->first();

    $this->actingAs($user)->get(route('receipts.show', $receipt->id))->assertOk();
});
