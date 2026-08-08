<?php

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleGeneration;

test('guest is redirected to login for all kendaraan.generasi routes', function () {
    $vehicle = Vehicle::factory()->create();
    $generation = VehicleGeneration::factory()->create(['vehicle_id' => $vehicle->id]);

    $this->get(route('kendaraan.generasi.index', $vehicle))->assertRedirect(route('login'));
    $this->get(route('kendaraan.generasi.create', $vehicle))->assertRedirect(route('login'));
    $this->post(route('kendaraan.generasi.store', $vehicle))->assertRedirect(route('login'));
    $this->get(route('kendaraan.generasi.edit', [$vehicle, $generation]))->assertRedirect(route('login'));
    $this->put(route('kendaraan.generasi.update', [$vehicle, $generation]))->assertRedirect(route('login'));
    $this->delete(route('kendaraan.generasi.destroy', [$vehicle, $generation]))->assertRedirect(route('login'));
});

test('authenticated user can view generasi index, create and edit pages', function () {
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->create();
    $generation = VehicleGeneration::factory()->create(['vehicle_id' => $vehicle->id]);

    $this->actingAs($user)->get(route('kendaraan.generasi.index', $vehicle))->assertOk();
    $this->actingAs($user)->get(route('kendaraan.generasi.create', $vehicle))->assertOk();
    $this->actingAs($user)->get(route('kendaraan.generasi.edit', [$vehicle, $generation]))->assertOk();
});

test('storing a generasi with valid data creates a row scoped to the vehicle and redirects', function () {
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->create();

    $response = $this->actingAs($user)->post(route('kendaraan.generasi.store', $vehicle), [
        'code' => 'GEN-A',
        'nickname' => 'Generasi Pertama',
        'start_year' => 2015,
        'end_year' => 2020,
    ]);

    $response->assertRedirect(route('kendaraan.generasi.index', $vehicle));
    $this->assertDatabaseHas('vehicle_generations', [
        'vehicle_id' => $vehicle->id,
        'code' => 'GEN-A',
    ]);
});

test('storing a generasi with missing required code fails validation', function () {
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->create();

    $response = $this->actingAs($user)->post(route('kendaraan.generasi.store', $vehicle), [
        'code' => '',
    ]);

    $response->assertSessionHasErrors('code');
    $this->assertDatabaseCount('vehicle_generations', 0);
});

test('storing a generasi with end_year before start_year fails validation', function () {
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->create();

    $response = $this->actingAs($user)->post(route('kendaraan.generasi.store', $vehicle), [
        'code' => 'GEN-B',
        'start_year' => 2020,
        'end_year' => 2010,
    ]);

    $response->assertSessionHasErrors('end_year');
});

test('updating a generasi persists changes', function () {
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->create();
    $generation = VehicleGeneration::factory()->create(['vehicle_id' => $vehicle->id]);

    $response = $this->actingAs($user)->put(route('kendaraan.generasi.update', [$vehicle, $generation]), [
        'code' => 'GEN-UPDATED',
        'nickname' => $generation->nickname,
        'start_year' => $generation->start_year,
        'end_year' => $generation->end_year,
    ]);

    $response->assertRedirect(route('kendaraan.generasi.index', $vehicle));
    $this->assertDatabaseHas('vehicle_generations', [
        'id' => $generation->id,
        'code' => 'GEN-UPDATED',
    ]);
});

test('destroying a generasi removes the row', function () {
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->create();
    $generation = VehicleGeneration::factory()->create(['vehicle_id' => $vehicle->id]);

    $response = $this->actingAs($user)->delete(route('kendaraan.generasi.destroy', [$vehicle, $generation]));

    $response->assertRedirect(route('kendaraan.generasi.index', $vehicle));
    $this->assertDatabaseMissing('vehicle_generations', ['id' => $generation->id]);
});

// --- Bug found and fixed in this task ---
// The nested routes /kendaraan/{kendaraan}/generasi/{generasi}/... did not verify that
// {generasi} actually belongs to {kendaraan}. Because Laravel resolves {generasi} via
// plain implicit binding (VehicleGeneration::findOrFail), a URL mixing a valid vehicle
// id with a *different* vehicle's generation id would still resolve, letting you edit,
// update or delete a generation "through" the wrong vehicle. Fixed by adding an
// abort_unless($generasi->vehicle_id === $kendaraan->id, 404) guard to edit/update/destroy.
test('editing a generasi through the wrong vehicle 404s instead of silently succeeding', function () {
    $user = User::factory()->create();
    $vehicleA = Vehicle::factory()->create();
    $vehicleB = Vehicle::factory()->create();
    $generationOfB = VehicleGeneration::factory()->create(['vehicle_id' => $vehicleB->id]);

    $this->actingAs($user)
        ->get(route('kendaraan.generasi.edit', [$vehicleA, $generationOfB]))
        ->assertStatus(404);
});

test('updating a generasi through the wrong vehicle 404s and does not persist changes', function () {
    $user = User::factory()->create();
    $vehicleA = Vehicle::factory()->create();
    $vehicleB = Vehicle::factory()->create();
    $generationOfB = VehicleGeneration::factory()->create(['vehicle_id' => $vehicleB->id, 'code' => 'ORIGINAL']);

    $this->actingAs($user)
        ->put(route('kendaraan.generasi.update', [$vehicleA, $generationOfB]), ['code' => 'HIJACKED'])
        ->assertStatus(404);

    $this->assertDatabaseHas('vehicle_generations', ['id' => $generationOfB->id, 'code' => 'ORIGINAL']);
});

test('destroying a generasi through the wrong vehicle 404s and does not delete it', function () {
    $user = User::factory()->create();
    $vehicleA = Vehicle::factory()->create();
    $vehicleB = Vehicle::factory()->create();
    $generationOfB = VehicleGeneration::factory()->create(['vehicle_id' => $vehicleB->id]);

    $this->actingAs($user)
        ->delete(route('kendaraan.generasi.destroy', [$vehicleA, $generationOfB]))
        ->assertStatus(404);

    $this->assertDatabaseHas('vehicle_generations', ['id' => $generationOfB->id]);
});
