<?php

// Bootstrap Laravel for database access
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Services\DirectClaudeCliService;
use Illuminate\Support\Facades\Log;

// Set JSON header
header('Content-Type: application/json');

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$message = $input['message'] ?? '';
$sessionId = $input['sessionId'] ?? 'test_session';

if (empty($message)) {
    echo json_encode([
        'success' => false,
        'error' => 'No message provided'
    ]);
    exit;
}

try {
    // Initialize Claude service
    $claudeService = new DirectClaudeCliService();
    
    Log::info('[TEST-AI-BACKEND] Processing message', [
        'message' => $message,
        'session_id' => $sessionId
    ]);
    
    // Send message to Claude
    $response = $claudeService->sendMessage($message, [
        'session_id' => $sessionId,
        'format' => 'text'
    ]);
    
    if ($response['success']) {
        echo json_encode([
            'success' => true,
            'message' => $response['message'],
            'processing_time' => $response['processing_time'] ?? null
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => $response['error'] ?? 'Failed to get response'
        ]);
    }
    
} catch (Exception $e) {
    Log::error('[TEST-AI-BACKEND] Error', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}