<?php

use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'username' => $user->username,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    // default factory role is 'kasir', which LoginController::redirectTo() sends to /unit
    $response->assertRedirect('/unit');
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'username' => $user->username,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('inactive users can not authenticate even with correct credentials', function () {
    $user = User::factory()->create(['is_active' => false]);

    $this->post('/login', [
        'username' => $user->username,
        'password' => 'password',
    ]);

    $this->assertGuest();
});

test('redirect destination after login depends on role', function () {
    $owner = User::factory()->role('owner')->create();
    $response = $this->post('/login', [
        'username' => $owner->username,
        'password' => 'password',
    ]);
    $response->assertRedirect('/barang');

    $this->post('/logout');

    $admin = User::factory()->role('admin')->create();
    $response = $this->post('/login', [
        'username' => $admin->username,
        'password' => 'password',
    ]);
    $response->assertRedirect('/kategori');
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect(route('login'));
});

test('guest cannot logout', function () {
    $response = $this->post('/logout');

    $response->assertRedirect('/login');
});
