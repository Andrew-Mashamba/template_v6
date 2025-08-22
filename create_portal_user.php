<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

// Get a client that has portal access enabled
$client = DB::table('clients')
    ->where('portal_access_enabled', true)
    ->where('email', 'john.demo@sacco.test')
    ->first();

if (!$client) {
    echo "No client found with portal access enabled!\n";
    exit(1);
}

// The plain text password we'll use
$plainPassword = 'Portal@2024!';

// Create the web portal user
$portalUserId = DB::table('web_portal_users')->insertGetId([
    'client_id' => $client->id,
    'client_number' => $client->client_number,
    'username' => $client->member_number, // Using member number as username
    'email' => $client->email,
    'phone' => $client->mobile_phone_number,
    'password_hash' => Hash::make($plainPassword),
    'email_verified_at' => now(),
    'is_active' => true,
    'is_locked' => false,
    'failed_login_attempts' => 0,
    'total_logins' => 0,
    'permissions' => json_encode([
        'view_account',
        'view_transactions',
        'view_loans',
        'view_shares',
        'make_payments',
        'transfer_funds',
        'view_statements'
    ]),
    'preferences' => json_encode([
        'theme' => 'light',
        'dashboard_widgets' => ['balance', 'recent_transactions', 'loan_summary'],
        'receive_push_notifications' => true
    ]),
    'preferred_language' => 'en',
    'timezone' => 'Africa/Dar_es_Salaam',
    'two_factor_enabled' => false,
    'email_notifications' => true,
    'sms_notifications' => true,
    'login_notifications' => true,
    'transaction_notifications' => true,
    'portal_registered_at' => now(),
    'registered_by' => 1, // Admin user who created this
    'created_by' => 1,
    'created_at' => now(),
    'updated_at' => now(),
]);

// Retrieve the created user
$portalUser = DB::table('web_portal_users')
    ->where('id', $portalUserId)
    ->first();

echo "=== WEB PORTAL USER CREATED ===\n\n";
echo "Client Information:\n";
echo "- Name: {$client->full_name}\n";
echo "- Member Number: {$client->member_number}\n";
echo "- Email: {$client->email}\n";
echo "- Phone: {$client->mobile_phone_number}\n";
echo "\n";
echo "Portal Access Credentials:\n";
echo "- Username: {$portalUser->username}\n";
echo "- Email: {$portalUser->email}\n";
echo "- Password: {$plainPassword}\n";
echo "\n";
echo "Portal Settings:\n";
echo "- Status: " . ($portalUser->is_active ? "Active" : "Inactive") . "\n";
echo "- Locked: " . ($portalUser->is_locked ? "Yes" : "No") . "\n";
echo "- 2FA: " . ($portalUser->two_factor_enabled ? "Enabled" : "Disabled") . "\n";
echo "- Language: {$portalUser->preferred_language}\n";
echo "- Timezone: {$portalUser->timezone}\n";
echo "\n";
echo "Permissions:\n";
$permissions = json_decode($portalUser->permissions, true);
foreach ($permissions as $permission) {
    echo "- $permission\n";
}
echo "\n";
echo "Notification Preferences:\n";
echo "- Email: " . ($portalUser->email_notifications ? "Enabled" : "Disabled") . "\n";
echo "- SMS: " . ($portalUser->sms_notifications ? "Enabled" : "Disabled") . "\n";
echo "- Login Alerts: " . ($portalUser->login_notifications ? "Enabled" : "Disabled") . "\n";
echo "- Transaction Alerts: " . ($portalUser->transaction_notifications ? "Enabled" : "Disabled") . "\n";
echo "\n";
echo "Portal User ID: {$portalUser->id}\n";
echo "Created at: {$portalUser->created_at}\n";