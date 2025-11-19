<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Reset cached roles and permissions between tests
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    // Define ad-hoc routes for testing middleware behavior
    Route::get('/admin-only', fn () => response('ok'))
        ->middleware('role:admin');

    Route::get('/can-edit', fn () => response('ok'))
        ->middleware('permission:edit articles');
});

it('allows users with the admin role to access admin-only routes', function () {
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this->actingAs($user)->get('/admin-only');
    $response->assertOk();
});

it('forbids users without the admin role from accessing admin-only routes', function () {
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/admin-only');
    $response->assertForbidden();
});

it('allows users with a required permission to access permission-guarded routes', function () {
    $permission = Permission::create(['name' => 'edit articles', 'guard_name' => 'web']);
    $role = Role::create(['name' => 'editor', 'guard_name' => 'web']);
    $role->givePermissionTo($permission);

    $user = User::factory()->create();
    $user->assignRole('editor');

    $response = $this->actingAs($user)->get('/can-edit');
    $response->assertOk();
});

it('forbids users without a required permission from accessing permission-guarded routes', function () {
    Permission::create(['name' => 'edit articles', 'guard_name' => 'web']);
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/can-edit');
    $response->assertForbidden();
});
