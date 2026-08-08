<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('guest is redirected to login for all users routes', function () {
    $target = User::factory()->create();

    $this->get(route('users.index'))->assertRedirect(route('login'));
    $this->get(route('users.create'))->assertRedirect(route('login'));
    $this->post(route('users.store'))->assertRedirect(route('login'));
    $this->get(route('users.edit', $target))->assertRedirect(route('login'));
    $this->put(route('users.update', $target))->assertRedirect(route('login'));
    $this->delete(route('users.destroy', $target))->assertRedirect(route('login'));
});

test('owner can access user management pages', function () {
    $owner = User::factory()->role('owner')->create();
    $target = User::factory()->create();

    $this->actingAs($owner)->get(route('users.index'))->assertOk();
    $this->actingAs($owner)->get(route('users.create'))->assertOk();
    $this->actingAs($owner)->get(route('users.edit', $target))->assertOk();
});

test('admin can access user management pages', function () {
    $admin = User::factory()->role('admin')->create();
    $target = User::factory()->create();

    $this->actingAs($admin)->get(route('users.index'))->assertOk();
    $this->actingAs($admin)->get(route('users.create'))->assertOk();
    $this->actingAs($admin)->get(route('users.edit', $target))->assertOk();
});

test('non admin_or_owner roles get 403 on user management pages', function (string $role) {
    $user = User::factory()->role($role)->create();
    $target = User::factory()->create();

    $this->actingAs($user)->get(route('users.index'))->assertForbidden();
    $this->actingAs($user)->get(route('users.create'))->assertForbidden();
    $this->actingAs($user)->get(route('users.edit', $target))->assertForbidden();
    $this->actingAs($user)->post(route('users.store'))->assertForbidden();
    $this->actingAs($user)->put(route('users.update', $target))->assertForbidden();
    $this->actingAs($user)->delete(route('users.destroy', $target))->assertForbidden();
})->with(['kasir', 'gudang', 'akuntan', 'procurement', 'supervisor']);

test('owner can create a new user with hashed password and assigned role', function () {
    $owner = User::factory()->role('owner')->create();

    $response = $this->actingAs($owner)->post(route('users.store'), [
        'name' => 'Staff Baru',
        'username' => 'staffbaru',
        'email' => 'staffbaru@example.com',
        'role' => 'gudang',
        'password' => 'secret123',
        'is_active' => true,
    ]);

    $response->assertRedirect(route('users.index'));
    $this->assertDatabaseHas('users', [
        'username' => 'staffbaru',
        'email' => 'staffbaru@example.com',
        'role' => 'gudang',
    ]);

    $created = User::where('username', 'staffbaru')->first();
    expect(Hash::check('secret123', $created->password))->toBeTrue();
});

test('storing a user with missing required fields fails validation', function () {
    $owner = User::factory()->role('owner')->create();

    $response = $this->actingAs($owner)->post(route('users.store'), [
        'name' => '',
        'username' => '',
        'email' => '',
        'role' => '',
        'password' => '',
    ]);

    $response->assertSessionHasErrors(['name', 'username', 'email', 'role', 'password']);
});

test('storing a user with an invalid role fails validation', function () {
    $owner = User::factory()->role('owner')->create();

    $response = $this->actingAs($owner)->post(route('users.store'), [
        'name' => 'Test',
        'username' => 'testuser2',
        'email' => 'testuser2@example.com',
        'role' => 'superadmin',
        'password' => 'secret123',
    ]);

    $response->assertSessionHasErrors('role');
});

test('storing a user with duplicate username or email fails validation', function () {
    $owner = User::factory()->role('owner')->create();
    User::factory()->create(['username' => 'existing', 'email' => 'existing@example.com']);

    $response = $this->actingAs($owner)->post(route('users.store'), [
        'name' => 'Test',
        'username' => 'existing',
        'email' => 'existing@example.com',
        'role' => 'kasir',
        'password' => 'secret123',
    ]);

    $response->assertSessionHasErrors(['username', 'email']);
});

test('updating a user persists changes and can update password', function () {
    $owner = User::factory()->role('owner')->create();
    $target = User::factory()->create();
    $oldPasswordHash = $target->password;

    $response = $this->actingAs($owner)->put(route('users.update', $target), [
        'name' => 'Nama Diupdate',
        'username' => $target->username,
        'email' => $target->email,
        'role' => 'akuntan',
        'password' => 'newpassword123',
    ]);

    $response->assertRedirect(route('users.index'));
    $target->refresh();
    expect($target->name)->toBe('Nama Diupdate');
    expect($target->role)->toBe('akuntan');
    expect($target->password)->not->toBe($oldPasswordHash);
    expect(Hash::check('newpassword123', $target->password))->toBeTrue();
});

test('updating a user without a password keeps the existing password', function () {
    $owner = User::factory()->role('owner')->create();
    $target = User::factory()->create();
    $oldPasswordHash = $target->password;

    $this->actingAs($owner)->put(route('users.update', $target), [
        'name' => $target->name,
        'username' => $target->username,
        'email' => $target->email,
        'role' => $target->role,
        'password' => '',
    ]);

    $target->refresh();
    expect($target->password)->toBe($oldPasswordHash);
});

test('owner cannot deactivate their own account', function () {
    $owner = User::factory()->role('owner')->create();

    $response = $this->actingAs($owner)->put(route('users.update', $owner), [
        'name' => $owner->name,
        'username' => $owner->username,
        'email' => $owner->email,
        'role' => $owner->role,
        'password' => '',
        'is_active' => false,
    ]);

    $response->assertRedirect();
    $owner->refresh();
    expect($owner->is_active)->toBeTrue();
});

test('owner can delete another user', function () {
    $owner = User::factory()->role('owner')->create();
    $target = User::factory()->create();

    $response = $this->actingAs($owner)->delete(route('users.destroy', $target));

    $response->assertRedirect(route('users.index'));
    $this->assertDatabaseMissing('users', ['id' => $target->id]);
});

test('owner cannot delete their own account', function () {
    $owner = User::factory()->role('owner')->create();

    $response = $this->actingAs($owner)->delete(route('users.destroy', $owner));

    $response->assertRedirect();
    $this->assertDatabaseHas('users', ['id' => $owner->id]);
});
