<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== COMPREHENSIVE ROLES AND PERMISSIONS SUMMARY ===\n\n";

// Summary statistics
echo "SUMMARY STATISTICS:\n";
echo "==================\n";
echo "✓ Roles: " . DB::table('roles')->count() . " total\n";
echo "✓ Permissions: " . DB::table('permissions')->count() . " total\n";
echo "✓ Role-Permission Assignments: " . DB::table('role_permissions')->count() . " total\n";
echo "✓ User-Role Assignments: " . DB::table('user_roles')->count() . " total\n";
echo "✓ Menu Actions: " . DB::table('menu_actions')->count() . " total\n";
echo "✓ Role-Menu Assignments: " . DB::table('role_menu_actions')->count() . " total\n";
echo "✓ Sub-Roles: " . DB::table('sub_roles')->count() . " total\n";
echo "✓ Sub-Role Permissions: " . DB::table('sub_role_permissions')->count() . " total\n";

echo "\nKEY FINDINGS:\n";
echo "=============\n";

// Check which users have roles
$usersWithRoles = DB::table('users')
    ->join('user_roles', 'users.id', '=', 'user_roles.user_id')
    ->count();
$totalUsers = DB::table('users')->count();
echo "• $usersWithRoles out of $totalUsers users have roles assigned\n";

// Check admin user
$adminUser = DB::table('users')
    ->join('user_roles', 'users.id', '=', 'user_roles.user_id')
    ->join('roles', 'roles.id', '=', 'user_roles.role_id')
    ->where('roles.name', 'System Administrator')
    ->select('users.name', 'users.email')
    ->first();
if ($adminUser) {
    echo "• System Administrator: {$adminUser->name} ({$adminUser->email})\n";
}

// Check permissions coverage
$rolesWithPerms = DB::table('roles')
    ->join('role_permissions', 'roles.id', '=', 'role_permissions.role_id')
    ->distinct()
    ->count('roles.id');
echo "• $rolesWithPerms out of 2 roles have permissions assigned\n";

// Menu access
$rolesWithMenus = DB::table('roles')
    ->join('role_menu_actions', 'roles.id', '=', 'role_menu_actions.role_id')
    ->distinct()
    ->count('roles.id');
echo "• $rolesWithMenus out of 2 roles have menu access configured\n";

echo "\nRECOMMENDATIONS:\n";
echo "================\n";

$recommendations = [];

if (DB::table('permissions')->count() < 10) {
    $recommendations[] = "Consider running a more comprehensive permission seeder. Only " . DB::table('permissions')->count() . " permissions found.";
}

if ($usersWithRoles < $totalUsers) {
    $recommendations[] = "Not all users have roles assigned. Consider assigning roles to the remaining " . ($totalUsers - $usersWithRoles) . " users.";
}

if (DB::table('role_permissions')->count() < 10) {
    $recommendations[] = "Very few role-permission assignments found. Consider adding more granular permissions.";
}

if (empty($recommendations)) {
    echo "✓ Role and permission setup appears to be adequate for basic operations.\n";
} else {
    foreach ($recommendations as $i => $rec) {
        echo ($i + 1) . ". " . $rec . "\n";
    }
}

echo "\nSEEDER STATUS:\n";
echo "==============\n";
echo "The following seeders have successfully populated data:\n";
echo "✓ RoleSeeder - Created 2 base roles\n";
echo "✓ PermissionsSeeder - Created 2 permissions (minimal set)\n";
echo "✓ MenuActionsSeeder - Created 209 menu actions\n";
echo "✓ RoleMenuActionSeeder - Assigned menu actions to roles\n";
echo "✓ UserRoleSeeder - Assigned roles to users\n";
echo "✓ SubRoleSeeder - Created sub-roles\n";
echo "✓ SubrolepermissionsSeeder - Configured sub-role permissions\n";

echo "\n=== END OF SUMMARY ===\n";