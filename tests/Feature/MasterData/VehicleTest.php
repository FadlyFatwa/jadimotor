<?php

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleGeneration;

test('guest is redirected to login for all kendaraan routes', function () {
    $vehicle = Vehicle::factory()->create();

    $this->get(route('kendaraan.index'))->assertRedirect(route('login'));
    $this->get(route('kendaraan.create'))->assertRedirect(route('login'));
    $this->post(route('kendaraan.store'))->assertRedirect(route('login'));
    $this->get(route('kendaraan.edit', $vehicle))->assertRedirect(route('login'));
    $this->put(route('kendaraan.update', $vehicle))->assertRedirect(route('login'));
    $this->delete(route('kendaraan.destroy', $vehicle))->assertRedirect(route('login'));
});

test('authenticated user can view kendaraan index, create and edit pages', function () {
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->create();

    $this->actingAs($user)->get(route('kendaraan.index'))->assertOk();
    $this->actingAs($user)->get(route('kendaraan.create'))->assertOk();
    $this->actingAs($user)->get(route('kendaraan.edit', $vehicle))->assertOk();
});

test('the {kendaraan} route parameter correctly resolves via implicit model binding', function () {
    // Checks the specific concern flagged in the task: the route uses {kendaraan}
    // (not {vehicle}), and VehicleController type-hints Vehicle $kendaraan — the
    // names match, so implicit binding works correctly. This is NOT a bug.
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->create(['name' => 'Avanza']);

    $response = $this->actingAs($user)->get(route('kendaraan.edit', $vehicle));

    $response->assertOk();
    // A bogus id should 404 rather than silently resolving to some other model.
    $this->actingAs($user)->get('/kendaraan/999999/edit')->assertStatus(404);
});

test('storing a kendaraan with valid data creates a row and redirects', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('kendaraan.store'), [
        'name' => 'Xenia',
        'manufacturer' => 'Daihatsu',
    ]);

    $response->assertRedirect(route('kendaraan.index'));
    $this->assertDatabaseHas('vehicles', [
        'name' => 'Xenia',
        'manufacturer' => 'Daihatsu',
    ]);
});

test('storing a kendaraan with missing required fields fails validation', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('kendaraan.store'), [
        'name' => '',
        'manufacturer' => '',
    ]);

    $response->assertSessionHasErrors(['name', 'manufacturer']);
    $this->assertDatabaseCount('vehicles', 0);
});

test('updating a kendaraan persists changes', function () {
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->create();

    $response = $this->actingAs($user)->put(route('kendaraan.update', $vehicle), [
        'name' => 'Nama Baru',
        'manufacturer' => $vehicle->manufacturer,
    ]);

    $response->assertRedirect(route('kendaraan.index'));
    $this->assertDatabaseHas('vehicles', [
        'id' => $vehicle->id,
        'name' => 'Nama Baru',
    ]);
});

test('destroying a kendaraan removes the row and cascades to its generations', function () {
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->create();
    $generation = VehicleGeneration::factory()->create(['vehicle_id' => $vehicle->id]);

    $response = $this->actingAs($user)->delete(route('kendaraan.destroy', $vehicle));

    $response->assertRedirect(route('kendaraan.index'));
    $this->assertDatabaseMissing('vehicles', ['id' => $vehicle->id]);
    $this->assertDatabaseMissing('vehicle_generations', ['id' => $generation->id]);
});
