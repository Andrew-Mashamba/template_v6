<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Role;
use App\Models\Menu;
use App\Models\RoleMenuAction;
use Illuminate\Support\Facades\DB;

try {
    DB::beginTransaction();
    
    // Find user with ID 1
    $user = User::find(1);
    if (!$user) {
        throw new Exception("User with ID 1 not found!");
    }
    
    echo "Found user: {$user->name} (ID: {$user->id})\n";
    
    // Find System Administrator role
    $adminRole = Role::where('name', 'System Administrator')->first();
    if (!$adminRole) {
        throw new Exception("System Administrator role not found!");
    }
    
    echo "Found role: {$adminRole->name} (ID: {$adminRole->id})\n";
    
    // Assign System Administrator role to user if not already assigned
    if (!$user->roles()->where('roles.id', $adminRole->id)->exists()) {
        $user->roles()->attach($adminRole->id, [
            'created_at' => now(),
            'updated_at' => now()
        ]);
        echo "Assigned System Administrator role to user\n";
    } else {
        echo "User already has System Administrator role\n";
    }
    
    // Get all menus
    $menus = Menu::all();
    echo "\nFound {$menus->count()} menus in the system\n";
    
    // Default actions
    $defaultActions = ['can_view', 'can_create', 'can_update', 'can_delete'];
    
    // Grant access to all menus for the admin role
    $addedCount = 0;
    $updatedCount = 0;
    
    foreach ($menus as $menu) {
        // Check if role_menu_action already exists
        $existingRoleMenuAction = RoleMenuAction::where('role_id', $adminRole->id)
            ->where('menu_id', $menu->id)
            ->first();
            
        if ($existingRoleMenuAction) {
            // Update to ensure all actions are allowed
            $existingRoleMenuAction->update([
                'allowed_actions' => json_encode($defaultActions),
                'updated_at' => now()
            ]);
            $updatedCount++;
            echo "Updated permissions for menu: {$menu->menu_name}\n";
        } else {
            // Create new role_menu_action with auto-incrementing ID
            DB::table('role_menu_actions')->insert([
                'role_id' => $adminRole->id,
                'menu_id' => $menu->id,
                'allowed_actions' => json_encode($defaultActions),
                'sub_role' => null,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $addedCount++;
            echo "Added permissions for menu: {$menu->menu_name}\n";
        }
    }
    
    DB::commit();
    
    echo "\n✅ Success!\n";
    echo "- User: {$user->name} (ID: {$user->id})\n";
    echo "- Role: {$adminRole->name} (ID: {$adminRole->id})\n";
    echo "- Menus: Added permissions for {$addedCount} menus, updated {$updatedCount} menus\n";
    echo "- Total menus accessible: {$menus->count()}\n";
    
    // Verify user can see all menus
    $user->refresh();
    $userMenuCount = 0;
    foreach ($user->roles as $role) {
        $menuService = app(\App\Services\MenuService::class);
        $menuItems = $menuService->getMenuItemsForRole($role);
        $userMenuCount = max($userMenuCount, $menuItems->count());
    }
    
    echo "\nVerification: User can now access {$userMenuCount} menus through their roles\n";
    
} catch (Exception $e) {
    DB::rollBack();
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}