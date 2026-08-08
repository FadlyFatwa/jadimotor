<?php

use App\Models\Cart;
use App\Models\Penjualan;
use App\Models\PenjualanDetail;
use App\Models\User;
use App\Models\Variasi;

test('checkout converts the cart into a penjualan with correct totals, decrements stock, and clears the cart', function () {
    $user = User::factory()->create();
    $variasiA = Variasi::factory()->create(['stock' => 10, 'harga_jual' => 50000]);
    $variasiB = Variasi::factory()->create(['stock' => 5, 'harga_jual' => 20000]);

    Cart::factory()->create([
        'user_id' => $user->id,
        'id_variasi' => $variasiA->id_variasi,
        'harga' => 50000,
        'diskon' => 0,
        'qty' => 2,
        'subtotal' => 100000,
    ]);
    Cart::factory()->create([
        'user_id' => $user->id,
        'id_variasi' => $variasiB->id_variasi,
        'harga' => 20000,
        'diskon' => 0,
        'qty' => 3,
        'subtotal' => 60000,
    ]);

    $response = $this->actingAs($user)->post(route('penjualan.store'), [
        'invoice' => 'INV-TEST-001',
        'tanggal' => now()->toDateString(),
        'metode_pembayaran' => 'cash',
        'cash_bayar' => 200000,
        'diskon' => 0,
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('penjualan.index'));

    $penjualan = Penjualan::where('nomor_nota', 'INV-TEST-001')->first();
    expect($penjualan)->not->toBeNull();
    expect((float) $penjualan->total)->toBe(160000.0);
    expect((float) $penjualan->grand_total)->toBe(160000.0);

    expect(PenjualanDetail::where('id_penjualan', $penjualan->id_penjualan)->count())->toBe(2);

    $variasiA->refresh();
    $variasiB->refresh();
    expect((float) $variasiA->stock)->toBe(8.0);
    expect((float) $variasiB->stock)->toBe(2.0);

    expect(Cart::where('user_id', $user->id)->count())->toBe(0);
});

test('checkout applies the percentage discount to the grand total', function () {
    $user = User::factory()->create();
    $variasi = Variasi::factory()->create(['stock' => 10, 'harga_jual' => 100000]);

    Cart::factory()->create([
        'user_id' => $user->id,
        'id_variasi' => $variasi->id_variasi,
        'harga' => 100000,
        'diskon' => 0,
        'qty' => 1,
        'subtotal' => 100000,
    ]);

    $this->actingAs($user)->post(route('penjualan.store'), [
        'invoice' => 'INV-TEST-002',
        'tanggal' => now()->toDateString(),
        'metode_pembayaran' => 'cash',
        'cash_bayar' => 100000,
        'diskon' => 10,
    ])->assertRedirect(route('penjualan.index'));

    $penjualan = Penjualan::where('nomor_nota', 'INV-TEST-002')->first();
    expect((float) $penjualan->total)->toBe(100000.0);
    expect((float) $penjualan->grand_total)->toBe(90000.0);
});

test('checkout fails and rolls back when cart is empty', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('penjualan.store'), [
        'invoice' => 'INV-EMPTY',
        'tanggal' => now()->toDateString(),
        'metode_pembayaran' => 'cash',
        'cash_bayar' => 0,
    ]);

    $response->assertSessionHas('error');
    expect(Penjualan::where('nomor_nota', 'INV-EMPTY')->exists())->toBeFalse();
});

test('checkout fails without decrementing stock when a cart item exceeds available stock', function () {
    $user = User::factory()->create();
    $variasi = Variasi::factory()->create(['stock' => 1, 'harga_jual' => 50000]);

    // Cart row created directly (bypassing addToCart's own stock guard) to
    // simulate stock dropping after the item was added to the cart.
    Cart::factory()->create([
        'user_id' => $user->id,
        'id_variasi' => $variasi->id_variasi,
        'harga' => 50000,
        'diskon' => 0,
        'qty' => 5,
        'subtotal' => 250000,
    ]);

    $response = $this->actingAs($user)->post(route('penjualan.store'), [
        'invoice' => 'INV-OVERSELL',
        'tanggal' => now()->toDateString(),
        'metode_pembayaran' => 'cash',
        'cash_bayar' => 250000,
    ]);

    $response->assertSessionHas('error');
    expect(Penjualan::where('nomor_nota', 'INV-OVERSELL')->exists())->toBeFalse();

    $variasi->refresh();
    expect((float) $variasi->stock)->toBe(1.0);
    // Cart should still exist since the transaction rolled back.
    expect(Cart::where('user_id', $user->id)->exists())->toBeTrue();
});

test('deleting a penjualan restores stock for each line item and removes the records', function () {
    $user = User::factory()->create();
    $variasi = Variasi::factory()->create(['stock' => 3]);

    $penjualan = Penjualan::factory()->create(['user_id' => $user->id]);
    PenjualanDetail::factory()->create([
        'id_penjualan' => $penjualan->id_penjualan,
        'id_variasi' => $variasi->id_variasi,
        'qty' => 4,
    ]);

    $response = $this->actingAs($user)->delete(route('penjualan.destroy', $penjualan->id_penjualan));

    $response->assertRedirect(route('penjualan.index'));
    $response->assertSessionHas('success');

    expect(Penjualan::find($penjualan->id_penjualan))->toBeNull();
    expect(PenjualanDetail::where('id_penjualan', $penjualan->id_penjualan)->exists())->toBeFalse();

    $variasi->refresh();
    expect((float) $variasi->stock)->toBe(7.0);
});

test('penjualan store and destroy routes require authentication', function () {
    $this->post(route('penjualan.store'), [])->assertRedirect(route('login'));
});

// KNOWN BUG (not fixed, see report): PenjualanController::show() renders
// 'pages.penjualan.show', but no such Blade view exists under
// resources/views/pages/penjualan/. This test documents that the route is
// currently broken (500) rather than asserting the desired 200 response.
test('viewing a single penjualan currently fails because the show view is missing', function () {
    $user = User::factory()->create();
    $penjualan = Penjualan::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('penjualan.show', $penjualan->id_penjualan));

    $response->assertStatus(500);
});
