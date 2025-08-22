<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

// Create consolidated migrations directory
$consolidatedDir = database_path('migrations/consolidated');
if (!file_exists($consolidatedDir)) {
    mkdir($consolidatedDir, 0755, true);
}

// Tables to consolidate based on analysis
$tablesToConsolidate = [
    'users' => [
        'migrations' => [
            '2014_10_12_000000_create_users_table.php',
            '2014_10_12_200000_add_two_factor_columns_to_users_table.php',
            '2024_03_19_000003_update_users_table_department_to_department_code.php',
            '2024_03_21_000000_add_password_changed_at_to_users_table.php',
            '2025_05_20_154826_add_otp_fields_to_users_table.php'
        ]
    ],
    'institutions' => [
        'migrations' => [
            '2024_03_13_100001_create_institutions_table.php',
            '2025_01_27_120000_add_depreciation_accounts_to_institutions_table.php',
            '2025_06_05_170000_add_missing_columns_to_institutions_table.php',
            '2025_06_05_180001_recreate_status_columns_in_institutions_table.php',
            '2025_07_09_181428_add_main_accounts_to_institutions_table.php'
        ]
    ],
    'departments' => [
        'migrations' => [
            '2024_03_13_100002_create_departments_table.php',
            '2025_05_09_130355_add_hierarchy_columns_to_departments.php',
            '2025_06_17_075658_add_branch_id_to_departments_table.php',
            '2025_07_04_130515_add_dashboard_type_to_departments_table.php'
        ]
    ],
    'loans' => [
        'migrations' => [
            '2024_03_13_create_loans_table.php',
            '2024_12_19_000004_improve_loans_table_structure.php',
            '2025_01_15_000000_add_disbursement_method_to_loans_table.php',
            '2025_06_27_000000_add_assessment_columns_to_loans_table.php',
            '2025_06_30_153053_add_loan_calculation_fields_to_loans_table.php',
            '2025_06_30_154242_add_missing_loan_fields_to_loans_table.php'
        ]
    ],
    'shares' => [
        'migrations' => [
            '2024_03_19_000002_create_shares_table.php',
            '2024_03_19_add_price_per_share_to_shares_table.php',
            '2024_03_19_add_share_product_id_to_shares_table.php',
            '2024_03_20_add_member_id_to_shares_table.php',
            '2025_06_10_035601_modify_shares_status_column_to_string.php',
            '2025_06_11_045218_add_summary_to_shares_table.php'
        ]
    ]
];

echo "=== CREATING ACTUAL CONSOLIDATED MIGRATIONS ===\n\n";

// Generate consolidated migration for users table
$usersContent = <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated migration for users table
 * 
 * This combines all migrations:
 * - 2014_10_12_000000_create_users_table.php
 * - 2014_10_12_200000_add_two_factor_columns_to_users_table.php
 * - 2024_03_19_000003_update_users_table_department_to_department_code.php
 * - 2024_03_21_000000_add_password_changed_at_to_users_table.php
 * - 2025_05_20_154826_add_otp_fields_to_users_table.php
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            // From original create
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            
            // From 2FA columns migration
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            
            // From department update migration
            $table->string('department_code')->nullable();
            
            // From password policy migration
            $table->timestamp('password_changed_at')->nullable();
            
            // From OTP fields migration
            $table->string('otp')->nullable();
            $table->string('otp_expires_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
PHP;

file_put_contents($consolidatedDir . '/2025_07_27_100000_create_users_table_consolidated.php', $usersContent);
echo "✅ Created: users table consolidated migration\n";

// Generate consolidated migration for institutions table
$institutionsContent = <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated migration for institutions table
 * 
 * This combines all migrations:
 * - 2024_03_13_100001_create_institutions_table.php
 * - 2025_01_27_120000_add_depreciation_accounts_to_institutions_table.php
 * - 2025_06_05_170000_add_missing_columns_to_institutions_table.php
 * - 2025_06_05_180001_recreate_status_columns_in_institutions_table.php
 * - 2025_07_09_181428_add_main_accounts_to_institutions_table.php
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('institutions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('status')->default('active');
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
            
            // From depreciation accounts migration
            $table->string('depreciation_expense_account')->nullable();
            $table->string('accumulated_depreciation_account')->nullable();
            
            // From missing columns migration
            $table->string('account_bad_written_off')->nullable();
            $table->string('account_interest_bad_written_off')->nullable();
            $table->string('account_dormant')->nullable();
            $table->string('account_provision_loan_portfolio')->nullable();
            $table->string('account_loan_overpayment')->nullable();
            $table->string('VAT_ON_REVENUE')->nullable();
            $table->string('Interest_In_Advance')->nullable();
            $table->string('Interest_In_Advance_accrued')->nullable();
            $table->string('account_interest_income_arrears')->nullable();
            $table->string('account_penalty_income')->nullable();
            $table->string('account_interest_income')->nullable();
            $table->string('account_ledger_fees_income')->nullable();
            $table->string('account_insurance_income')->nullable();
            $table->string('account_processing_fee_income')->nullable();
            $table->string('account_penalt_income_arrears')->nullable();
            $table->string('loan_form_fee')->nullable();
            $table->string('commission_expense')->nullable();
            $table->string('account_loan_overpayment_recovered')->nullable();
            $table->string('account_loan_written_off_recovered')->nullable();
            $table->string('account_loan_interest_written_off_recovered')->nullable();
            $table->string('interest_out_provision')->nullable();
            $table->string('provision_expense')->nullable();
            $table->string('account_share_dividend')->nullable();
            $table->string('account_income_on_investment')->nullable();
            $table->string('account_interest_receivable')->nullable();
            $table->string('account_registration_fee')->nullable();
            $table->string('bank_fee_expense')->nullable();
            $table->string('account_application_fee')->nullable();
            $table->string('account_reactivation_fee')->nullable();
            $table->string('TransferFee')->nullable();
            $table->string('Transfer_fee_expense')->nullable();
            $table->string('account_loan_outstanding')->nullable();
            $table->string('account_interest_outstanding')->nullable();
            $table->string('account_penalty_outstanding')->nullable();
            $table->string('account_vat_outstanding')->nullable();
            $table->string('account_retained_earnings')->nullable();
            $table->string('RevaluationReserve')->nullable();
            $table->string('GeneralReserve')->nullable();
            $table->string('share_capital')->nullable();
            $table->string('SharePremium')->nullable();
            $table->string('SuspenseIncomeRevenue')->nullable();
            $table->string('SuspenseAccountRevenue')->nullable();
            $table->string('FineandPenaltiesRevenue')->nullable();
            $table->string('VATONCOLLECTION')->nullable();
            $table->string('INTERESTONMEMBERSDEPOSIT')->nullable();
            $table->string('UTILITIESEXPENSE')->nullable();
            $table->string('EXPENSEINCOMERECEIVABLE')->nullable();
            $table->string('ExciseExpense')->nullable();
            $table->string('PRINTINGANDSTATIONERYEXPENSE')->nullable();
            $table->string('COMMUNICATIONEXPENSE')->nullable();
            $table->string('ADVERTISINGEXPENSE')->nullable();
            $table->string('MARKETINGEXPENSE')->nullable();
            $table->string('OFFICEEXPENSE')->nullable();
            $table->string('TRANSPORTEXPENSE')->nullable();
            $table->string('TRAVELEXPENSE')->nullable();
            $table->string('TRAININGEXPENSE')->nullable();
            $table->string('REPAIRANDMAINTENANCE')->nullable();
            $table->string('SITTINGALLOWANCE')->nullable();
            $table->string('BOARDOFEXPENSES')->nullable();
            $table->string('CONSULTANCYEXPENSE')->nullable();
            $table->string('LEGALEXPENSE')->nullable();
            $table->string('OUTSOURCINGEXPENSE')->nullable();
            $table->string('GENERALMEETING')->nullable();
            $table->string('AUDITFEES')->nullable();
            $table->string('FIRESECURITYEXPENSE')->nullable();
            $table->string('SUNDRYEXPENSE')->nullable();
            $table->string('EXCISEDUTY')->nullable();
            $table->string('OTHERCHARGES')->nullable();
            $table->string('CORPORATESOCIALINVESTMENT')->nullable();
            $table->string('CapitalGainLoss')->nullable();
            $table->string('DIVIDENDSONCAPITAL')->nullable();
            $table->string('RENTS')->nullable();
            $table->string('ENTERTAINMENTEXPENSE')->nullable();
            $table->string('FUNERALTRANSPORTEXPENSE')->nullable();
            
            // From main accounts migration
            $table->string('main_cash_account')->nullable();
            $table->string('main_bank_account')->nullable();
            $table->string('main_revenue_account')->nullable();
            $table->string('main_expense_account')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('institutions');
    }
};
PHP;

file_put_contents($consolidatedDir . '/2025_07_27_100001_create_institutions_table_consolidated.php', $institutionsContent);
echo "✅ Created: institutions table consolidated migration\n";

// Generate consolidated migration for departments table
$departmentsContent = <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated migration for departments table
 * 
 * This combines all migrations:
 * - 2024_03_13_100002_create_departments_table.php
 * - 2025_05_09_130355_add_hierarchy_columns_to_departments.php
 * - 2025_06_17_075658_add_branch_id_to_departments_table.php
 * - 2025_07_04_130515_add_dashboard_type_to_departments_table.php
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('department_code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            
            // From hierarchy columns migration
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('level')->default(0);
            $table->string('path')->nullable();
            $table->integer('order')->default(0);
            
            // From branch_id migration
            $table->unsignedBigInteger('branch_id')->nullable();
            
            // From dashboard_type migration
            $table->string('dashboard_type')->nullable();
            
            // Add indexes
            $table->index('parent_id');
            $table->index('level');
            $table->index('branch_id');
            
            // Add foreign keys
            $table->foreign('parent_id')->references('id')->on('departments')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
PHP;

file_put_contents($consolidatedDir . '/2025_07_27_100002_create_departments_table_consolidated.php', $departmentsContent);
echo "✅ Created: departments table consolidated migration\n";

// Generate consolidated migration for loans table
$loansContent = <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated migration for loans table
 * 
 * This combines all migrations:
 * - 2024_03_13_create_loans_table.php
 * - 2024_12_19_000004_improve_loans_table_structure.php
 * - 2025_01_15_000000_add_disbursement_method_to_loans_table.php
 * - 2025_06_27_000000_add_assessment_columns_to_loans_table.php
 * - 2025_06_30_153053_add_loan_calculation_fields_to_loans_table.php
 * - 2025_06_30_154242_add_missing_loan_fields_to_loans_table.php
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->string('loan_id')->unique();
            $table->string('client_number');
            $table->unsignedBigInteger('loan_sub_product_id');
            $table->decimal('principle', 15, 2);
            $table->decimal('interest', 15, 2);
            $table->integer('period');
            $table->string('interest_method');
            $table->date('loan_start_date');
            $table->date('loan_end_date');
            $table->string('status')->default('pending');
            $table->string('purpose')->nullable();
            $table->text('comments')->nullable();
            $table->timestamps();
            
            // From improve structure migration
            $table->decimal('loan_balance', 15, 2)->default(0);
            $table->string('payment_frequency')->default('monthly');
            $table->integer('grace_period')->default(0);
            $table->string('grace_period_type')->nullable();
            $table->decimal('penalty_rate', 5, 2)->default(0);
            $table->boolean('insurance_required')->default(false);
            $table->decimal('insurance_amount', 15, 2)->default(0);
            $table->string('repayment_method')->default('equal_installments');
            $table->integer('number_of_installments')->default(0);
            $table->decimal('installment_amount', 15, 2)->default(0);
            $table->date('first_payment_date')->nullable();
            $table->date('maturity_date')->nullable();
            $table->boolean('is_restructured')->default(false);
            $table->string('restructured_from_loan_id')->nullable();
            $table->date('restructured_date')->nullable();
            $table->text('restructure_reason')->nullable();
            $table->string('collateral_type')->nullable();
            $table->decimal('collateral_value', 15, 2)->default(0);
            $table->string('guarantor_name')->nullable();
            $table->string('guarantor_id_number')->nullable();
            $table->string('guarantor_phone')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('disbursed_by')->nullable();
            $table->timestamp('disbursed_at')->nullable();
            
            // From disbursement method migration
            $table->string('disbursement_method')->default('cash');
            $table->string('disbursement_reference')->nullable();
            $table->string('disbursement_account')->nullable();
            
            // From assessment columns migration
            $table->boolean('loan_assessed')->default(false);
            $table->boolean('assessment_complete')->default(false);
            $table->timestamp('assessment_date')->nullable();
            $table->string('assessment_officer')->nullable();
            $table->text('assessment_notes')->nullable();
            $table->decimal('assessment_score', 5, 2)->default(0);
            $table->string('assessment_decision')->nullable();
            $table->json('assessment_details')->nullable();
            
            // From loan calculation fields migration
            $table->decimal('processing_fee_rate', 5, 2)->default(0);
            $table->decimal('processing_fee_amount', 15, 2)->default(0);
            $table->decimal('insurance_rate', 5, 2)->default(0);
            $table->decimal('excise_duty_rate', 5, 2)->default(0);
            $table->decimal('excise_duty_amount', 15, 2)->default(0);
            $table->decimal('legal_fee_amount', 15, 2)->default(0);
            $table->decimal('valuation_fee_amount', 15, 2)->default(0);
            $table->decimal('total_charges', 15, 2)->default(0);
            $table->decimal('net_disbursement', 15, 2)->default(0);
            $table->boolean('charges_deducted_upfront')->default(true);
            $table->string('charges_payment_method')->nullable();
            $table->decimal('monthly_payment', 15, 2)->default(0);
            $table->decimal('total_interest', 15, 2)->default(0);
            $table->decimal('total_payment', 15, 2)->default(0);
            $table->decimal('effective_interest_rate', 5, 2)->default(0);
            $table->json('amortization_schedule')->nullable();
            $table->json('charges_breakdown')->nullable();
            
            // From missing loan fields migration
            $table->string('loan_account_number')->nullable();
            $table->string('interest_account_number')->nullable();
            $table->decimal('approved_amount', 15, 2)->default(0);
            $table->decimal('outstanding_balance', 15, 2)->default(0);
            $table->decimal('interest_outstanding', 15, 2)->default(0);
            $table->decimal('penalty_outstanding', 15, 2)->default(0);
            $table->decimal('total_outstanding', 15, 2)->default(0);
            $table->decimal('principal_paid', 15, 2)->default(0);
            $table->decimal('interest_paid', 15, 2)->default(0);
            $table->decimal('penalty_paid', 15, 2)->default(0);
            $table->decimal('total_paid', 15, 2)->default(0);
            $table->integer('installments_paid')->default(0);
            $table->integer('installments_remaining')->default(0);
            $table->date('last_payment_date')->nullable();
            $table->decimal('last_payment_amount', 15, 2)->default(0);
            $table->date('next_payment_date')->nullable();
            $table->decimal('next_payment_amount', 15, 2)->default(0);
            $table->integer('days_in_arrears')->default(0);
            $table->decimal('arrears_amount', 15, 2)->default(0);
            $table->string('classification')->default('normal');
            $table->decimal('provision_rate', 5, 2)->default(0);
            $table->decimal('provision_amount', 15, 2)->default(0);
            $table->boolean('is_written_off')->default(false);
            $table->date('written_off_date')->nullable();
            $table->decimal('written_off_amount', 15, 2)->default(0);
            $table->text('write_off_reason')->nullable();
            
            // Add indexes
            $table->index('loan_id');
            $table->index('client_number');
            $table->index('status');
            $table->index('loan_sub_product_id');
            $table->index('created_at');
            
            // Add foreign keys
            $table->foreign('client_number')->references('client_number')->on('clients');
            $table->foreign('loan_sub_product_id')->references('id')->on('loan_sub_products');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
PHP;

file_put_contents($consolidatedDir . '/2025_07_27_100003_create_loans_table_consolidated.php', $loansContent);
echo "✅ Created: loans table consolidated migration\n";

// Generate consolidated migration for shares table
$sharesContent = <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated migration for shares table
 * 
 * This combines all migrations:
 * - 2024_03_19_000002_create_shares_table.php
 * - 2024_03_19_add_price_per_share_to_shares_table.php
 * - 2024_03_19_add_share_product_id_to_shares_table.php
 * - 2024_03_20_add_member_id_to_shares_table.php
 * - 2025_06_10_035601_modify_shares_status_column_to_string.php
 * - 2025_06_11_045218_add_summary_to_shares_table.php
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shares', function (Blueprint $table) {
            $table->id();
            $table->string('client_number');
            $table->string('account_number');
            $table->integer('number_of_shares');
            $table->decimal('amount', 15, 2);
            $table->date('purchase_date');
            $table->string('status')->default('active');
            $table->timestamps();
            
            // From price_per_share migration
            $table->decimal('price_per_share', 10, 2)->default(0);
            
            // From share_product_id migration
            $table->unsignedBigInteger('share_product_id')->nullable();
            
            // From member_id migration
            $table->unsignedBigInteger('member_id')->nullable();
            
            // From summary migration
            $table->text('summary')->nullable();
            
            // Add indexes
            $table->index('client_number');
            $table->index('account_number');
            $table->index('status');
            $table->index('share_product_id');
            $table->index('member_id');
            
            // Add foreign keys
            $table->foreign('client_number')->references('client_number')->on('clients');
            $table->foreign('account_number')->references('account_number')->on('accounts');
            $table->foreign('share_product_id')->references('id')->on('sub_products');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shares');
    }
};
PHP;

file_put_contents($consolidatedDir . '/2025_07_27_100004_create_shares_table_consolidated.php', $sharesContent);
echo "✅ Created: shares table consolidated migration\n";

echo "\n=== CONSOLIDATION SUMMARY ===\n";
echo "Created 5 consolidated migration files in: database/migrations/consolidated/\n";
echo "\nThese files combine multiple migrations into single, comprehensive migrations.\n";
echo "They can be used as reference or in new projects instead of the fragmented files.\n";
echo "\nNOTE: Since all migrations have already run, these consolidated versions are for reference only.\n";