<?php

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "SACCOS Role Permission Assignment\n";
echo "==================================\n\n";

// Define role permission templates
$rolePermissionTemplates = [
    'IT Manager' => [
        'modules' => ['all'], // Full system access
    ],
    
    'Chief Accountant' => [
        'modules' => [
            'dashboard' => ['view', 'export', 'customize'],
            'accounting' => ['all'],
            'billing' => ['all'],
            'budget' => ['all'],
            'expenses' => ['all'],
            'payments' => ['all'],
            'reconciliation' => ['all'],
            'reports' => ['all'],
            'deposits' => ['view', 'export', 'view_maturity'],
            'loans' => ['view', 'view_reports', 'export'],
            'shares' => ['view', 'view_reports', 'export'],
            'savings' => ['view', 'view_statement', 'export'],
            'investment' => ['all'],
            'insurance' => ['view', 'view_reports'],
            'cash_management' => ['view', 'view_position', 'view_reports'],
            'transactions' => ['all'],
        ],
    ],
    
    'Accountant' => [
        'modules' => [
            'dashboard' => ['view'],
            'accounting' => ['view_coa', 'create_journal', 'view_ledger', 'view_trial_balance', 
                           'view_balance_sheet', 'view_income_statement', 'view_cash_flow'],
            'billing' => ['view', 'create', 'edit', 'send', 'view_reports'],
            'expenses' => ['view', 'create', 'edit', 'view_reports'],
            'payments' => ['view', 'process', 'view_reports'],
            'reconciliation' => ['view', 'create', 'view_discrepancies'],
            'reports' => ['view', 'generate', 'export'],
            'transactions' => ['view', 'create', 'view_audit_trail'],
        ],
    ],
    
    'Branch Manager' => [
        'modules' => [
            'dashboard' => ['view', 'export', 'customize'],
            'branches' => ['view', 'edit', 'manage_settings', 'assign_users'],
            'clients' => ['all'],
            'loans' => ['all'],
            'deposits' => ['all'],
            'savings' => ['all'],
            'shares' => ['all'],
            'cash_management' => ['all'],
            'teller' => ['view', 'view_float', 'view_reports'],
            'reports' => ['all'],
            'management' => ['view_dashboard', 'view_analytics', 'view_kpi', 'view_performance'],
            'approvals' => ['all'],
        ],
    ],
    
    'Loan Officer' => [
        'modules' => [
            'dashboard' => ['view'],
            'clients' => ['view', 'create', 'edit', 'view_documents', 'view_financial'],
            'loans' => ['view', 'create', 'edit', 'manage_repayment', 'manage_collateral', 
                       'manage_guarantors', 'view_reports'],
            'active_loans' => ['all'],
            'reports' => ['view', 'generate', 'export'],
        ],
    ],
    
    'Teller' => [
        'modules' => [
            'dashboard' => ['view'],
            'teller' => ['all'],
            'cash_management' => ['view', 'cash_counting', 'view_position'],
            'deposits' => ['create', 'view'],
            'savings' => ['deposit', 'withdraw', 'view'],
            'payments' => ['process', 'view'],
            'transactions' => ['create', 'view'],
        ],
    ],
    
    'HR Manager' => [
        'modules' => [
            'dashboard' => ['view', 'export'],
            'hr' => ['all'],
            'self_services' => ['all'],
            'users' => ['view', 'create', 'edit', 'manage_roles', 'view_activity'],
            'reports' => ['view', 'generate', 'export'],
        ],
    ],
    
    'Compliance Officer' => [
        'modules' => [
            'dashboard' => ['view'],
            'reports' => ['all'],
            'management' => ['audit_management', 'compliance_management', 'risk_management'],
            'reconciliation' => ['view', 'view_discrepancies'],
            'transactions' => ['view', 'view_audit_trail'],
            'system' => ['audit', 'logs'],
        ],
    ],
    
    'Customer Service' => [
        'modules' => [
            'dashboard' => ['view'],
            'clients' => ['view', 'create', 'edit', 'upload_documents'],
            'shares' => ['view'],
            'savings' => ['view', 'view_statement'],
            'deposits' => ['view'],
            'loans' => ['view'],
            'members_portal' => ['view', 'manage_faqs', 'manage_feedback'],
        ],
    ],
    
    'Auditor' => [
        'modules' => [
            'dashboard' => ['view'],
            'accounting' => ['view_coa', 'view_ledger', 'view_trial_balance', 
                           'view_balance_sheet', 'view_income_statement', 'view_cash_flow'],
            'reports' => ['all'],
            'transactions' => ['view', 'view_audit_trail'],
            'system' => ['audit', 'logs'],
            'management' => ['audit_management', 'view_analytics'],
        ],
    ],
];

// Process each role
foreach ($rolePermissionTemplates as $roleName => $template) {
    echo "Processing Role: $roleName\n";
    echo str_repeat('-', 40) . "\n";
    
    $role = Role::where('name', $roleName)->first();
    if (!$role) {
        echo "⚠️  Role '$roleName' not found - skipping\n\n";
        continue;
    }
    
    $permissionIds = [];
    
    // Check if role should have all permissions
    if (in_array('all', $template['modules'])) {
        $permissionIds = Permission::pluck('id')->toArray();
        echo "✅ Assigning ALL permissions (Full system access)\n";
    } else {
        // Process specific module permissions
        foreach ($template['modules'] as $module => $actions) {
            if (is_numeric($module)) {
                // Simple module name without specific actions
                $module = $actions;
                $modulePermissions = Permission::where('name', 'like', $module . '.%')->get();
                foreach ($modulePermissions as $perm) {
                    $permissionIds[] = $perm->id;
                }
                echo "  • $module: All permissions\n";
            } else {
                // Module with specific actions
                if ($actions === ['all']) {
                    // All permissions for this module
                    $modulePermissions = Permission::where('name', 'like', $module . '.%')->get();
                    foreach ($modulePermissions as $perm) {
                        $permissionIds[] = $perm->id;
                    }
                    echo "  • $module: All permissions\n";
                } else {
                    // Specific actions for this module
                    foreach ($actions as $action) {
                        $permissionName = $module . '.' . $action;
                        $permission = Permission::where('name', $permissionName)->first();
                        if ($permission) {
                            $permissionIds[] = $permission->id;
                        }
                    }
                    echo "  • $module: " . implode(', ', $actions) . "\n";
                }
            }
        }
    }
    
    // Sync permissions with role
    try {
        DB::beginTransaction();
        
        $role->permissions()->sync($permissionIds);
        echo "✅ Assigned " . count($permissionIds) . " permissions to $roleName\n";
        
        // Update all users with this role
        $users = $role->users;
        foreach ($users as $user) {
            // Collect all permissions from user's roles and sub-roles
            $allPermissions = collect();
            foreach ($user->roles as $userRole) {
                $allPermissions = $allPermissions->merge($userRole->permissions);
            }
            foreach ($user->subRoles as $subRole) {
                $allPermissions = $allPermissions->merge($subRole->permissions);
            }
            
            // Sync unique permissions to user
            $user->permissions()->sync($allPermissions->pluck('id')->unique());
        }
        
        echo "✅ Updated " . $users->count() . " users with this role\n";
        
        DB::commit();
    } catch (\Exception $e) {
        DB::rollBack();
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "==================================\n";
echo "Permission assignment complete!\n";