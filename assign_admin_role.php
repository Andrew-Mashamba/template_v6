<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// First check if user exists
$user = DB::table('users')->where('id', 1)->first();
if (!$user) {
    echo "Error: User with ID 1 not found\n";
    exit(1);
}

// Get Administrator role
$role = DB::table('roles')->where('name', 'Administrator')->first();
if (!$role) {
    echo "Error: Administrator role not found\n";
    exit(1);
}

// Check if assignment already exists
$existing = DB::table('user_roles')
    ->where('user_id', 1)
    ->where('role_id', $role->id)
    ->first();

if ($existing) {
    echo "User already has Administrator role\n";
} else {
    // Create the role assignment
    DB::table('user_roles')->insert([
        'user_id' => 1,
        'role_id' => $role->id,
        'created_at' => now(),
        'updated_at' => now()
    ]);
    echo "Successfully assigned Administrator role to user {$user->name} (ID: 1)\n";
}

// Verify the assignment
$assignments = DB::table('user_roles')
    ->join('users', 'users.id', '=', 'user_roles.user_id')
    ->join('roles', 'roles.id', '=', 'user_roles.role_id')
    ->where('users.id', 1)
    ->select('users.name as user_name', 'users.email', 'roles.name as role_name')
    ->get();

echo "\nUser's current roles:\n";
foreach ($assignments as $assignment) {
    echo "- {$assignment->user_name} ({$assignment->email}) has role: {$assignment->role_name}\n";
}

// Show the permissions this role has
echo "\nPermissions for Administrator role:\n";
$permissions = DB::table('role_permissions')
    ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
    ->where('role_permissions.role_id', $role->id)
    ->select('permissions.*')
    ->get();

foreach ($permissions as $permission) {
    echo "- {$permission->name} ({$permission->module}:{$permission->action})\n";
}