<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== GRANTING ALL MENU ACCESS TO ADMINISTRATOR ROLE ===\n\n";

// Get Administrator role
$adminRole = DB::table('roles')->where('name', 'Administrator')->first();
if (!$adminRole) {
    echo "Administrator role not found!\n";
    exit(1);
}

echo "Administrator role ID: {$adminRole->id}\n";

// Get all menus
$allMenus = DB::table('menus')->get();
echo "Total menus to grant access: {$allMenus->count()}\n\n";

// Get all menu actions for comprehensive access
$allMenuActions = DB::table('menu_actions')->get();
$menuActionsMap = [];
foreach ($allMenuActions as $action) {
    if (!isset($menuActionsMap[$action->menu_id])) {
        $menuActionsMap[$action->menu_id] = [];
    }
    $menuActionsMap[$action->menu_id][] = $action->action_name;
}

// Clear existing role menu actions for admin role
DB::table('role_menu_actions')->where('role_id', $adminRole->id)->delete();
echo "Cleared existing menu access for Administrator role.\n\n";

// Grant access to all menus
$count = 0;
foreach ($allMenus as $menu) {
    // Get all actions for this menu
    $allowedActions = isset($menuActionsMap[$menu->id]) ? $menuActionsMap[$menu->id] : ['view', 'create', 'edit', 'delete'];
    
    DB::table('role_menu_actions')->insert([
        'role_id' => $adminRole->id,
        'menu_id' => $menu->id,
        'sub_role' => 'Administrator',
        'allowed_actions' => json_encode($allowedActions),
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    $count++;
    echo "✓ Granted access to: {$menu->menu_name} (ID: {$menu->id}) with actions: " . implode(', ', $allowedActions) . "\n";
}

echo "\n=== SUMMARY ===\n";
echo "Successfully granted access to $count menus for Administrator role.\n";

// Verify the access
$verifyAccess = DB::table('role_menu_actions')
    ->where('role_id', $adminRole->id)
    ->count();

echo "Verification: Administrator role now has access to $verifyAccess menus.\n";

// Check user with ID 1
$userHasAdminRole = DB::table('user_roles')
    ->where('user_id', 1)
    ->where('role_id', $adminRole->id)
    ->exists();

if ($userHasAdminRole) {
    echo "\nUser ID 1 (Andrew S. Mashamba) has Administrator role and now has access to all menus!\n";
} else {
    echo "\nWarning: User ID 1 does not have Administrator role. Please assign the role.\n";
}