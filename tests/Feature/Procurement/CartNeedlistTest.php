<?php

use App\Models\CartNeedlist;
use App\Models\Supplier;
use App\Models\SupplierVariasi;
use App\Models\User;
use App\Models\Variasi;
use Tests\Feature\Procurement\Concerns\ProcurementTestHelpers;

uses(ProcurementTestHelpers::class);

test('cart index only shows the authenticated user\'s cart items', function () {
    $user = $this->procurementUser();
    $otherUser = $this->procurementUser();

    $variasi = Variasi::factory()->create();
    CartNeedlist::factory()->create(['user_id' => $user->id, 'id_variasi' => $variasi->id_variasi]);
    CartNeedlist::factory()->create(['user_id' => $otherUser->id, 'id_variasi' => $variasi->id_variasi]);

    $response = $this->actingAs($user)->get(route('cart.index'));

    $response->assertOk();
    $response->assertViewHas('cartItems', function ($cartItems) use ($user) {
        return $cartItems->count() === 1 && $cartItems->first()->user_id === $user->id;
    });
});

test('storing a new item creates a cart_needlists row for the user', function () {
    $user = $this->procurementUser();
    $variasi = Variasi::factory()->create();

    $response = $this->actingAs($user)->postJson(route('cart.store'), [
        'id_variasi' => $variasi->id_variasi,
        'qty' => 3,
    ]);

    $response->assertOk()->assertJson(['status' => 'success']);

    $this->assertDatabaseHas('cart_needlists', [
        'user_id' => $user->id,
        'id_variasi' => $variasi->id_variasi,
        'qty' => 3,
    ]);
});

test('storing an item already in the cart increments its qty instead of duplicating', function () {
    $user = $this->procurementUser();
    $variasi = Variasi::factory()->create();

    CartNeedlist::factory()->create([
        'user_id' => $user->id,
        'id_variasi' => $variasi->id_variasi,
        'qty' => 2,
    ]);

    $this->actingAs($user)->postJson(route('cart.store'), [
        'id_variasi' => $variasi->id_variasi,
        'qty' => 5,
    ])->assertOk();

    expect(CartNeedlist::where('user_id', $user->id)->where('id_variasi', $variasi->id_variasi)->count())->toBe(1);
    $this->assertDatabaseHas('cart_needlists', [
        'user_id' => $user->id,
        'id_variasi' => $variasi->id_variasi,
        'qty' => 7,
    ]);
});

test('storing without required fields fails validation', function () {
    $user = $this->procurementUser();

    $this->actingAs($user)->postJson(route('cart.store'), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['id_variasi', 'qty']);
});

test('destroy removes a cart item owned by the current user', function () {
    $user = $this->procurementUser();
    $variasi = Variasi::factory()->create();
    $item = CartNeedlist::factory()->create(['user_id' => $user->id, 'id_variasi' => $variasi->id_variasi]);

    $response = $this->actingAs($user)->deleteJson(route('cart.destroy', $item->id));

    $response->assertOk()->assertJson(['status' => 'deleted']);
    $this->assertDatabaseMissing('cart_needlists', ['id' => $item->id]);
});

test('destroy does not remove a cart item owned by another user', function () {
    $owner = $this->procurementUser();
    $intruder = $this->procurementUser();
    $variasi = Variasi::factory()->create();
    $item = CartNeedlist::factory()->create(['user_id' => $owner->id, 'id_variasi' => $variasi->id_variasi]);

    $this->actingAs($intruder)->deleteJson(route('cart.destroy', $item->id));

    $this->assertDatabaseHas('cart_needlists', ['id' => $item->id]);
});

test('ajax barang variasi datatable returns supplier variasi rows', function () {
    $user = $this->procurementUser();
    [$variasi, $supplier] = $this->variasiWithSupplier();

    $response = $this->actingAs($user)->getJson(route('ajax.barangVariasi'));

    $response->assertOk();
    $response->assertJsonFragment(['nama_supplier' => $supplier->nama_supplier]);
});
