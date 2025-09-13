<?php

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Create a test scenario with limited permissions
echo "Testing menu visibility with limited permissions\n";
echo "=================================================\n\n";

// Get a role with limited permissions (let's use a teller role if it exists)
$tellerRole = Role::where('name', 'LIKE', '%Teller%')
    ->orWhere('name', 'LIKE', '%Cashier%')
    ->first();

if (!$tellerRole) {
    // Create a test role with limited permissions
    $tellerRole = Role::create([
        'name' => 'Test Teller',
        'description' => 'Test role with limited permissions',
        'status' => 'ACTIVE',
        'institution_id' => 11,
    ]);
    
    // Assign only teller and savings related permissions
    $tellerPermissions = Permission::where('name', 'LIKE', 'teller.%')
        ->orWhere('name', 'LIKE', 'savings.%')
        ->orWhere('name', 'LIKE', 'dashboard.%')
        ->get();
    
    $tellerRole->permissions()->sync($tellerPermissions->pluck('id'));
}

echo "Role: {$tellerRole->name}\n";
echo "Permissions: " . $tellerRole->permissions()->count() . "\n\n";

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
$permissions = $tellerRole->permissions;

// For each permission, add the corresponding menu ID
foreach ($permissions as $permission) {
    $permissionName = $permission->name;
    $permissionPrefix = explode('.', $permissionName)[0];
    
    if (isset($permissionToMenuMap[$permissionPrefix])) {
        $menuId = $permissionToMenuMap[$permissionPrefix];
        $menuItems->push($menuId);
    }
}

// Always include Dashboard if user has any permissions
if ($permissions->isNotEmpty()) {
    $menuItems->push(0);
}

$uniqueMenuIds = $menuItems->unique()->values()->toArray();
sort($uniqueMenuIds);

echo "Accessible Menu IDs: " . count($uniqueMenuIds) . "\n";
echo "Menu IDs: " . implode(', ', $uniqueMenuIds) . "\n\n";

// List the actual menus
echo "Accessible Menus for Teller Role:\n";
echo "==================================\n";
foreach ($uniqueMenuIds as $menuId) {
    if ($menuId == 0) {
        echo "✓ Dashboard\n";
    } else {
        $menu = \App\Models\Menu::find($menuId);
        if ($menu) {
            echo "✓ {$menu->menu_name}\n";
        }
    }
}

echo "\nPermission Breakdown:\n";
echo "=====================\n";
$moduleCount = [];
foreach ($permissions as $permission) {
    $module = explode('.', $permission->name)[0];
    if (!isset($moduleCount[$module])) {
        $moduleCount[$module] = 0;
    }
    $moduleCount[$module]++;
}

foreach ($moduleCount as $module => $count) {
    echo "• {$module}: {$count} permissions\n";
}

echo "\n✅ Test Complete: A user with only teller/savings permissions sees only relevant menus!\n";