<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Artisan;

echo "=== RE-SEEDING ROLES AND PERMISSIONS ===\n\n";

$seeders = [
    'RoleSeeder' => 'Basic roles',
    'SubRoleSeeder' => 'Sub-roles', 
    'PermissionsSeeder' => 'Permissions',
    'RolepermissionsSeeder' => 'Role-Permission assignments',
    'MenuSeeder' => 'Menus',
    'MenuActionsSeeder' => 'Menu actions',
    'RoleMenuActionSeeder' => 'Role-Menu-Action assignments',
    'UserRoleSeeder' => 'User-Role assignments',
    'UserrolesSeeder' => 'User roles (comprehensive)',
    'SubrolepermissionsSeeder' => 'Sub-role permissions',
    'UsersubmenusSeeder' => 'User sub-menus',
    'UsersubRolesSeeder' => 'User sub-roles',
    'UserpermissionsSeeder' => 'User permissions'
];

foreach ($seeders as $seeder => $description) {
    echo "Running $seeder ($description)... ";
    try {
        Artisan::call('db:seed', ['--class' => $seeder]);
        echo "✓ Done\n";
    } catch (\Exception $e) {
        echo "✗ Failed: " . $e->getMessage() . "\n";
    }
}

echo "\n=== DONE ===\n";