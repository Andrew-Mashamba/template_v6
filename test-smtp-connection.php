<?php

/**
 * Test SMTP connection and send a test email
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Mail;

// Boot Laravel
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "========================================\n";
echo "  SMTP Connection Test\n";
echo "========================================\n\n";

// Get SMTP configuration
$config = config('mail');
echo "📧 Mail Configuration:\n";
echo "   Mailer: " . ($config['default'] ?? 'smtp') . "\n";
echo "   Host: " . env('MAIL_HOST', 'localhost') . "\n";
echo "   Port: " . env('MAIL_PORT', 25) . "\n";
echo "   Encryption: " . env('MAIL_ENCRYPTION', 'null') . "\n";
echo "   Username: " . env('MAIL_USERNAME', 'not set') . "\n";
echo "   From: " . env('MAIL_FROM_ADDRESS', 'noreply@example.com') . "\n";
echo "   From Name: " . env('MAIL_FROM_NAME', 'Laravel') . "\n\n";

// Test sending a simple email
try {
    echo "📨 Sending test email to andrew.s.mashamba@gmail.com...\n";
    
    Mail::raw('This is a test email from SACCOS Core System to verify SMTP configuration.', function ($message) {
        $message->to('andrew.s.mashamba@gmail.com')
                ->subject('SACCOS SMTP Test - ' . date('Y-m-d H:i:s'));
    });
    
    echo "✅ Test email sent successfully!\n";
    echo "   Check your inbox at andrew.s.mashamba@gmail.com\n\n";
    
} catch (Exception $e) {
    echo "❌ Failed to send test email:\n";
    echo "   Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    
    // Try to get more details
    if (method_exists($e, 'getDebug')) {
        echo "   Debug Info:\n";
        echo "   " . $e->getDebug() . "\n\n";
    }
}

echo "========================================\n\n";
exit(0);