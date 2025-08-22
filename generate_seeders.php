<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Tables to exclude from seeding (system tables, etc.)
$excludeTables = [
    'migrations',
    'failed_jobs',
    'personal_access_tokens',
    'sessions',
    'password_resets',
    'jobs',
    'audit_logs',
    'transaction_audit_logs',
    'notification_logs',
    'query_responses',
    'temp_permissions',
    'datafeeds',
    'financial_data',
    'financial_position',
    'financial_ratios',
    'cash_flow_configurations',
    'analysis_sessions',
    'scheduled_reports',
    'transaction_reconciliations',
    'transaction_retry_logs',
    'pending_registrations',
    'orders',
    'purchases',
    'tenders',
    'pay_rolls',
    'leaves',
    'leave_management',
    'employee_requests',
    'employeefiles',
    'employeests',
    'hires_approvals',
    'onboarding',
    'interviews',
    'applicants',
    'job_postings',
    'entries_types',
    'entries_amount',
    'reports',
    'scores',
    'payables',
    'receivables',
    'interest_payables',
    'loss_reserves',
    'locked_amounts',
    'loans_arreas',
    'loans_originated',
    'loans_schedules',
    'loans_summary',
    'settled_loans',
    'short_long_term_loans',
    'maendeleo_loans',
    'current_loans_stages',
    'approvers_of_loans_stages',
    'loan_process_progress',
    'loan_stages',
    'loan_audit_logs',
    'loan_approvals',
    'loan_collateral',
    'loan_collaterals',
    'loan_guarantors',
    'loan_images',
    'loan_product_charges',
    'loan_collateral',
    'loan_collaterals',
    'loan_guarantors',
    'loan_images',
    'loan_product_charges',
    'product_has_charges',
    'product_has_insurance',
    'chargeslist',
    'insurancelist',
    'insurances',
    'taxes',
    'vendors',
    'inventories',
    'investments_list',
    'investment_types',
    'asset_url',
    'assets_list',
    'landed_property_types',
    'movable_property_types',
    'main_collateral_types',
    'custom_collaterals',
    'collateral_typests',
    'collaterals',
    'guarantors',
    'groups',
    'Group_loans',
    'member_categories',
    'client_documents',
    'document_types',
    'asset_accounts',
    'income_accounts',
    'liability_accounts',
    'sub_accounts',
    'expense_accounts',
    'expense_approvals',
    'bank_statements_staging_table',
    'reconciliation_staging_table',
    'reconciled_transactions',
    'im_bank_transactions',
    'gepg_transactions',
    'payment_notifications',
    'payment_methods',
    'payments',
    'cheques',
    'cheque_books',
    'bank_transfers',
    'internal_transfers',
    'security_transport_logs',
    'teller_end_of_day_positions',
    'till_reconciliations',
    'till_transactions',
    'strongroom_ledgers',
    'cash_movements',
    'cash_in_transit_providers',
    'bank_accounts',
    'vaults',
    'tills',
    'tellers',
    'web_portal_users',
    'user_profiles',
    'user_security_profiles',
    'user_sub_menus',
    'user_sub_roles',
    'user_roles',
    'role_permissions',
    'sub_role_permissions',
    'role_menu_actions',
    'menu_actions',
    'sub_menus',
    'menus',
    'menu_list',
    'permissions',
    'sub_roles',
    'roles',
    'departments',
    'leaderships',
    'committee_approvals',
    'committees',
    'committee_members',
    'meetings',
    'meeting_attendance',
    'meeting_documents',
    'institution_files',
    'institutions',
    'branches',
    'regions',
    'districts',
    'wards',
    'mnos',
    'services',
    'deposit_types',
    'savings_types',
    'sub_products',
    'loan_sub_products',
    'loan_provision_settings',
    'mandatory_savings_settings',
    'mandatory_savings_tracking',
    'mandatory_savings_notifications',
    'password_policies',
    'process_code_configs',
    'approval_matrix_configs',
    'approval_actions',
    'approval_comments',
    'approvals',
    'notifications',
    'charges',
    'dividends',
    'issued_shares',
    'share_ownership',
    'share_transfers',
    'share_withdrawals',
    'shares',
    'share_registers',
    'standing_instructions',
    'ppes',
    'expenses',
    'administrative_expenses',
    'general_ledger',
    'account_historical_balances',
    'historical_balances',
    'main_budget',
    'main_budget_pending',
    'budget_managements',
    'ai_interactions',
    'api_keys',
    'audit_and_compliance',
    'clients',
    'users',
    'failed_jobs',
    'personal_access_tokens',
    'sessions',
    'password_resets',
    'jobs',
    'migrations'
];

// Get all tables
$tables = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name");

$seederDir = 'database/seeders';
if (!is_dir($seederDir)) {
    mkdir($seederDir, 0755, true);
}

echo "Starting seeder generation...\n";

foreach ($tables as $table) {
    $tableName = $table->table_name;
    
    // Skip excluded tables
    if (in_array($tableName, $excludeTables)) {
        echo "Skipping table: {$tableName}\n";
        continue;
    }
    
    echo "Processing table: {$tableName}\n";
    
    try {
        // Get table data
        $data = DB::table($tableName)->get();
        
        if ($data->isEmpty()) {
            echo "  - No data found, skipping\n";
            continue;
        }
        
        // Get table columns
        $columns = Schema::getColumnListing($tableName);
        
        // Create seeder filename
        $seederName = ucfirst(str_replace('_', '', $tableName)) . 'Seeder';
        $seederFile = $seederDir . '/' . $seederName . '.php';
        
        // Check if seeder already exists
        $existingSeeder = file_exists($seederFile);
        
        // Generate seeder content
        $seederContent = generateSeederContent($tableName, $seederName, $data, $columns, $existingSeeder);
        
        // Write seeder file
        file_put_contents($seederFile, $seederContent);
        
        echo "  - " . ($existingSeeder ? "Updated" : "Created") . " seeder: {$seederName}.php ({$data->count()} records)\n";
        
    } catch (Exception $e) {
        echo "  - Error processing table {$tableName}: " . $e->getMessage() . "\n";
    }
}

echo "\nSeeder generation completed!\n";

function generateSeederContent($tableName, $seederName, $data, $columns, $existingSeeder) {
    $content = "<?php\n\n";
    $content .= "use Illuminate\\Database\\Seeder;\n";
    $content .= "use Illuminate\\Support\\Facades\\DB;\n\n";
    $content .= "class {$seederName} extends Seeder\n";
    $content .= "{\n";
    $content .= "    /**\n";
    $content .= "     * Run the database seeds.\n";
    $content .= "     *\n";
    $content .= "     * @return void\n";
    $content .= "     */\n";
    $content .= "    public function run()\n";
    $content .= "    {\n";
    $content .= "        // Clear existing data\n";
    $content .= "        DB::table('{$tableName}')->truncate();\n\n";
    $content .= "        // Insert data\n";
    $content .= "        \$data = [\n";
    
    foreach ($data as $row) {
        $content .= "            [\n";
        foreach ($columns as $column) {
            if (isset($row->$column)) {
                $value = $row->$column;
                
                // Handle different data types
                if (is_null($value)) {
                    $content .= "                '{$column}' => null,\n";
                } elseif (is_numeric($value)) {
                    $content .= "                '{$column}' => {$value},\n";
                } elseif (is_bool($value)) {
                    $content .= "                '{$column}' => " . ($value ? 'true' : 'false') . ",\n";
                } else {
                    // Escape quotes in strings
                    $escapedValue = str_replace("'", "\\'", $value);
                    $content .= "                '{$column}' => '{$escapedValue}',\n";
                }
            } else {
                $content .= "                '{$column}' => null,\n";
            }
        }
        $content .= "            ],\n";
    }
    
    $content .= "        ];\n\n";
    $content .= "        foreach (\$data as \$row) {\n";
    $content .= "            DB::table('{$tableName}')->insert(\$row);\n";
    $content .= "        }\n";
    $content .= "    }\n";
    $content .= "}\n";
    
    return $content;
} 