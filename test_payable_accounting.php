<?php

/**
 * Demonstration: How Accounts Are Affected When Recording a Payable
 * 
 * This script demonstrates the accounting entries created when recording
 * a new vendor bill/payable in the SACCOS system.
 */

echo "=================================================================\n";
echo "DEMONSTRATION: ACCOUNTING ENTRIES FOR PAYABLES\n";
echo "=================================================================\n\n";

// Example Payable Data
$payableData = [
    'vendor_name' => 'ABC Office Supplies Ltd',
    'bill_number' => 'INV-2025-001',
    'bill_date' => '2025-09-15',
    'due_date' => '2025-10-15',
    'subtotal' => 500000.00,  // TZS 500,000
    'vat_rate' => 18,          // 18% VAT
    'vat_amount' => 90000.00,  // TZS 90,000
    'total_amount' => 590000.00 // TZS 590,000
];

echo "NEW PAYABLE BEING RECORDED:\n";
echo "----------------------------\n";
echo "Vendor: {$payableData['vendor_name']}\n";
echo "Bill #: {$payableData['bill_number']}\n";
echo "Date: {$payableData['bill_date']}\n";
echo "Due Date: {$payableData['due_date']}\n";
echo "Subtotal: TZS " . number_format($payableData['subtotal'], 2) . "\n";
echo "VAT ({$payableData['vat_rate']}%): TZS " . number_format($payableData['vat_amount'], 2) . "\n";
echo "Total: TZS " . number_format($payableData['total_amount'], 2) . "\n\n";

echo "=================================================================\n";
echo "DOUBLE-ENTRY ACCOUNTING ENTRIES CREATED:\n";
echo "=================================================================\n\n";

// Entry 1: Debit Expense Account
echo "Entry 1: DEBIT EXPENSE ACCOUNT\n";
echo "--------------------------------\n";
echo "Account: Office Supplies Expense (050200001000)\n";
echo "Type: Expense Account\n";
echo "Debit: TZS " . number_format($payableData['subtotal'], 2) . "\n";
echo "Credit: TZS 0.00\n";
echo "Effect: Increases expense by base amount (excluding VAT)\n\n";

// Entry 2: Debit VAT Account
echo "Entry 2: DEBIT INPUT VAT ACCOUNT\n";
echo "---------------------------------\n";
echo "Account: Input VAT Receivable (011500001000)\n";
echo "Type: Current Asset\n";
echo "Debit: TZS " . number_format($payableData['vat_amount'], 2) . "\n";
echo "Credit: TZS 0.00\n";
echo "Effect: Records recoverable VAT as an asset\n\n";

// Entry 3: Credit Accounts Payable
echo "Entry 3: CREDIT ACCOUNTS PAYABLE\n";
echo "---------------------------------\n";
echo "Account: Trade and Other Payables (010110002000)\n";
echo "Type: Current Liability\n";
echo "Debit: TZS 0.00\n";
echo "Credit: TZS " . number_format($payableData['total_amount'], 2) . "\n";
echo "Effect: Increases liability for amount owed to vendor\n\n";

// Summary
echo "=================================================================\n";
echo "ACCOUNTING EQUATION CHECK:\n";
echo "=================================================================\n";
$totalDebits = $payableData['subtotal'] + $payableData['vat_amount'];
$totalCredits = $payableData['total_amount'];
echo "Total Debits:  TZS " . number_format($totalDebits, 2) . "\n";
echo "Total Credits: TZS " . number_format($totalCredits, 2) . "\n";
echo "Balance: " . ($totalDebits == $totalCredits ? "✓ BALANCED" : "✗ NOT BALANCED") . "\n\n";

echo "=================================================================\n";
echo "FINANCIAL STATEMENT IMPACT:\n";
echo "=================================================================\n\n";

echo "BALANCE SHEET CHANGES:\n";
echo "----------------------\n";
echo "Assets:\n";
echo "  Current Assets:\n";
echo "    Input VAT Receivable: + TZS " . number_format($payableData['vat_amount'], 2) . "\n";
echo "\nLiabilities:\n";
echo "  Current Liabilities:\n";
echo "    Accounts Payable: + TZS " . number_format($payableData['total_amount'], 2) . "\n\n";

echo "INCOME STATEMENT CHANGES:\n";
echo "-------------------------\n";
echo "Operating Expenses:\n";
echo "  Office Supplies: + TZS " . number_format($payableData['subtotal'], 2) . "\n\n";

echo "=================================================================\n";
echo "WHEN PAYMENT IS MADE (FUTURE):\n";
echo "=================================================================\n\n";

echo "Entry 1: DEBIT ACCOUNTS PAYABLE\n";
echo "--------------------------------\n";
echo "Account: Trade and Other Payables (010110002000)\n";
echo "Debit: TZS " . number_format($payableData['total_amount'], 2) . "\n";
echo "Credit: TZS 0.00\n";
echo "Effect: Reduces liability (payable cleared)\n\n";

echo "Entry 2: CREDIT BANK ACCOUNT\n";
echo "-----------------------------\n";
echo "Account: Bank Account (010110001600)\n";
echo "Debit: TZS 0.00\n";
echo "Credit: TZS " . number_format($payableData['total_amount'], 2) . "\n";
echo "Effect: Reduces bank balance (cash outflow)\n\n";

echo "=================================================================\n";
echo "KEY ACCOUNTS INVOLVED:\n";
echo "=================================================================\n\n";

$accounts = [
    [
        'name' => 'Office Supplies Expense',
        'number' => '050200001000',
        'type' => 'Expense',
        'normal_balance' => 'Debit',
        'increases_with' => 'Debit'
    ],
    [
        'name' => 'Input VAT Receivable',
        'number' => '011500001000',
        'type' => 'Current Asset',
        'normal_balance' => 'Debit',
        'increases_with' => 'Debit'
    ],
    [
        'name' => 'Trade and Other Payables',
        'number' => '010110002000',
        'type' => 'Current Liability',
        'normal_balance' => 'Credit',
        'increases_with' => 'Credit'
    ],
    [
        'name' => 'Bank Account',
        'number' => '010110001600',
        'type' => 'Current Asset',
        'normal_balance' => 'Debit',
        'increases_with' => 'Debit'
    ]
];

foreach ($accounts as $account) {
    echo "Account: {$account['name']}\n";
    echo "  Number: {$account['number']}\n";
    echo "  Type: {$account['type']}\n";
    echo "  Normal Balance: {$account['normal_balance']}\n";
    echo "  Increases With: {$account['increases_with']}\n\n";
}

echo "=================================================================\n";
echo "PROCESS FLOW IN THE SYSTEM:\n";
echo "=================================================================\n\n";

echo "1. User creates new payable in Trade & Other Payables module\n";
echo "2. System validates all required fields\n";
echo "3. System begins database transaction\n";
echo "4. Payable record created in 'trade_payables' table\n";
echo "5. General Ledger entries created:\n";
echo "   a. Debit expense account (base amount)\n";
echo "   b. Debit VAT account (VAT amount) if applicable\n";
echo "   c. Credit payables account (total amount)\n";
echo "6. Account balances updated in 'accounts' table\n";
echo "7. Transaction committed if all successful\n";
echo "8. Notification job dispatched (if vendor email provided)\n";
echo "9. Payable appears in reports:\n";
echo "   - Accounts Payable listing\n";
echo "   - Aging analysis\n";
echo "   - Cash flow projections\n";
echo "   - Trial Balance\n";
echo "   - Balance Sheet\n";
echo "   - Income Statement\n\n";

echo "=================================================================\n";
echo "END OF DEMONSTRATION\n";
echo "=================================================================\n";