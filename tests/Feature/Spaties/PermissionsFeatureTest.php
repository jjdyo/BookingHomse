<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Ensure Spatie permission cache is reset between tests
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

it('allows assigning roles and grants permissions via roles', function () {
    // Arrange: create role and permission
    $role = Role::create(['name' => 'admin', 'guard_name' => 'web']);
    $permission = Permission::create(['name' => 'edit articles', 'guard_name' => 'web']);
    $role->givePermissionTo($permission);

    $user = User::factory()->create();

    // Assert pre-conditions
    expect($user->hasRole('admin'))->toBeFalse();
    expect($user->can('edit articles'))->toBeFalse();

    // Act: assign role
    $user->assignRole('admin');

    // Assert: role and permission are now effective
    expect($user->hasRole('admin'))->toBeTrue();
    expect($user->can('edit articles'))->toBeTrue();
});

it('denies permissions to users without the required role/permission', function () {
    // Arrange
    $permission = Permission::firstOrCreate(['name' => 'edit articles', 'guard_name' => 'web']);
    $userWithout = User::factory()->create();

    // Assert
    expect($userWithout->can($permission->name))->toBeFalse();
});

it('checks hasAnyRole and hasAllRoles helpers', function () {
    // Arrange
    Role::create(['name' => 'editor', 'guard_name' => 'web']);
    Role::create(['name' => 'writer', 'guard_name' => 'web']);

    $user = User::factory()->create();

    // Assign only one role first
    $user->assignRole('editor');

    expect($user->hasAnyRole(['editor', 'writer']))->toBeTrue();
    expect($user->hasAllRoles(['editor', 'writer']))->toBeFalse();

    // Assign the second role
    $user->assignRole('writer');

    expect($user->hasAllRoles(['editor', 'writer']))->toBeTrue();
});
