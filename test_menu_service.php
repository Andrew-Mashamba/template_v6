<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Role;
use App\Services\MenuService;

echo "=== TESTING MENU SERVICE FOR USER ID 1 ===\n\n";

// Get user
$user = User::find(1);
if (!$user) {
    echo "User not found!\n";
    exit(1);
}

echo "User: {$user->name}\n";
echo "Email: {$user->email}\n\n";

// Get user's roles
$roles = $user->roles;
echo "User Roles:\n";
foreach ($roles as $role) {
    echo "- {$role->name} (ID: {$role->id})\n";
}

// Test MenuService
$menuService = new MenuService();
$allMenuItems = collect();

echo "\nProcessing menu items for each role:\n";
foreach ($roles as $role) {
    echo "\nRole: {$role->name}\n";
    $menuItems = $menuService->getMenuItemsForRole($role);
    echo "Menu items from this role: {$menuItems->count()}\n";
    
    // Show first few menu items
    $menuItems->take(5)->each(function($menuId) {
        $menu = \App\Models\Menu::find($menuId);
        if ($menu) {
            echo "  - {$menu->menu_name} (ID: {$menuId})\n";
        }
    });
    
    if ($menuItems->count() > 5) {
        echo "  ... and " . ($menuItems->count() - 5) . " more\n";
    }
    
    $allMenuItems = $allMenuItems->merge($menuItems);
}

// Get unique menu items (as Sidebar does)
$uniqueMenuItems = $allMenuItems->unique()->values();

echo "\n=== FINAL RESULT ===\n";
echo "Total unique menu items user can access: {$uniqueMenuItems->count()}\n";
echo "Total menus in system: " . \App\Models\Menu::count() . "\n";

if ($uniqueMenuItems->count() == \App\Models\Menu::count()) {
    echo "\n✓ SUCCESS: User has access to ALL menus in the system!\n";
} else {
    echo "\n⚠ WARNING: User is missing access to some menus.\n";
    
    $allMenuIds = \App\Models\Menu::pluck('id');
    $missingMenuIds = $allMenuIds->diff($uniqueMenuItems);
    
    if ($missingMenuIds->count() > 0) {
        echo "\nMissing menu IDs: " . $missingMenuIds->implode(', ') . "\n";
    }
}