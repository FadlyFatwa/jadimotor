<?php

use App\Models\Cart;
use App\Models\User;
use App\Models\Variasi;

test('a user can add a variasi to their cart by id_variasi', function () {
    $user = User::factory()->create();
    $variasi = Variasi::factory()->create(['stock' => 10, 'harga_jual' => 50000]);

    $response = $this->actingAs($user)->postJson(route('penjualan.cart.add'), [
        'id_variasi' => $variasi->id_variasi,
        'nama_barang_jual' => $variasi->nama_variasi,
        'harga' => 50000,
        'qty' => 2,
        'diskon' => 0,
    ]);

    $response->assertOk();

    $this->assertDatabaseHas('carts', [
        'user_id' => $user->id,
        'id_variasi' => $variasi->id_variasi,
        'qty' => 2,
        'harga' => 50000,
    ]);
});

test('adding the same variasi to cart twice increments the existing row instead of duplicating', function () {
    $user = User::factory()->create();
    $variasi = Variasi::factory()->create(['stock' => 10, 'harga_jual' => 50000]);

    $this->actingAs($user)->postJson(route('penjualan.cart.add'), [
        'id_variasi' => $variasi->id_variasi,
        'nama_barang_jual' => $variasi->nama_variasi,
        'harga' => 50000,
        'qty' => 2,
        'diskon' => 0,
    ])->assertOk();

    $this->actingAs($user)->postJson(route('penjualan.cart.add'), [
        'id_variasi' => $variasi->id_variasi,
        'nama_barang_jual' => $variasi->nama_variasi,
        'harga' => 50000,
        'qty' => 3,
        'diskon' => 0,
    ])->assertOk();

    expect(Cart::where('user_id', $user->id)->count())->toBe(1);
    $this->assertDatabaseHas('carts', [
        'user_id' => $user->id,
        'id_variasi' => $variasi->id_variasi,
        'qty' => 5,
    ]);
});

test('adding to cart is rejected when requested qty exceeds available stock', function () {
    $user = User::factory()->create();
    $variasi = Variasi::factory()->create(['stock' => 1, 'harga_jual' => 50000]);

    $response = $this->actingAs($user)->postJson(route('penjualan.cart.add'), [
        'id_variasi' => $variasi->id_variasi,
        'nama_barang_jual' => $variasi->nama_variasi,
        'harga' => 50000,
        'qty' => 5,
        'diskon' => 0,
    ]);

    $response->assertStatus(400);
    $this->assertDatabaseMissing('carts', [
        'user_id' => $user->id,
        'id_variasi' => $variasi->id_variasi,
    ]);
});

test('a cart is scoped to the authenticated user', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    Cart::factory()->create(['user_id' => $userA->id]);
    Cart::factory()->create(['user_id' => $userA->id]);
    Cart::factory()->create(['user_id' => $userB->id]);

    $response = $this->actingAs($userA)->getJson(route('penjualan.cart.get'));

    $response->assertOk();
    expect(Cart::where('user_id', $userA->id)->count())->toBe(2);
});

test('a user can update qty and harga of their own cart item', function () {
    $user = User::factory()->create();
    $variasi = Variasi::factory()->create(['stock' => 20]);
    $cart = Cart::factory()->create([
        'user_id' => $user->id,
        'id_variasi' => $variasi->id_variasi,
        'qty' => 1,
        'harga' => 10000,
        'diskon' => 0,
    ]);

    $response = $this->actingAs($user)->putJson(route('penjualan.cart.update', $cart->id), [
        'harga' => 12000,
        'diskon' => 1000,
        'qty' => 3,
    ]);

    $response->assertOk()->assertJson(['success' => true]);

    $this->assertDatabaseHas('carts', [
        'id' => $cart->id,
        'qty' => 3,
        'harga' => 12000,
        'diskon' => 1000,
        'subtotal' => (12000 - 1000) * 3,
    ]);
});

test('a user cannot update or delete another user\'s cart item', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $cart = Cart::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($intruder)->putJson(route('penjualan.cart.update', $cart->id), [
        'harga' => 1,
        'diskon' => 0,
        'qty' => 1,
    ])->assertStatus(403);

    $this->actingAs($intruder)->deleteJson(route('penjualan.cart.destroy', $cart->id))
        ->assertStatus(403);

    $this->assertDatabaseHas('carts', ['id' => $cart->id]);
});

test('a user can remove a single item from their cart', function () {
    $user = User::factory()->create();
    $cart = Cart::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->deleteJson(route('penjualan.cart.destroy', $cart->id));

    $response->assertOk()->assertJson(['success' => true]);
    $this->assertDatabaseMissing('carts', ['id' => $cart->id]);
});

test('a user can clear their entire cart', function () {
    $user = User::factory()->create();
    Cart::factory()->count(3)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->deleteJson(route('penjualan.cart.clear'));

    $response->assertOk();
    expect(Cart::where('user_id', $user->id)->count())->toBe(0);
});

test('cart routes require authentication', function () {
    $this->getJson(route('penjualan.cart.get'))->assertStatus(401);
});
