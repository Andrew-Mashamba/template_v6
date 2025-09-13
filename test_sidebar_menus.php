<?php

use App\Models\User;
use App\Models\Menu;
use Illuminate\Support\Facades\Auth;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Test for User ID 1
$user = User::find(1);
Auth::login($user);

// Get user roles and permissions
$roles = $user->roles()->with('permissions')->get();
$allPermissions = collect();
foreach ($roles as $role) {
    $allPermissions = $allPermissions->merge($role->permissions);
}

echo "User: {$user->name}\n";
echo "Total Permissions: " . $allPermissions->count() . "\n\n";

// Map permissions to menu IDs
$permissionToMenuMap = [
    'dashboard' => 0,
    'branches' => 1,
    'clients' => 2,
    'shares' => 3,
    'savings' => 4,
    'deposits' => 5,
    'loans' => 6,
    'products' => 7,
    'accounting' => 8,
    'services' => 181,
    'expenses' => 9,
    'payments' => 10,
    'investment' => 11,
    'procurement' => 12,
    'budget' => 13,
    'insurance' => 14,
    'teller' => 15,
    'reconciliation' => 16,
    'hr' => 17,
    'self_services' => 18,
    'approvals' => 19,
    'reports' => 20,
    'profile' => 21,
    'users' => 22,
    'active_loans' => 23,
    'management' => 24,
    'cash_management' => 26,
    'billing' => 27,
    'transactions' => 28,
    'members_portal' => 29,
    'email' => 30,
    'subscriptions' => 31,
    'system' => 0,
];

$menuItems = collect();

// For each permission, add the corresponding menu ID
foreach ($allPermissions as $permission) {
    $permissionName = $permission->name;
    $permissionPrefix = explode('.', $permissionName)[0];
    
    if (isset($permissionToMenuMap[$permissionPrefix])) {
        $menuId = $permissionToMenuMap[$permissionPrefix];
        $menuItems->push($menuId);
    }
}

// Always include Dashboard if user has any permissions
if ($allPermissions->isNotEmpty()) {
    $menuItems->push(0);
}

$uniqueMenuIds = $menuItems->unique()->values()->toArray();
sort($uniqueMenuIds);

echo "Accessible Menu IDs: " . count($uniqueMenuIds) . "\n";
echo "Menu IDs: " . implode(', ', $uniqueMenuIds) . "\n\n";

// List the actual menus
echo "Accessible Menus:\n";
echo "================\n";
foreach ($uniqueMenuIds as $menuId) {
    if ($menuId == 0) {
        echo "0: Dashboard\n";
    } else {
        $menu = Menu::find($menuId);
        if ($menu) {
            echo "{$menuId}: {$menu->menu_name} (menu_number: {$menu->menu_number})\n";
        }
    }
}

// Check which permissions map to which menus
echo "\nPermission Module Distribution:\n";
echo "================================\n";
$moduleCount = [];
foreach ($allPermissions as $permission) {
    $module = explode('.', $permission->name)[0];
    if (!isset($moduleCount[$module])) {
        $moduleCount[$module] = 0;
    }
    $moduleCount[$module]++;
}

foreach ($moduleCount as $module => $count) {
    $menuId = $permissionToMenuMap[$module] ?? 'Not mapped';
    echo "{$module}: {$count} permissions -> Menu ID: {$menuId}\n";
}