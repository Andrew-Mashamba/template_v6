<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Get Administrator role
$role = DB::table('roles')->where('name', 'Administrator')->first();
$permissions = DB::table('permissions')->get();

echo "Assigning all permissions to Administrator role...\n";

foreach ($permissions as $permission) {
    $existing = DB::table('role_permissions')
        ->where('role_id', $role->id)
        ->where('permission_id', $permission->id)
        ->first();
    
    if (!$existing) {
        DB::table('role_permissions')->insert([
            'role_id' => $role->id,
            'permission_id' => $permission->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        echo "- Assigned: {$permission->name}\n";
    } else {
        echo "- Already has: {$permission->name}\n";
    }
}

echo "\nAdministrator role now has all permissions assigned.\n";

// Verify
$count = DB::table('role_permissions')->where('role_id', $role->id)->count();
echo "Total permissions: $count\n";