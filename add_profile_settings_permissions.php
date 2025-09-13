<?php

use Illuminate\Support\Facades\DB;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Module to add
$module = 'profile-setting';

// Common actions for the module
$actions = ['view', 'create', 'edit', 'delete', 'manage', 'export'];

echo "Adding profile-setting module permissions...\n\n";

// First, ensure all permissions exist in the database
foreach ($actions as $action) {
    $permissionName = $module . '.' . $action;
    
    // Check if permission exists
    $exists = DB::table('permissions')->where('name', $permissionName)->exists();
    
    if (!$exists) {
        // Create the permission with all required fields
        DB::table('permissions')->insert([
            'name' => $permissionName,
            'slug' => str_replace('.', '-', $permissionName),
            'description' => 'Profile Settings - ' . ucfirst($action) . ' permission',
            'module' => $module,
            'action' => $action,
            'is_system' => false,
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        echo "✅ Created permission: $permissionName\n";
    } else {
        echo "✓ Permission already exists: $permissionName\n";
    }
}

echo "\n";
echo "Assigning profile-setting permissions to IT Manager role...\n\n";

// Find the IT Manager role
$itManagerRole = DB::table('roles')->where('name', 'IT Manager')->first();

if (!$itManagerRole) {
    echo "❌ IT Manager role not found!\n";
    exit(1);
}

echo "Found IT Manager role ID: {$itManagerRole->id}\n\n";

// Get all profile-setting permissions
$permissionIds = DB::table('permissions')
    ->where('name', 'like', $module . '.%')
    ->pluck('id', 'name');

echo "Found " . count($permissionIds) . " profile-setting permissions.\n\n";

// Assign permissions to IT Manager role
foreach ($permissionIds as $permissionName => $permissionId) {
    $exists = DB::table('role_permissions')
        ->where('role_id', $itManagerRole->id)
        ->where('permission_id', $permissionId)
        ->exists();
    
    if (!$exists) {
        DB::table('role_permissions')->insert([
            'role_id' => $itManagerRole->id,
            'permission_id' => $permissionId,
            'is_inherited' => false,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        echo "✅ Assigned permission to IT Manager: $permissionName\n";
    } else {
        echo "✓ IT Manager already has permission: $permissionName\n";
    }
}

echo "\n";
echo "Clearing permission cache...\n";

// Clear any permission caches
try {
    \Artisan::call('cache:clear');
    echo "✅ Cache cleared successfully\n";
} catch (\Exception $e) {
    echo "⚠️ Could not clear cache: " . $e->getMessage() . "\n";
}

echo "\n";
echo "✅ Profile Settings permissions setup complete!\n";
echo "The IT Manager role now has full access to Institution Settings Manager.\n";
echo "\n";
echo "Please refresh your browser to see the changes.\n";