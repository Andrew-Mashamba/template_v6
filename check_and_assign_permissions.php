<?php

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Modules that need permissions
$modules = [
    'budget-management',
    'hr',
    'expenses',
    'reports'
];

// Common actions for each module
$actions = ['view', 'create', 'edit', 'delete', 'approve', 'manage', 'export'];

echo "Checking and creating permissions for modules...\n";

// First, ensure all permissions exist in the database
foreach ($modules as $module) {
    foreach ($actions as $action) {
        $permissionName = $module . '.' . $action;
        
        // Check if permission exists
        $exists = DB::table('permissions')->where('name', $permissionName)->exists();
        
        if (!$exists) {
            // Create the permission with all required fields
            DB::table('permissions')->insert([
                'name' => $permissionName,
                'slug' => str_replace('.', '-', $permissionName),
                'description' => ucfirst(str_replace('-', ' ', $module)) . ' - ' . ucfirst($action) . ' permission',
                'module' => $module,
                'action' => $action,
                'is_system' => false,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            echo "Created permission: $permissionName\n";
        } else {
            echo "Permission already exists: $permissionName\n";
        }
    }
}

echo "\n";
echo "Checking IT Manager role permissions...\n";

// Find the IT Manager role
$itManagerRole = DB::table('roles')->where('name', 'IT Manager')->first();

if (!$itManagerRole) {
    echo "IT Manager role not found. Creating it...\n";
    $roleId = DB::table('roles')->insertGetId([
        'name' => 'IT Manager',
        'guard_name' => 'web',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    $itManagerRole = DB::table('roles')->where('id', $roleId)->first();
}

echo "IT Manager role ID: {$itManagerRole->id}\n\n";

// Build list of permission names
$permissionNames = [];
foreach ($modules as $module) {
    foreach ($actions as $action) {
        $permissionNames[] = $module . '.' . $action;
    }
}

// Get all permissions we want to assign
$permissionIds = DB::table('permissions')
    ->where(function($query) use ($modules) {
        foreach ($modules as $module) {
            $query->orWhere('name', 'like', $module . '.%');
        }
    })
    ->pluck('id', 'name');

echo "Found " . count($permissionIds) . " permissions to check.\n\n";

// Check and assign permissions to IT Manager role
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
echo "Checking current user's role...\n";

// Check if current logged-in user (if any) has IT Manager role
$currentUserId = 1; // Assuming user ID 1, adjust as needed

$userRole = DB::table('user_roles')
    ->where('user_id', $currentUserId)
    ->where('role_id', $itManagerRole->id)
    ->first();

if (!$userRole) {
    echo "User ID $currentUserId doesn't have IT Manager role. Assigning it...\n";
    DB::table('user_roles')->insert([
        'user_id' => $currentUserId,
        'role_id' => $itManagerRole->id,
        'created_at' => now(),
        'updated_at' => now()
    ]);
    echo "✅ Assigned IT Manager role to user ID $currentUserId\n";
} else {
    echo "✓ User ID $currentUserId already has IT Manager role\n";
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
echo "✅ Permission setup complete!\n";
echo "The IT Manager role now has full access to:\n";
echo "- Budget Management\n";
echo "- Human Resources\n";
echo "- Expenses\n";
echo "- Reports\n";
echo "\n";
echo "Please refresh your browser to see the changes.\n";