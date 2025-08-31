# Member Exit Calculation Troubleshooting Guide

## Issue Description
The member exit calculation is showing all zeros instead of the correct financial data.

## Root Cause Analysis
The calculation method `calculateMemberExitData()` is working correctly, but the user needs to click the "Search Member" button to trigger the calculation.

## Expected Data for Member 00006
Based on our testing, member 00006 should show:
- **Shares Balance**: TZS 50,000.00
- **Savings Balance**: TZS 0.00
- **Deposits Balance**: TZS 0.00
- **Loan Balance**: TZS 50,000.00
- **Unpaid Bills**: TZS 201,010.00
- **Dividends**: TZS 5,750.00
- **Interest on Savings**: TZS 6,000.00
- **Total Credits**: TZS 61,750.00
- **Total Debits**: TZS 251,010.00
- **Final Settlement**: TZS -189,260.00 (Member owes this amount)

## Step-by-Step Solution

### 1. Navigate to Member Exit Section
1. Go to the Clients page
2. Find the "Member Exit" tab or section
3. Look for the member exit form

### 2. Search for Member
1. In the "Member Number" field, enter: `00006`
2. **Click the "Search Member" button**
3. Wait for the page to refresh/update

### 3. Verify Results
After clicking "Search Member", you should see:
- Member information displayed
- Comprehensive exit calculation with the correct amounts
- Final settlement amount showing TZS -189,260.00

## Troubleshooting Steps

### If Still Showing Zeros:

#### 1. Check Browser Console
1. Open browser developer tools (F12)
2. Go to Console tab
3. Look for any JavaScript errors
4. If errors found, refresh the page and try again

#### 2. Verify Livewire Connection
1. Check if Livewire is properly loaded
2. Look for Livewire connection status in browser console
3. Ensure no network connectivity issues

#### 3. Manual Test
If the button doesn't work, you can test the calculation manually:

```bash
# Run this command to test the calculation
php artisan tinker --execute="
\$component = new App\Http\Livewire\Clients\Clients();
\$component->exitMemberNumber = '00006';
\$reflection = new ReflectionClass(\$component);
\$method = \$reflection->getMethod('searchMemberForExit');
\$method->setAccessible(true);
\$method->invoke(\$component);
if(\$component->exitMemberDetails) {
    echo 'Member: ' . \$component->exitMemberDetails->first_name . ' ' . \$component->exitMemberDetails->last_name . PHP_EOL;
    echo 'Shares Balance: ' . number_format(\$component->exitMemberDetails->exit_shares_balance, 2) . PHP_EOL;
    echo 'Final Settlement: ' . number_format(\$component->exitMemberDetails->exit_final_settlement, 2) . PHP_EOL;
}
"
```

## Expected Output After Search

```
Member Information
Member Name: GONZA LO
Member Number: 00006
Phone Number: 0692410353
Current Status: ACTIVE

Exit Calculation Summary
Final Settlement Amount: TZS -189,260.00
Member owes this amount

Credits (+)
Shares Balance: TZS 50,000.00
Savings Balance: TZS 0.00
Deposits Balance: TZS 0.00
Dividends: TZS 5,750.00
Interest on Savings: TZS 6,000.00
Total Credits: TZS 61,750.00

Debits (-)
Loan Balance: TZS 50,000.00
Unpaid Bills: TZS 201,010.00
Total Debits: TZS 251,010.00

Summary
1 Active Accounts
1 Active Loans
1 Unpaid Bills
-189,260 Settlement
```

## Common Issues and Solutions

### Issue 1: Button Not Responding
**Solution**: Check browser console for JavaScript errors

### Issue 2: Page Not Refreshing
**Solution**: Ensure Livewire is properly loaded and connected

### Issue 3: Still Showing Zeros After Search
**Solution**: 
1. Clear browser cache
2. Refresh the page
3. Try searching again

### Issue 4: Method Not Found Error
**Solution**: Ensure the Livewire component is properly registered and the method exists

## Verification Commands

### Test Database Data
```bash
# Verify member exists
php artisan tinker --execute="
\$member = App\Models\ClientsModel::where('client_number', '00006')->first();
echo 'Member: ' . (\$member ? \$member->first_name . ' ' . \$member->last_name : 'Not found') . PHP_EOL;
"
```

### Test Calculation Method
```bash
# Test calculation directly
php artisan tinker --execute="
\$member = App\Models\ClientsModel::where('client_number', '00006')->first();
if(\$member) {
    \$shares = DB::table('accounts')
        ->where('client_number', \$member->client_number)
        ->where('product_number', '1000')
        ->whereIn('status', ['ACTIVE', 'PENDING'])
        ->sum(DB::raw('CAST(balance AS DECIMAL)'));
    echo 'Shares: ' . number_format(\$shares, 2) . PHP_EOL;
}
"
```

## Technical Details

### Method Flow
1. User enters member number (00006)
2. User clicks "Search Member" button
3. `searchMemberForExit()` method is called
4. Member is found in database
5. `calculateMemberExitData()` method is called
6. All financial calculations are performed
7. Results are stored in `$exitMemberDetails`
8. Page is updated with calculated values

### Key Methods
- `searchMemberForExit()`: Main entry point for member search
- `calculateMemberExitData()`: Performs all financial calculations
- `processMemberExit()`: Handles the actual exit processing

### Database Tables Used
- `clients`: Member information
- `accounts`: Account balances (shares, savings, deposits)
- `loans`: Loan information
- `bills`: Unpaid bills
- `dividends`: Dividend amounts
- `interest_payables`: Interest on savings

## Support

If the issue persists after following these steps:
1. Check browser console for errors
2. Verify Livewire is working properly
3. Test with a different member number
4. Contact system administrator if needed

The calculation logic is working correctly - the issue is likely with the user interface or JavaScript execution.
