<?php

use App\Models\User;
use App\Notifications\UserCredentialsNotification;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "TESTING USER CREDENTIALS EMAIL TEMPLATE\n";
echo "========================================\n\n";

// Find a test user or use the recently created one
$user = User::find(9); // The user with appsbongo@gmail.com

if (!$user) {
    echo "User not found. Please specify a valid user ID.\n";
    exit(1);
}

echo "User: {$user->name}\n";
echo "Email: {$user->email}\n\n";

// Test password
$testPassword = 'TestPassword123';
$departmentName = 'Information Systems';
$roleName = 'Software Developer';

echo "Sending test credentials email...\n";
echo "Department: $departmentName\n";
echo "Role: $roleName\n";
echo "Password: $testPassword\n\n";

try {
    // Send the notification
    $user->notify(new UserCredentialsNotification(
        $user,
        $testPassword,
        $departmentName,
        $roleName
    ));
    
    echo "✅ Email notification queued successfully!\n";
    echo "Check the email at: {$user->email}\n\n";
    
    echo "Expected email content:\n";
    echo "------------------------\n";
    echo "Subject: Your Account Has Been Created - " . config('app.name') . "\n";
    echo "Greeting: Dear {$user->name},\n";
    echo "- Your account has been successfully created\n";
    echo "- Email: {$user->email}\n";
    echo "- Password: $testPassword\n";
    echo "- Department: $departmentName\n";
    echo "- Role: $roleName\n";
    echo "- Instructions to change password on first login\n";
    echo "- Login button/link\n";
    echo "- Support contact information\n";
    
} catch (\Exception $e) {
    echo "❌ Error sending email: " . $e->getMessage() . "\n";
}