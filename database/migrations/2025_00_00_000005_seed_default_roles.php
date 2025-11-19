<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ensure permission/role cache is cleared before seeding
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Create default roles if they do not already exist
        // Using Spatie's findOrCreate is the recommended approach
        Role::findOrCreate('user', 'web');
        Role::findOrCreate('trainer', 'web');
        Role::findOrCreate('admin', 'web');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cleanly remove the default roles on rollback (only those created by this migration)
        $roles = ['user', 'admin'];

        foreach ($roles as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role) {
                $role->delete();
            }
        }

        // Clear the cache again after modifications
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
