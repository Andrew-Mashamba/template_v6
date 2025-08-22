<?php

/**
 * Test script to verify loan_images table and test document saving
 * Run with: php test_loan_documents.php
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "\n=== LOAN DOCUMENTS TEST SCRIPT ===\n\n";

try {
    // 1. Check if loan_images table exists
    echo "1. Checking loan_images table structure...\n";
    $columns = DB::select("SELECT column_name, data_type, is_nullable, column_default 
                           FROM information_schema.columns 
                           WHERE table_name = 'loan_images' 
                           ORDER BY ordinal_position");
    
    echo "   Table columns:\n";
    foreach ($columns as $column) {
        echo "   - {$column->column_name} ({$column->data_type})" . 
             ($column->is_nullable === 'NO' ? ' NOT NULL' : '') . "\n";
    }
    echo "\n";
    
    // 2. Count existing records
    echo "2. Checking existing records...\n";
    $totalRecords = DB::table('loan_images')->count();
    echo "   Total records in loan_images: $totalRecords\n\n";
    
    // 3. Check a sample loan
    echo "3. Checking loan LN202508173563...\n";
    $sampleLoan = DB::table('loans')->where('loan_id', 'LN202508173563')->first();
    if ($sampleLoan) {
        echo "   Loan found - ID: {$sampleLoan->id}, Client: {$sampleLoan->client_number}\n";
        
        // Check documents for this loan
        $documents = DB::table('loan_images')->where('loan_id', 'LN202508173563')->get();
        echo "   Documents for this loan: " . $documents->count() . "\n";
        
        if ($documents->count() > 0) {
            foreach ($documents as $doc) {
                echo "     - {$doc->filename} ({$doc->category})\n";
            }
        }
    } else {
        echo "   Loan not found\n";
    }
    echo "\n";
    
    // 4. Test inserting a document
    echo "4. Testing document insert...\n";
    $testLoanId = 'TEST_' . date('YmdHis');
    $testData = [
        'loan_id' => $testLoanId,
        'filename' => 'test_document_' . time() . '.pdf',
        'original_name' => 'Test Document.pdf',
        'url' => 'loan_applications/documents/general/test_' . time() . '.pdf',
        'file_size' => 1024000,
        'mime_type' => 'application/pdf',
        'category' => 'general',
        'document_category' => 'general',
        'document_descriptions' => 'Test document from CLI script',
        'created_at' => now(),
        'updated_at' => now()
    ];
    
    echo "   Inserting test document with loan_id: $testLoanId\n";
    
    try {
        $insertId = DB::table('loan_images')->insertGetId($testData);
        echo "   ✅ SUCCESS! Document inserted with ID: $insertId\n";
        
        // Verify the insert
        $verifyRecord = DB::table('loan_images')->find($insertId);
        if ($verifyRecord) {
            echo "   ✅ Verified: Record exists in database\n";
            echo "     - Loan ID: {$verifyRecord->loan_id}\n";
            echo "     - Filename: {$verifyRecord->filename}\n";
            echo "     - Category: {$verifyRecord->category}\n";
        } else {
            echo "   ❌ ERROR: Could not verify inserted record\n";
        }
        
        // Clean up test record
        DB::table('loan_images')->where('id', $insertId)->delete();
        echo "   🧹 Test record cleaned up\n";
        
    } catch (Exception $e) {
        echo "   ❌ ERROR inserting document: " . $e->getMessage() . "\n";
    }
    echo "\n";
    
    // 5. Test with actual loan ID format
    echo "5. Testing with actual loan ID format (LN202508173563)...\n";
    $actualTestData = [
        'loan_id' => 'LN202508173563',
        'filename' => 'test_actual_' . time() . '.pdf',
        'original_name' => 'Test Actual Document.pdf',
        'url' => 'loan_applications/documents/general/test_actual_' . time() . '.pdf',
        'file_size' => 2048000,
        'mime_type' => 'application/pdf',
        'category' => 'identity',
        'document_category' => 'identity',
        'document_descriptions' => 'Test document with actual loan ID',
        'created_at' => now(),
        'updated_at' => now()
    ];
    
    try {
        $insertId2 = DB::table('loan_images')->insertGetId($actualTestData);
        echo "   ✅ SUCCESS! Document inserted with ID: $insertId2\n";
        echo "   This document is now associated with loan LN202508173563\n";
        
        // Check all documents for this loan
        $allDocs = DB::table('loan_images')->where('loan_id', 'LN202508173563')->get();
        echo "   Total documents for LN202508173563: " . $allDocs->count() . "\n";
        
        // Optional: Remove test record (comment out if you want to keep it)
        // DB::table('loan_images')->where('id', $insertId2)->delete();
        // echo "   🧹 Test record cleaned up\n";
        
    } catch (Exception $e) {
        echo "   ❌ ERROR inserting document: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== TEST COMPLETE ===\n\n";
    
} catch (Exception $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}