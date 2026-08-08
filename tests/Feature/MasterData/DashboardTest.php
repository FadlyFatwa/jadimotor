<?php

use App\Models\User;

test('guest is redirected to login when visiting the dashboard', function () {
    $this->get('/dashboard')->assertRedirect(route('login'));
});

test('authenticated user can view the dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/dashboard')->assertOk();
});
