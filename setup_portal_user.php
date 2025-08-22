<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

// Clear existing web portal users
DB::statement('TRUNCATE TABLE web_portal_users RESTART IDENTITY CASCADE');
echo "Cleared existing portal users.\n\n";

// Get Jane Member client
$client = DB::table('clients')
    ->where('email', 'jane.member@sacco.test')
    ->first();

if (!$client) {
    echo "Client not found!\n";
    exit(1);
}

// The plain text password
$plainPassword = 'SecurePass123!';

// Create the portal user
DB::table('web_portal_users')->insert([
    'client_id' => $client->id,
    'client_number' => $client->client_number,
    'username' => $client->member_number,
    'email' => $client->email,
    'phone' => $client->mobile_phone_number,
    'password_hash' => Hash::make($plainPassword),
    'email_verified_at' => now(),
    'is_active' => true,
    'is_locked' => false,
    'failed_login_attempts' => 0,
    'total_logins' => 0,
    'permissions' => json_encode([
        'view_dashboard',
        'view_account_balance',
        'view_transactions',
        'view_loans',
        'apply_for_loan',
        'view_shares',
        'buy_shares',
        'transfer_shares',
        'make_payments',
        'transfer_funds',
        'download_statements',
        'update_profile',
        'change_password'
    ]),
    'preferences' => json_encode([
        'theme' => 'light',
        'dashboard_layout' => 'grid',
        'show_balance' => true,
        'currency_format' => 'TZS'
    ]),
    'preferred_language' => 'en',
    'timezone' => 'Africa/Dar_es_Salaam',
    'two_factor_enabled' => false,
    'email_notifications' => true,
    'sms_notifications' => true,
    'login_notifications' => true,
    'transaction_notifications' => true,
    'portal_registered_at' => now(),
    'registered_by' => 1,
    'created_by' => 1,
    'created_at' => now(),
    'updated_at' => now(),
]);

echo "=== WEB PORTAL USER CREATED SUCCESSFULLY ===\n\n";
echo "Member Details:\n";
echo "- Name: {$client->full_name}\n";
echo "- Member #: {$client->member_number}\n";
echo "- Account #: {$client->account_number}\n";
echo "- Email: {$client->email}\n";
echo "- Phone: {$client->mobile_phone_number}\n\n";

echo "LOGIN CREDENTIALS:\n";
echo "==========================================\n";
echo "Username: {$client->member_number}\n";
echo "Email: {$client->email}\n";
echo "Password: {$plainPassword}\n";
echo "==========================================\n\n";

echo "Portal Access Details:\n";
echo "- Status: Active ✓\n";
echo "- 2FA: Disabled\n";
echo "- Notifications: All enabled\n\n";

echo "Granted Permissions:\n";
$permissions = [
    'view_dashboard',
    'view_account_balance',
    'view_transactions',
    'view_loans',
    'apply_for_loan',
    'view_shares',
    'buy_shares',
    'transfer_shares',
    'make_payments',
    'transfer_funds',
    'download_statements',
    'update_profile',
    'change_password'
];

foreach ($permissions as $permission) {
    echo "  ✓ " . str_replace('_', ' ', ucwords($permission)) . "\n";
}

echo "\nNote: User can login with either member number ({$client->member_number}) or email address.\n";