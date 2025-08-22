<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Let's create a real consolidated migration for the users table as an example
$table = 'users';

// Get table structure
$columns = DB::select("
    SELECT 
        column_name,
        data_type,
        character_maximum_length,
        is_nullable,
        column_default
    FROM information_schema.columns 
    WHERE table_name = '$table' 
    ORDER BY ordinal_position
");

// Get indexes
$indexes = DB::select("
    SELECT 
        indexname,
        indexdef
    FROM pg_indexes 
    WHERE tablename = '$table'
");

// Generate the consolidated migration
$migrationContent = "<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

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
        Schema::create('users', function (Blueprint \$table) {
";

// Add columns based on actual structure
foreach ($columns as $column) {
    $line = "            ";
    
    // Determine column type and method
    switch ($column->column_name) {
        case 'id':
            $line .= "\$table->id();";
            break;
        case 'email':
            $line .= "\$table->string('email')->unique();";
            break;
        case 'email_verified_at':
            $line .= "\$table->timestamp('email_verified_at')->nullable();";
            break;
        case 'password':
            $line .= "\$table->string('password');";
            break;
        case 'remember_token':
            $line .= "\$table->rememberToken();";
            break;
        case 'created_at':
        case 'updated_at':
            // Skip - handled by timestamps()
            continue 2;
        case 'two_factor_secret':
        case 'two_factor_recovery_codes':
            $line .= "\$table->text('$column->column_name')->nullable();";
            break;
        case 'two_factor_confirmed_at':
            $line .= "\$table->timestamp('$column->column_name')->nullable();";
            break;
        case 'password_changed_at':
            $line .= "\$table->timestamp('$column->column_name')->nullable();";
            break;
        case 'otp':
        case 'otp_expires_at':
            $line .= "\$table->string('$column->column_name')->nullable();";
            break;
        default:
            // Generic handling
            if ($column->data_type == 'bigint') {
                $line .= "\$table->bigInteger('$column->column_name')";
            } elseif ($column->data_type == 'character varying') {
                $line .= "\$table->string('$column->column_name'";
                if ($column->character_maximum_length && $column->character_maximum_length != 255) {
                    $line .= ", $column->character_maximum_length";
                }
                $line .= ")";
            } elseif ($column->data_type == 'text') {
                $line .= "\$table->text('$column->column_name')";
            } elseif ($column->data_type == 'timestamp without time zone') {
                $line .= "\$table->timestamp('$column->column_name')";
            } else {
                $line .= "\$table->string('$column->column_name')";
            }
            
            if ($column->is_nullable == 'YES') {
                $line .= "->nullable()";
            }
            $line .= ";";
    }
    
    $line .= " // From " . getSourceMigration($column->column_name);
    $migrationContent .= $line . "\n";
}

$migrationContent .= "            \$table->timestamps();
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
";

// Save the file
$filepath = database_path('migrations/consolidated/2025_07_27_093200_create_users_table_fully_consolidated.php');
file_put_contents($filepath, $migrationContent);

echo "Created fully consolidated users table migration:\n";
echo "database/migrations/consolidated/2025_07_27_093200_create_users_table_fully_consolidated.php\n\n";
echo "This migration combines all " . count($columns) . " columns from 5 separate migration files into one.\n";

function getSourceMigration($column) {
    $sources = [
        'id' => 'original create',
        'name' => 'original create',
        'email' => 'original create',
        'email_verified_at' => 'original create',
        'password' => 'original create',
        'remember_token' => 'original create',
        'created_at' => 'original create',
        'updated_at' => 'original create',
        'two_factor_secret' => '2FA columns',
        'two_factor_recovery_codes' => '2FA columns',
        'two_factor_confirmed_at' => '2FA columns',
        'department_code' => 'department update',
        'password_changed_at' => 'password policy',
        'otp' => 'OTP fields',
        'otp_expires_at' => 'OTP fields'
    ];
    
    return $sources[$column] ?? 'unknown';
}