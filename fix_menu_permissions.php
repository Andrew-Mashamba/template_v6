<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Role;
use App\Models\Menu;
use Illuminate\Support\Facades\DB;

try {
    DB::beginTransaction();
    
    // Fix the sequence for PostgreSQL
    if (DB::getDriverName() === 'pgsql') {
        $maxId = DB::table('role_menu_actions')->max('id') ?? 0;
        $nextId = $maxId + 1;
        DB::statement("ALTER SEQUENCE role_menu_actions_id_seq RESTART WITH {$nextId}");
        echo "Fixed PostgreSQL sequence to start at {$nextId}\n";
    }
    
    // Get admin role and new menus
    $adminRole = Role::where('name', 'System Administrator')->first();
    $newMenus = Menu::whereIn('id', [29, 30, 31])->get();
    
    echo "Found {$newMenus->count()} new menus to add permissions for\n";
    
    $defaultActions = ['can_view', 'can_create', 'can_update', 'can_delete'];
    
    foreach ($newMenus as $menu) {
        // Check if permission already exists
        $exists = DB::table('role_menu_actions')
            ->where('role_id', $adminRole->id)
            ->where('menu_id', $menu->id)
            ->exists();
            
        if (!$exists) {
            DB::table('role_menu_actions')->insert([
                'role_id' => $adminRole->id,
                'menu_id' => $menu->id,
                'allowed_actions' => json_encode($defaultActions),
                'sub_role' => null,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            echo "Added permissions for menu: {$menu->menu_name}\n";
        } else {
            echo "Permissions already exist for menu: {$menu->menu_name}\n";
        }
    }
    
    DB::commit();
    echo "\n✅ Successfully added permissions for new menus!\n";
    
} catch (Exception $e) {
    DB::rollBack();
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}