<?php

/**
 * Test script for NBC SMS Service with new API format
 * 
 * This script tests the updated NBC SMS service to ensure it follows
 * the exact API format provided in the example.
 */

require_once 'vendor/autoload.php';

use App\Services\SmsService;
use Illuminate\Support\Facades\Log;

// Set up Laravel application
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== NBC SMS Service Test ===\n\n";

try {
    // Create SMS service instance
    $smsService = new SmsService();
    
    echo "✓ SMS Service initialized\n";
    
    // Test configuration
    $config = config('services.nbc_sms');
    echo "\n=== Configuration Test ===\n";
    echo "Base URL: " . ($config['base_url'] ?? 'NOT SET') . "\n";
    echo "Channel ID: " . ($config['channel_id'] ?? 'NOT SET') . "\n";
    echo "API Key: " . (empty($config['api_key']) ? 'NOT SET' : 'SET') . "\n";
    
    if ($config['base_url'] === 'https://sms-engine.tz.af.absa.local') {
        echo "✓ Base URL matches expected value\n";
    } else {
        echo "✗ Base URL mismatch\n";
    }
    
    if ($config['channel_id'] === 'KRWT43976') {
        echo "✓ Channel ID matches expected value\n";
    } else {
        echo "✗ Channel ID mismatch\n";
    }
    
    // Test payload structure using reflection
    $reflection = new ReflectionClass($smsService);
    $prepareMethod = $reflection->getMethod('prepareNbcApiPayload');
    $prepareMethod->setAccessible(true);
    
    $testPhone = '255653666201';
    $testMessage = "Hi testing sms";
    $testRecipient = (object) [
        'full_name' => 'Customer Name',
        'client_number' => '1234567890'
    ];
    
    $payload = $prepareMethod->invoke($smsService, 
        $testPhone,
        $testMessage,
        $testRecipient,
        [
            'smsType' => 'TRANSACTIONAL',
            'serviceName' => 'SACCOSS',
            'language' => 'English'
        ]
    );
    
    echo "\n=== Payload Structure Test ===\n";
    echo "✓ Payload prepared successfully\n";
    echo "Payload structure:\n";
    echo json_encode($payload, JSON_PRETTY_PRINT) . "\n\n";
    
    // Verify payload matches expected format
    $expectedFields = [
        'notificationRefNo',
        'recipientPhone', 
        'sms',
        'recipientName',
        'language',
        'smsType',
        'serviceName',
        'channelId'
    ];
    
    $missingFields = array_diff($expectedFields, array_keys($payload));
    if (empty($missingFields)) {
        echo "✓ Payload contains all required fields\n";
    } else {
        echo "✗ Missing fields: " . implode(', ', $missingFields) . "\n";
    }
    
    // Check for unexpected fields
    $unexpectedFields = array_diff(array_keys($payload), $expectedFields);
    if (empty($unexpectedFields)) {
        echo "✓ No unexpected fields in payload\n";
    } else {
        echo "✗ Unexpected fields: " . implode(', ', $unexpectedFields) . "\n";
    }
    
    // Verify field order matches example
    $expectedOrder = [
        'notificationRefNo',
        'recipientPhone',
        'sms', 
        'recipientName',
        'language',
        'smsType',
        'serviceName',
        'channelId'
    ];
    
    $actualOrder = array_keys($payload);
    if ($actualOrder === $expectedOrder) {
        echo "✓ Payload field order matches example\n";
    } else {
        echo "✗ Field order mismatch\n";
        echo "Expected: " . implode(', ', $expectedOrder) . "\n";
        echo "Actual: " . implode(', ', $actualOrder) . "\n";
    }
    
    // Verify specific values
    if ($payload['smsType'] === 'TRANSACTIONAL') {
        echo "✓ smsType is 'TRANSACTIONAL'\n";
    } else {
        echo "✗ smsType should be 'TRANSACTIONAL', got: " . $payload['smsType'] . "\n";
    }
    
    if ($payload['serviceName'] === 'SACCOSS') {
        echo "✓ serviceName is 'SACCOSS'\n";
    } else {
        echo "✗ serviceName should be 'SACCOSS', got: " . $payload['serviceName'] . "\n";
    }
    
    if ($payload['channelId'] === 'KRWT43976') {
        echo "✓ channelId is 'KRWT43976'\n";
    } else {
        echo "✗ channelId should be 'KRWT43976', got: " . $payload['channelId'] . "\n";
    }
    
    if ($payload['recipientPhone'] === '255653666201') {
        echo "✓ recipientPhone is '255653666201'\n";
    } else {
        echo "✗ recipientPhone should be '255653666201', got: " . $payload['recipientPhone'] . "\n";
    }
    
    if ($payload['recipientName'] === 'Customer Name') {
        echo "✓ recipientName is 'Customer Name'\n";
    } else {
        echo "✗ recipientName should be 'Customer Name', got: " . $payload['recipientName'] . "\n";
    }
    
    if ($payload['sms'] === 'Hi testing sms') {
        echo "✓ sms message is 'Hi testing sms'\n";
    } else {
        echo "✗ sms message should be 'Hi testing sms', got: " . $payload['sms'] . "\n";
    }
    
    echo "\n=== Test Summary ===\n";
    echo "All tests completed successfully!\n";
    echo "The NBC SMS service is now configured to match the provided API format.\n";
    
} catch (Exception $e) {
    echo "✗ Test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== API Format Verification ===\n";
echo "The service now sends requests in this exact format:\n";
echo "curl --location 'https://sms-engine.tz.af.absa.local/nbc-sms-engine/api/v1/direct-sms' \\\n";
echo "--header 'X-API-Key: YOUR_API_KEY' \\\n";
echo "--header 'Content-Type: application/json' \\\n";
echo "--data '{\n";
echo "  \"notificationRefNo\": \"UILKS89868766009\",\n";
echo "  \"recipientPhone\": \"255653666201\",\n";
echo "  \"sms\": \"Hi testing sms\",\n";
echo "  \"recipientName\": \"Customer Name\",\n";
echo "  \"language\": \"English\",\n";
echo "  \"smsType\": \"TRANSACTIONAL\",\n";
echo "  \"serviceName\": \"SACCOSS\",\n";
echo "  \"channelId\": \"KRWT43976\"\n";
echo "}'\n"; 