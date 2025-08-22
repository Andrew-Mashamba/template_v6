<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConsolidatedMenuSeeder extends Seeder
{
    public function run()
    {
        // Disable foreign key checks (PostgreSQL compatible)
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('SET session_replication_role = replica;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }
        
        // Clear existing data
        DB::table('role_menu_actions')->delete();
        DB::table('menu_actions')->delete();
        DB::table('menus')->delete();
        
        // Insert all menus
        $menus = [
            // Main menus
            ['id' => 1, 'name' => 'Dashboard', 'menu_name' => 'Dashboard', 'menu_description' => 'Access and manage dashboard features', 'route' => 'dashboard', 'icon' => 'home', 'parent_id' => null, 'order' => 1],
            ['id' => 2, 'name' => 'Members', 'menu_name' => 'Members', 'menu_description' => 'Access and manage members features', 'route' => 'members', 'icon' => 'users', 'parent_id' => null, 'order' => 2],
            ['id' => 3, 'name' => 'Loans', 'menu_name' => 'Loans', 'menu_description' => 'Access and manage loans features', 'route' => 'loans', 'icon' => 'dollar-sign', 'parent_id' => null, 'order' => 3],
            ['id' => 4, 'name' => 'Savings', 'menu_name' => 'Savings', 'menu_description' => 'Access and manage savings features', 'route' => 'savings', 'icon' => 'piggy-bank', 'parent_id' => null, 'order' => 4],
            ['id' => 5, 'name' => 'Shares', 'menu_name' => 'Shares', 'menu_description' => 'Access and manage shares features', 'route' => 'shares', 'icon' => 'chart-line', 'parent_id' => null, 'order' => 5],
            ['id' => 6, 'name' => 'Accounting', 'menu_name' => 'Accounting', 'menu_description' => 'Access and manage accounting features', 'route' => 'accounting', 'icon' => 'calculator', 'parent_id' => null, 'order' => 6],
            ['id' => 7, 'name' => 'Reports', 'menu_name' => 'Reports', 'menu_description' => 'Access and manage reports features', 'route' => 'reports', 'icon' => 'file-text', 'parent_id' => null, 'order' => 7],
            ['id' => 8, 'name' => 'Settings', 'menu_name' => 'Settings', 'menu_description' => 'Access and manage settings features', 'route' => 'settings', 'icon' => 'settings', 'parent_id' => null, 'order' => 8],
            ['id' => 9, 'name' => 'Cash Management', 'menu_name' => 'Cash Management', 'menu_description' => 'Access and manage cash management features', 'route' => 'cash-management', 'icon' => 'dollar-sign', 'parent_id' => null, 'order' => 9],
            ['id' => 10, 'name' => 'Billing', 'menu_name' => 'Billing', 'menu_description' => 'Access and manage billing features', 'route' => 'billing', 'icon' => 'credit-card', 'parent_id' => null, 'order' => 10],
            ['id' => 11, 'name' => 'Transactions', 'menu_name' => 'Transactions', 'menu_description' => 'Access and manage transactions features', 'route' => 'transactions', 'icon' => 'activity', 'parent_id' => null, 'order' => 11],
            ['id' => 12, 'name' => 'HR Management', 'menu_name' => 'HR Management', 'menu_description' => 'Access and manage HR management features', 'route' => 'hr-management', 'icon' => 'users', 'parent_id' => null, 'order' => 12],
            ['id' => 13, 'name' => 'Audit', 'menu_name' => 'Audit', 'menu_description' => 'Access and manage audit features', 'route' => 'audit', 'icon' => 'check-circle', 'parent_id' => null, 'order' => 13],
            ['id' => 14, 'name' => 'Security', 'menu_name' => 'Security', 'menu_description' => 'Access and manage security features', 'route' => 'security', 'icon' => 'shield', 'parent_id' => null, 'order' => 14],
            ['id' => 15, 'name' => 'System', 'menu_name' => 'System', 'menu_description' => 'Access and manage system features', 'route' => 'system', 'icon' => 'server', 'parent_id' => null, 'order' => 15],
            ['id' => 16, 'name' => 'Communications', 'menu_name' => 'Communications', 'menu_description' => 'Access and manage communications features', 'route' => 'communications', 'icon' => 'message-circle', 'parent_id' => null, 'order' => 16],
            ['id' => 17, 'name' => 'Products', 'menu_name' => 'Products', 'menu_description' => 'Access and manage products features', 'route' => 'products', 'icon' => 'package', 'parent_id' => null, 'order' => 17],
            ['id' => 18, 'name' => 'Inventory', 'menu_name' => 'Inventory', 'menu_description' => 'Access and manage inventory features', 'route' => 'inventory', 'icon' => 'box', 'parent_id' => null, 'order' => 18],
            ['id' => 19, 'name' => 'Procurement', 'menu_name' => 'Procurement', 'menu_description' => 'Access and manage procurement features', 'route' => 'procurement', 'icon' => 'shopping-cart', 'parent_id' => null, 'order' => 19],
            ['id' => 20, 'name' => 'Assets', 'menu_name' => 'Assets', 'menu_description' => 'Access and manage assets features', 'route' => 'assets', 'icon' => 'briefcase', 'parent_id' => null, 'order' => 20],
            ['id' => 21, 'name' => 'Payroll', 'menu_name' => 'Payroll', 'menu_description' => 'Access and manage payroll features', 'route' => 'payroll', 'icon' => 'dollar-sign', 'parent_id' => null, 'order' => 21],
            ['id' => 22, 'name' => 'Budgets', 'menu_name' => 'Budgets', 'menu_description' => 'Access and manage budgets features', 'route' => 'budgets', 'icon' => 'trending-up', 'parent_id' => null, 'order' => 22],
            ['id' => 23, 'name' => 'Investments', 'menu_name' => 'Investments', 'menu_description' => 'Access and manage investments features', 'route' => 'investments', 'icon' => 'trending-up', 'parent_id' => null, 'order' => 23],
            ['id' => 24, 'name' => 'Insurance', 'menu_name' => 'Insurance', 'menu_description' => 'Access and manage insurance features', 'route' => 'insurance', 'icon' => 'shield', 'parent_id' => null, 'order' => 24],
            ['id' => 25, 'name' => 'Fixed Deposits', 'menu_name' => 'Fixed Deposits', 'menu_description' => 'Access and manage fixed deposits features', 'route' => 'fixed-deposits', 'icon' => 'lock', 'parent_id' => null, 'order' => 25],
            ['id' => 26, 'name' => 'Dividends', 'menu_name' => 'Dividends', 'menu_description' => 'Access and manage dividends features', 'route' => 'dividends', 'icon' => 'gift', 'parent_id' => null, 'order' => 26],
            ['id' => 27, 'name' => 'Meetings', 'menu_name' => 'Meetings', 'menu_description' => 'Access and manage meetings features', 'route' => 'meetings', 'icon' => 'calendar', 'parent_id' => null, 'order' => 27],
            ['id' => 28, 'name' => 'Projects', 'menu_name' => 'Projects', 'menu_description' => 'Access and manage projects features', 'route' => 'projects', 'icon' => 'clipboard', 'parent_id' => null, 'order' => 28],
            ['id' => 29, 'name' => 'Contracts', 'menu_name' => 'Contracts', 'menu_description' => 'Access and manage contracts features', 'route' => 'contracts', 'icon' => 'file-text', 'parent_id' => null, 'order' => 29],
            ['id' => 30, 'name' => 'Compliance', 'menu_name' => 'Compliance', 'menu_description' => 'Access and manage compliance features', 'route' => 'compliance', 'icon' => 'check-square', 'parent_id' => null, 'order' => 30],
            ['id' => 31, 'name' => 'Dashboard Analytics', 'menu_name' => 'Dashboard Analytics', 'menu_description' => 'Access and manage dashboard analytics features', 'route' => 'analytics', 'icon' => 'bar-chart', 'parent_id' => null, 'order' => 31],
        ];
        
        DB::table('menus')->insert($menus);
        
        // Insert menu actions
        $actions = [
            ['id' => 1, 'name' => 'View', 'slug' => 'view'],
            ['id' => 2, 'name' => 'Create', 'slug' => 'create'],
            ['id' => 3, 'name' => 'Edit', 'slug' => 'edit'],
            ['id' => 4, 'name' => 'Delete', 'slug' => 'delete'],
            ['id' => 5, 'name' => 'Approve', 'slug' => 'approve'],
            ['id' => 6, 'name' => 'Export', 'slug' => 'export'],
        ];
        
        DB::table('menu_actions')->insert($actions);
        
        // Re-enable foreign key checks
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('SET session_replication_role = DEFAULT;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }
}
