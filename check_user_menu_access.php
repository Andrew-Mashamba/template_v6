<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Get user info
$user = DB::table('users')->where('id', 1)->first();
echo "=== USER MENU ACCESS CHECK ===\n\n";
echo "User: {$user->name} (ID: {$user->id})\n";
echo "Email: {$user->email}\n\n";

// Get user roles
$userRoles = DB::table('user_roles')
    ->join('roles', 'roles.id', '=', 'user_roles.role_id')
    ->where('user_roles.user_id', 1)
    ->select('roles.*')
    ->get();

echo "Current Roles:\n";
foreach ($userRoles as $role) {
    echo "- {$role->name} (ID: {$role->id})\n";
}

// Get all menus
$allMenus = DB::table('menus')->get();
echo "\nTotal menus in system: " . $allMenus->count() . "\n";

// Check current menu access through role_menu_actions
$roleIds = $userRoles->pluck('id')->toArray();
$currentMenuAccess = [];

if (!empty($roleIds)) {
    $menuAccess = DB::table('role_menu_actions')
        ->whereIn('role_id', $roleIds)
        ->join('menus', 'menus.id', '=', 'role_menu_actions.menu_id')
        ->select('menus.id', 'menus.menu_name')
        ->distinct()
        ->get();
    
    foreach ($menuAccess as $access) {
        $currentMenuAccess[$access->id] = $access->menu_name;
    }
    
    echo "\nCurrent menu access: " . count($currentMenuAccess) . " menus\n";
    
    if (count($currentMenuAccess) < $allMenus->count()) {
        echo "\nMissing access to these menus:\n";
        foreach ($allMenus as $menu) {
            if (!isset($currentMenuAccess[$menu->id])) {
                echo "- {$menu->menu_name} (ID: {$menu->id})\n";
            }
        }
    }
} else {
    echo "\nUser has no roles assigned!\n";
}

// Check Administrator role
$adminRole = DB::table('roles')->where('name', 'Administrator')->first();
if ($adminRole) {
    echo "\nAdministrator role found (ID: {$adminRole->id})\n";
    
    // Check if user has Administrator role
    $hasAdminRole = DB::table('user_roles')
        ->where('user_id', 1)
        ->where('role_id', $adminRole->id)
        ->exists();
    
    echo "User has Administrator role: " . ($hasAdminRole ? "Yes" : "No") . "\n";
}