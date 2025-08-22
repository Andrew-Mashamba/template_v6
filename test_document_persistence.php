<?php

/**
 * Test script to verify document persistence across steps
 * Run with: php test_document_persistence.php
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\DB;

echo "\n=== DOCUMENT PERSISTENCE TEST ===\n\n";

try {
    // Create a new loan application component
    $component = new \App\Http\Livewire\Dashboard\LoanApplication();
    
    // Set up basic data
    $component->client_number = '10003';
    $component->selectedProductId = 1;
    $component->loanAmount = 500000;
    $component->repaymentPeriod = 12;
    $component->currentStep = 1;
    
    echo "1. Initial Setup:\n";
    echo "   - Client: {$component->client_number}\n";
    echo "   - Step: {$component->currentStep}\n";
    echo "   - Documents: " . count($component->uploadedDocuments) . "\n\n";
    
    // Simulate adding documents in step 1
    echo "2. Adding test documents in Step 1...\n";
    $component->uploadedDocuments = [
        [
            'filename' => 'test_doc_1.pdf',
            'description' => 'Test Document 1',
            'category' => 'identity',
            'size' => 100000,
            'path' => 'test/path/1.pdf'
        ],
        [
            'filename' => 'test_doc_2.pdf',
            'description' => 'Test Document 2',
            'category' => 'financial',
            'size' => 200000,
            'path' => 'test/path/2.pdf'
        ]
    ];
    $component->uploadedDocumentsCount = count($component->uploadedDocuments);
    
    echo "   - Added " . count($component->uploadedDocuments) . " documents\n";
    foreach ($component->uploadedDocuments as $doc) {
        echo "     • {$doc['filename']} ({$doc['category']})\n";
    }
    echo "\n";
    
    // Move to step 2
    echo "3. Moving to Step 2...\n";
    $component->currentStep = 2;
    $component->nextStep();
    echo "   - Current step: {$component->currentStep}\n";
    echo "   - Documents count: " . count($component->uploadedDocuments) . "\n";
    
    if (count($component->uploadedDocuments) == 2) {
        echo "   ✅ Documents persisted to Step 2!\n";
    } else {
        echo "   ❌ Documents lost when moving to Step 2!\n";
    }
    echo "\n";
    
    // Move to step 3
    echo "4. Moving to Step 3...\n";
    $component->currentStep = 3;
    $component->nextStep();
    echo "   - Current step: {$component->currentStep}\n";
    echo "   - Documents count: " . count($component->uploadedDocuments) . "\n";
    
    if (count($component->uploadedDocuments) == 2) {
        echo "   ✅ Documents persisted to Step 3!\n";
    } else {
        echo "   ❌ Documents lost when moving to Step 3!\n";
    }
    echo "\n";
    
    // Move to step 4
    echo "5. Moving to Step 4 (Documents step)...\n";
    $component->currentStep = 4;
    $component->nextStep();
    echo "   - Current step: {$component->currentStep}\n";
    echo "   - Documents count: " . count($component->uploadedDocuments) . "\n";
    
    if (count($component->uploadedDocuments) == 2) {
        echo "   ✅ Documents persisted to Step 4!\n";
        echo "   - Documents available:\n";
        foreach ($component->uploadedDocuments as $doc) {
            echo "     • {$doc['filename']} ({$doc['category']})\n";
        }
    } else {
        echo "   ❌ Documents lost when moving to Step 4!\n";
    }
    echo "\n";
    
    // Test moving back to previous step
    echo "6. Moving back to Step 3...\n";
    $component->previousStep();
    echo "   - Current step: {$component->currentStep}\n";
    echo "   - Documents count: " . count($component->uploadedDocuments) . "\n";
    
    if (count($component->uploadedDocuments) == 2) {
        echo "   ✅ Documents persisted when going back!\n";
    } else {
        echo "   ❌ Documents lost when going back!\n";
    }
    echo "\n";
    
    // Final summary
    echo "=== TEST SUMMARY ===\n";
    if (count($component->uploadedDocuments) == 2) {
        echo "✅ SUCCESS: Documents persist across all steps!\n";
        echo "   - Initial documents: 2\n";
        echo "   - Final documents: " . count($component->uploadedDocuments) . "\n";
        echo "   - All documents retained through navigation\n";
    } else {
        echo "❌ FAILURE: Documents were lost during navigation!\n";
        echo "   - Initial documents: 2\n";
        echo "   - Final documents: " . count($component->uploadedDocuments) . "\n";
        echo "   - Documents need to be preserved\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n";