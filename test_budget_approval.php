<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\BudgetManagement;
use App\Models\approvals;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Test the budget edit approval process
echo "Testing Budget Edit Approval Process\n";
echo "=====================================\n\n";

// Find a budget with pending edit approval
$budget = BudgetManagement::where('edit_approval_status', 'PENDING')->first();

if ($budget) {
    echo "Found budget with pending edit:\n";
    echo "- ID: {$budget->id}\n";
    echo "- Name: {$budget->budget_name}\n";
    echo "- Current Revenue: {$budget->revenue}\n";
    echo "- Pending Changes: " . json_encode($budget->pending_changes) . "\n\n";
    
    // Find the corresponding approval request
    $approval = approvals::where('process_id', $budget->id)
        ->where('process_code', 'BUDGET_EDIT')
        ->where('process_status', 'PENDING')
        ->first();
    
    if ($approval) {
        echo "Found approval request:\n";
        echo "- Approval ID: {$approval->id}\n";
        echo "- Process Code: {$approval->process_code}\n";
        echo "- Edit Package: " . substr($approval->edit_package, 0, 200) . "...\n\n";
        
        // Decode the edit package
        $editPackage = json_decode($approval->edit_package, true);
        if ($editPackage && isset($editPackage['new_values'])) {
            echo "New values from edit package:\n";
            foreach ($editPackage['new_values'] as $key => $value) {
                if (!in_array($key, ['justification', 'requested_by', 'requested_at'])) {
                    echo "- {$key}: {$value}\n";
                }
            }
        }
    } else {
        echo "No approval request found for this budget.\n";
    }
} else {
    echo "No budget with pending edit approval found.\n\n";
    
    // Show all budgets
    $budgets = BudgetManagement::all();
    echo "All budgets in the system:\n";
    foreach ($budgets as $budget) {
        echo "- ID: {$budget->id}, Name: {$budget->budget_name}, Status: {$budget->status}, Edit Status: {$budget->edit_approval_status}\n";
    }
}

echo "\n";