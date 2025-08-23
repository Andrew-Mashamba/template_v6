<?php

// List of known duplicates (keep the plural form)
$duplicates = [
    'RoleSeeder::class', // Keep RolesSeeder
    'PermissionSeeder::class', // Keep PermissionsSeeder  
    'UserRoleSeeder::class', // Keep UserrolesSeeder
    'MenuSeeder::class', // Keep ConsolidatedMenuSeeder
    'ServiceSeeder::class', // Keep ServicesSeeder
    'SubRoleSeeder::class', // Keep SubrolesSeeder
    'CommitteeSeeder::class', // Keep CommitteesSeeder
    'RoleMenuActionSeeder::class', // Keep RoleMenuActionsSeeder
];

$content = file_get_contents('database/seeders/DatabaseSeeder.php');

// Remove duplicates
foreach ($duplicates as $duplicate) {
    $content = str_replace("            $duplicate,\n", '', $content);
}

file_put_contents('database/seeders/DatabaseSeeder.php', $content);

echo "Fixed DatabaseSeeder by removing duplicates\n";