<?php

use App\Models\CartNeedlist;
use App\Models\Needlist;
use App\Models\NeedlistItem;
use App\Models\Variasi;
use Tests\Feature\Procurement\Concerns\ProcurementTestHelpers;

uses(ProcurementTestHelpers::class);

// ---------------------------------------------------------------------
// storeFromCart
// ---------------------------------------------------------------------

test('storeFromCart converts the user cart into a draft needlist and clears the cart', function () {
    $user = $this->procurementUser();
    $variasiA = Variasi::factory()->create();
    $variasiB = Variasi::factory()->create();

    CartNeedlist::factory()->create(['user_id' => $user->id, 'id_variasi' => $variasiA->id_variasi, 'qty' => 4]);
    CartNeedlist::factory()->create(['user_id' => $user->id, 'id_variasi' => $variasiB->id_variasi, 'qty' => 2]);

    $response = $this->actingAs($user)->post(route('needlist.storeFromCart'));

    $needlist = Needlist::where('user_id', $user->id)->first();

    expect($needlist)->not->toBeNull();
    expect($needlist->status)->toBe('draft');
    expect($needlist->details)->toHaveCount(2);
    expect(CartNeedlist::where('user_id', $user->id)->count())->toBe(0);

    $response->assertRedirect(route('needlist.show', $needlist->id));
});

test('storeFromCart redirects back with an error when the cart is empty', function () {
    $user = $this->procurementUser();

    $response = $this->actingAs($user)->post(route('needlist.storeFromCart'));

    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect(Needlist::count())->toBe(0);
});

// ---------------------------------------------------------------------
// submit
// ---------------------------------------------------------------------

test('submit transitions a draft needlist with pending items to submitted', function () {
    $user = $this->procurementUser();
    $variasi = Variasi::factory()->create();
    $needlist = $this->needlistWithItem($user, $variasi, 5, 'draft', 'pending');

    $response = $this->actingAs($user)->post(route('needlist.submit', $needlist->id));

    $response->assertRedirect(route('needlist.show', $needlist->id));
    expect($needlist->fresh()->status)->toBe('submitted');
});

test('submit refuses a needlist with no submittable items', function () {
    $user = $this->procurementUser();
    $needlist = Needlist::factory()->create(['user_id' => $user->id, 'status' => 'draft']);

    $response = $this->actingAs($user)->post(route('needlist.submit', $needlist->id));

    $response->assertSessionHas('error');
    expect($needlist->fresh()->status)->toBe('draft');
});

test('submit 404s for a needlist that is not draft or rejected', function () {
    $user = $this->procurementUser();
    $variasi = Variasi::factory()->create();
    $needlist = $this->needlistWithItem($user, $variasi, 5, 'approved', 'approved');

    $this->actingAs($user)->post(route('needlist.submit', $needlist->id))
        ->assertNotFound();
});

// ---------------------------------------------------------------------
// destroyDetail / toggleReference
// ---------------------------------------------------------------------

test('destroyDetail removes a non-approved item', function () {
    $user = $this->procurementUser();
    $variasi = Variasi::factory()->create();
    $needlist = $this->needlistWithItem($user, $variasi, 5, 'draft', 'pending');
    $item = $needlist->details()->first();

    $this->actingAs($user)->delete(route('needlist.detail.destroy'), ['detail_id' => $item->id])
        ->assertRedirect();

    $this->assertDatabaseMissing('needlist_items', ['id' => $item->id]);
});

test('destroyDetail refuses to remove an approved item', function () {
    $user = $this->procurementUser();
    $variasi = Variasi::factory()->create();
    $needlist = $this->needlistWithItem($user, $variasi, 5, 'submitted', 'approved');
    $item = $needlist->details()->first();

    $response = $this->actingAs($user)->delete(route('needlist.detail.destroy'), ['detail_id' => $item->id]);

    $response->assertSessionHas('error');
    $this->assertDatabaseHas('needlist_items', ['id' => $item->id]);
});

test('toggleReference flips the is_reference flag', function () {
    $user = $this->procurementUser();
    $variasi = Variasi::factory()->create();
    $needlist = $this->needlistWithItem($user, $variasi, 5, 'draft', 'pending');
    $item = $needlist->details()->first();

    expect($item->is_reference)->toBeFalse();

    $response = $this->actingAs($user)->post(route('needlist.item.toggleReference', $item->id));

    $response->assertOk()->assertJson(['success' => true, 'is_reference' => true]);
    expect($item->fresh()->is_reference)->toBeTrue();

    $this->actingAs($user)->post(route('needlist.item.toggleReference', $item->id));
    expect($item->fresh()->is_reference)->toBeFalse();
});

// ---------------------------------------------------------------------
// edit / addDraftDetail / removeDraftDetail / getDraftItemsJson / update
// ---------------------------------------------------------------------

test('addDraftDetail stages a new item in the session draft for a needlist being edited', function () {
    $user = $this->procurementUser();
    $variasiExisting = Variasi::factory()->create();
    $needlist = $this->needlistWithItem($user, $variasiExisting, 3, 'draft', 'pending');
    $variasiNew = Variasi::factory()->create();

    // Initialize the edit session (route seeds session("edit_needlist_{id}"))
    $this->actingAs($user)->get(route('needlist.edit', $needlist->id))->assertOk();

    $response = $this->actingAs($user)->postJson(route('needlist.draft.add', $needlist->id), [
        'id_variasi' => $variasiNew->id_variasi,
    ]);

    $response->assertOk()->assertJson(['success' => true]);

    $json = $this->actingAs($user)->getJson(route('needlist.draft.json', $needlist->id))->json();

    expect(collect($json['draft_items'])->pluck('id_variasi'))->toContain($variasiNew->id_variasi);
});

test('removeDraftDetail removes an item from the session draft', function () {
    $user = $this->procurementUser();
    $variasi = Variasi::factory()->create();
    $needlist = $this->needlistWithItem($user, $variasi, 3, 'draft', 'pending');
    $variasiNew = Variasi::factory()->create();

    $this->actingAs($user)->get(route('needlist.edit', $needlist->id));
    $this->actingAs($user)->postJson(route('needlist.draft.add', $needlist->id), [
        'id_variasi' => $variasiNew->id_variasi,
    ]);

    $this->actingAs($user)->deleteJson(route('needlist.draft.remove', ['id' => $needlist->id, 'temp_id' => 0]), [
        'id_variasi' => $variasiNew->id_variasi,
    ])->assertOk();

    $json = $this->actingAs($user)->getJson(route('needlist.draft.json', $needlist->id))->json();

    expect(collect($json['draft_items'])->pluck('id_variasi'))->not->toContain($variasiNew->id_variasi);
});

test('addDraftDetail rejects a duplicate variasi already in the draft', function () {
    $user = $this->procurementUser();
    $variasi = Variasi::factory()->create();
    $needlist = $this->needlistWithItem($user, $variasi, 3, 'draft', 'pending');

    $this->actingAs($user)->get(route('needlist.edit', $needlist->id));

    // The existing approved-less item is seeded into session as a draft item (status pending != approved)
    $response = $this->actingAs($user)->postJson(route('needlist.draft.add', $needlist->id), [
        'id_variasi' => $variasi->id_variasi,
    ]);

    $response->assertStatus(422);
});

test('update saves qty changes for non-approved items without changing status', function () {
    $user = $this->procurementUser();
    $variasi = Variasi::factory()->create();
    $needlist = $this->needlistWithItem($user, $variasi, 3, 'draft', 'pending');
    $item = $needlist->details()->first();

    $this->actingAs($user)->get(route('needlist.edit', $needlist->id));

    $payload = [
        'action_type' => 'save',
        'temp_items_json' => json_encode([
            [
                'detail_id' => $item->id,
                'id_variasi' => $variasi->id_variasi,
                'qty' => 9,
                'status' => 'pending',
                'is_reference' => false,
            ],
        ]),
    ];

    $response = $this->actingAs($user)->put(route('needlist.update', $needlist->id), $payload);

    $response->assertRedirect(route('needlist.show', $needlist->id));
    expect($item->fresh()->qty)->toBe(9);
    expect($needlist->fresh()->status)->toBe('draft');
});

test('update with action_type submit transitions the needlist to submitted', function () {
    $user = $this->procurementUser();
    $variasi = Variasi::factory()->create();
    $needlist = $this->needlistWithItem($user, $variasi, 3, 'draft', 'pending');
    $item = $needlist->details()->first();

    $this->actingAs($user)->get(route('needlist.edit', $needlist->id));

    $payload = [
        'action_type' => 'submit',
        'temp_items_json' => json_encode([
            [
                'detail_id' => $item->id,
                'id_variasi' => $variasi->id_variasi,
                'qty' => $item->qty,
                'status' => 'pending',
                'is_reference' => false,
            ],
        ]),
    ];

    $this->actingAs($user)->put(route('needlist.update', $needlist->id), $payload);

    expect($needlist->fresh()->status)->toBe('submitted');
});

test('update on a rejected needlist reverts status to draft when just saving', function () {
    $user = $this->procurementUser();
    $variasi = Variasi::factory()->create();
    $needlist = $this->needlistWithItem($user, $variasi, 3, 'rejected', 'rejected');
    $item = $needlist->details()->first();

    $this->actingAs($user)->get(route('needlist.edit', $needlist->id));

    $payload = [
        'action_type' => 'save',
        'temp_items_json' => json_encode([
            [
                'detail_id' => $item->id,
                'id_variasi' => $variasi->id_variasi,
                'qty' => $item->qty,
                'status' => 'rejected',
                'is_reference' => false,
            ],
        ]),
    ];

    $this->actingAs($user)->put(route('needlist.update', $needlist->id), $payload);

    expect($needlist->fresh()->status)->toBe('draft');
});

// ---------------------------------------------------------------------
// Supervisor review flow: approveTemp / rejectTemp / submitReview
// ---------------------------------------------------------------------

test('submitReview approves the needlist when every item is approved', function () {
    $procurement = $this->procurementUser();
    $supervisor = $this->supervisorUser();
    $variasi = Variasi::factory()->create();
    $needlist = $this->needlistWithItem($procurement, $variasi, 5, 'submitted', 'pending');
    $item = $needlist->details()->first();

    $this->actingAs($supervisor)->post(route('needlist.approveTemp', $needlist->id), [
        'detail_id' => $item->id,
    ])->assertRedirect();

    $response = $this->actingAs($supervisor)->post(route('needlist.submitReview', $needlist->id));

    $response->assertRedirect(route('needlist.supervisorIndex'));
    $needlist->refresh();
    expect($needlist->status)->toBe('approved');
    expect($needlist->approved_by)->toBe($supervisor->id);
    expect($needlist->approved_at)->not->toBeNull();
    expect($item->fresh()->status)->toBe('approved');
});

test('submitReview rejects the whole needlist when any item is rejected', function () {
    $procurement = $this->procurementUser();
    $supervisor = $this->supervisorUser();
    $variasiA = Variasi::factory()->create();
    $variasiB = Variasi::factory()->create();
    $needlist = $this->needlistWithItem($procurement, $variasiA, 5, 'submitted', 'pending');
    NeedlistItem::factory()->create([
        'needlist_id' => $needlist->id,
        'id_variasi' => $variasiB->id_variasi,
        'qty' => 2,
        'status' => 'pending',
    ]);
    $items = $needlist->details;

    $this->actingAs($supervisor)->post(route('needlist.approveTemp', $needlist->id), [
        'detail_id' => $items[0]->id,
    ]);
    $this->actingAs($supervisor)->post(route('needlist.rejectTemp', $needlist->id), [
        'detail_id' => $items[1]->id,
        'rejected_reason' => 'Harga tidak sesuai',
    ]);

    $this->actingAs($supervisor)->post(route('needlist.submitReview', $needlist->id));

    $needlist->refresh();
    expect($needlist->status)->toBe('rejected');
    expect($items[1]->fresh()->status)->toBe('rejected');
    expect($items[1]->fresh()->rejected_reason)->toBe('Harga tidak sesuai');
});

test('submitReview refuses to finalize while items are still unreviewed', function () {
    $procurement = $this->procurementUser();
    $supervisor = $this->supervisorUser();
    $variasiA = Variasi::factory()->create();
    $variasiB = Variasi::factory()->create();
    $needlist = $this->needlistWithItem($procurement, $variasiA, 5, 'submitted', 'pending');
    NeedlistItem::factory()->create([
        'needlist_id' => $needlist->id,
        'id_variasi' => $variasiB->id_variasi,
        'qty' => 2,
        'status' => 'pending',
    ]);
    $items = $needlist->details;

    // Only review the first item, leave the second untouched.
    $this->actingAs($supervisor)->post(route('needlist.approveTemp', $needlist->id), [
        'detail_id' => $items[0]->id,
    ]);

    $response = $this->actingAs($supervisor)->post(route('needlist.submitReview', $needlist->id));

    $response->assertSessionHas('error');
    expect($needlist->fresh()->status)->toBe('submitted');
});

// ---------------------------------------------------------------------
// Read-only smoke tests
// ---------------------------------------------------------------------

test('needlist index renders', function () {
    $user = $this->procurementUser();
    Needlist::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)->get(route('needlist.index'))->assertOk();
});

test('needlist show renders full detail for an owner', function () {
    $user = $this->procurementUser();
    $variasi = Variasi::factory()->create();
    $needlist = $this->needlistWithItem($user, $variasi, 4, 'draft', 'pending');

    $this->actingAs($user)->get(route('needlist.show', $needlist->id))->assertOk();
});

test('supervisor json only lists submitted needlists', function () {
    $user = $this->procurementUser();
    $supervisor = $this->supervisorUser();
    Needlist::factory()->create(['user_id' => $user->id, 'status' => 'draft']);
    $submitted = Needlist::factory()->create(['user_id' => $user->id, 'status' => 'submitted']);

    $response = $this->actingAs($supervisor)->getJson(route('needlist.supervisorJson'));

    $response->assertOk();
    $response->assertJsonFragment(['kode_needlist' => $submitted->kode_needlist]);
    $response->assertJsonMissing(['status' => 'draft']);
});
