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

echo "SIDEBAR MENU VISIBILITY TEST\n";
echo "=============================\n\n";

// Initialize the Sidebar component logic
$sidebar = new \App\Http\Livewire\Sidebar\Sidebar();
$sidebar->currentUserId = $user->id;
$sidebar->currentUserRoles = $user->roles()->with(['permissions', 'department'])->get();

// Load menu items using the actual component method
$sidebar->loadMenuItems();

echo "User: {$user->name}\n";
echo "Roles: " . $sidebar->currentUserRoles->count() . "\n";
echo "Total Menu Items: " . count($sidebar->menuItems) . "\n\n";

// Check which menus actually exist
echo "Menu Validation:\n";
echo "================\n";
$validMenus = [];
$invalidMenus = [];

foreach ($sidebar->menuItems as $menuId) {
    if ($menuId == 0) {
        $validMenus[] = $menuId; // Dashboard is always valid
        echo "✓ Menu ID 0: Dashboard (special case)\n";
    } else {
        $menu = Menu::find($menuId);
        if ($menu) {
            $validMenus[] = $menuId;
            echo "✓ Menu ID {$menuId}: {$menu->menu_name}\n";
        } else {
            $invalidMenus[] = $menuId;
            echo "✗ Menu ID {$menuId}: NOT FOUND IN DATABASE\n";
        }
    }
}

echo "\nSummary:\n";
echo "========\n";
echo "Valid Menus: " . count($validMenus) . "\n";
echo "Invalid Menu IDs: " . count($invalidMenus) . "\n";

if (count($invalidMenus) > 0) {
    echo "Invalid IDs: " . implode(', ', $invalidMenus) . "\n";
}

// Test what will actually be displayed in the blade template
echo "\nMenus that will be displayed (after blade filtering):\n";
echo "=====================================================\n";
$displayedMenus = 0;
foreach ($sidebar->menuItems as $menuId) {
    // Skip these menu IDs as per blade template
    if (in_array($menuId, [11, 12, 14, 15, 24, 25])) {
        continue;
    }
    
    // Skip dashboard (handled separately in blade)
    if ($menuId == 0) {
        $displayedMenus++;
        echo "• Dashboard (handled separately)\n";
        continue;
    }
    
    // Check if menu exists
    $menu = Menu::find($menuId);
    if ($menu) {
        $displayedMenus++;
        echo "• {$menu->menu_name}\n";
    }
}

echo "\nTotal Menus Displayed: {$displayedMenus}\n";

// Final verification
$allPermissions = collect();
foreach ($sidebar->currentUserRoles as $role) {
    $allPermissions = $allPermissions->merge($role->permissions);
}

echo "\nPermissions Check:\n";
echo "==================\n";
echo "User has {$allPermissions->count()} permissions\n";
echo "This should allow access to all system menus.\n";

if ($displayedMenus < 25) {
    echo "\n⚠️ WARNING: User has all permissions but not all menus are visible!\n";
} else {
    echo "\n✅ SUCCESS: User with all permissions can see all available menus!\n";
}