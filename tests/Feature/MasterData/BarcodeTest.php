<?php

use App\Models\User;

$barcodeRoutes = [
    'barcode.print.multiple',
    'barcode.print.template',
    'barcode.print.template.101',
    'barcode.print.template.fanbelt',
];

test('guest is redirected to login for all barcode print routes', function () use ($barcodeRoutes) {
    foreach ($barcodeRoutes as $name) {
        $this->get(route($name))->assertRedirect(route('login'));
    }
});

test('barcode print routes abort with 400 when no ids are supplied', function () use ($barcodeRoutes) {
    $user = User::factory()->create();

    foreach ($barcodeRoutes as $name) {
        $this->actingAs($user)->get(route($name))->assertStatus(400);
    }
});

test('barcode print routes abort with 404 when ids do not match any detail_penerimaan', function () use ($barcodeRoutes) {
    $user = User::factory()->create();

    foreach ($barcodeRoutes as $name) {
        $this->actingAs($user)->get(route($name, ['ids' => '1,2,3']))->assertStatus(404);
    }
});

// --- Known application bug, documented but not fixed (out of scope: see report) ---
// DetailPenerimaan::barang() (app/Models/DetailPenerimaan.php) is a belongsTo relation
// to App\Models\Barang, but that model class does not exist anywhere in app/Models.
// As soon as a barcode print route finds at least one matching DetailPenerimaan row,
// every foreach loop below calls $detail->barang, which throws
// "Class \"App\Models\Barang\" not found". This means the happy path of ALL FOUR
// barcode print endpoints is completely broken today. This bug lives in the
// Penerimaan/Procurement domain (DetailPenerimaan model, Barang model), which is
// outside this workstream's scope (master-data/auth), so it is left unfixed and
// reported for the Procurement workstream / a follow-up fix.
