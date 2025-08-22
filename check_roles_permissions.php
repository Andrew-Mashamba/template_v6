<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CHECKING ROLES AND PERMISSIONS SEEDING STATUS ===\n\n";

// Check roles table
$rolesCount = DB::table('roles')->count();
echo "1. ROLES TABLE:\n";
echo "   - Total roles: $rolesCount\n";
if ($rolesCount > 0) {
    $roles = DB::table('roles')->get();
    echo "   - Roles found:\n";
    foreach ($roles as $role) {
        echo "     * ID: {$role->id}, Name: {$role->name}, Guard: {$role->guard_name}\n";
    }
}
echo "\n";

// Check permissions table
$permissionsCount = DB::table('permissions')->count();
echo "2. PERMISSIONS TABLE:\n";
echo "   - Total permissions: $permissionsCount\n";
if ($permissionsCount > 0) {
    echo "   - Sample permissions (first 10):\n";
    $permissions = DB::table('permissions')->limit(10)->get();
    foreach ($permissions as $perm) {
        echo "     * ID: {$perm->id}, Name: {$perm->name}, Guard: {$perm->guard_name}\n";
    }
}
echo "\n";

// Check role_permissions table
$rolePermissionsCount = DB::table('role_permissions')->count();
echo "3. ROLE_PERMISSIONS TABLE:\n";
echo "   - Total role-permission assignments: $rolePermissionsCount\n";
if ($rolePermissionsCount > 0) {
    $rolePerm = DB::table('role_permissions')
        ->join('roles', 'roles.id', '=', 'role_permissions.role_id')
        ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
        ->select('roles.name as role_name', DB::raw('COUNT(role_permissions.permission_id) as permission_count'))
        ->groupBy('roles.id', 'roles.name')
        ->get();
    echo "   - Permissions per role:\n";
    foreach ($rolePerm as $rp) {
        echo "     * Role '{$rp->role_name}': {$rp->permission_count} permissions\n";
    }
}
echo "\n";

// Check user_roles table
$userRolesCount = DB::table('user_roles')->count();
echo "4. USER_ROLES TABLE:\n";
echo "   - Total user-role assignments: $userRolesCount\n";
if ($userRolesCount > 0) {
    $userRoles = DB::table('user_roles')
        ->join('users', 'users.id', '=', 'user_roles.user_id')
        ->join('roles', 'roles.id', '=', 'user_roles.role_id')
        ->select('users.name as user_name', 'roles.name as role_name')
        ->limit(10)
        ->get();
    echo "   - Sample user-role assignments (first 10):\n";
    foreach ($userRoles as $ur) {
        echo "     * User '{$ur->user_name}' has role '{$ur->role_name}'\n";
    }
}
echo "\n";

// Check user_permissions table
$userPermissionsCount = DB::table('user_permissions')->count();
echo "5. USER_PERMISSIONS TABLE:\n";
echo "   - Total direct user permissions: $userPermissionsCount\n";
echo "\n";

// Check sub_roles table
$subRolesCount = DB::table('sub_roles')->count();
echo "6. SUB_ROLES TABLE:\n";
echo "   - Total sub-roles: $subRolesCount\n";
if ($subRolesCount > 0) {
    $subRoles = DB::table('sub_roles')->limit(5)->get();
    echo "   - Sample sub-roles (first 5):\n";
    foreach ($subRoles as $sr) {
        echo "     * ID: {$sr->id}, Name: {$sr->name}, Parent Role ID: {$sr->role_id}\n";
    }
}
echo "\n";

// Check sub_role_permissions table
$subRolePermissionsCount = DB::table('sub_role_permissions')->count();
echo "7. SUB_ROLE_PERMISSIONS TABLE:\n";
echo "   - Total sub-role permissions: $subRolePermissionsCount\n";
echo "\n";

// Check user_sub_roles table
$userSubRolesCount = DB::table('user_sub_roles')->count();
echo "8. USER_SUB_ROLES TABLE:\n";
echo "   - Total user sub-role assignments: $userSubRolesCount\n";
echo "\n";

// Check menu permissions
$menuActionsCount = DB::table('menu_actions')->count();
$roleMenuActionsCount = DB::table('role_menu_actions')->count();
echo "9. MENU PERMISSIONS:\n";
echo "   - Total menu actions: $menuActionsCount\n";
echo "   - Total role-menu-action assignments: $roleMenuActionsCount\n";
if ($roleMenuActionsCount > 0) {
    $menuPerms = DB::table('role_menu_actions')
        ->join('roles', 'roles.id', '=', 'role_menu_actions.role_id')
        ->select('roles.name as role_name', DB::raw('COUNT(role_menu_actions.id) as menu_count'))
        ->groupBy('roles.id', 'roles.name')
        ->get();
    echo "   - Menu permissions per role:\n";
    foreach ($menuPerms as $mp) {
        echo "     * Role '{$mp->role_name}': {$mp->menu_count} menu permissions\n";
    }
}
echo "\n";

// Check for any missing seeders
echo "=== CHECKING FOR POTENTIAL ISSUES ===\n";
$issues = [];

if ($rolesCount == 0) $issues[] = "No roles found - RoleSeeder may have failed";
if ($permissionsCount == 0) $issues[] = "No permissions found - PermissionSeeder may have failed";
if ($rolePermissionsCount == 0 && $rolesCount > 0) $issues[] = "No role-permission assignments - RolePermissionsSeeder may have failed";
if ($userRolesCount == 0 && $rolesCount > 0) $issues[] = "No user-role assignments - UserRoleSeeder may have failed";
if ($menuActionsCount == 0) $issues[] = "No menu actions found";
if ($roleMenuActionsCount == 0 && $rolesCount > 0) $issues[] = "No role-menu-action assignments";

if (empty($issues)) {
    echo "✓ All role and permission seeders appear to be working correctly!\n";
} else {
    echo "⚠ Found the following issues:\n";
    foreach ($issues as $issue) {
        echo "  - $issue\n";
    }
}

echo "\n=== END OF CHECK ===\n";