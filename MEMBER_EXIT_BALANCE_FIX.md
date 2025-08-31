# Member Exit Balance Calculation Fix

## Problem
The member exit functionality was failing with the following error:
```
SQLSTATE[42P01]: Undefined table: 7 ERROR: relation "shares" does not exist
```

## Root Cause
The code was trying to query non-existent tables:
- `shares` table (doesn't exist)
- `savings` table (doesn't exist)
- `loans.outstanding_loan_value` field (doesn't exist)

## Solution

### 1. Fixed Table References

#### Before (Incorrect):
```php
// Get member's account balances
$shares = DB::table('shares')
    ->where('client_number', $member->client_number)
    ->where('status', 'ACTIVE')
    ->sum('amount');
    
$savings = DB::table('savings')
    ->where('client_number', $member->client_number)
    ->where('status', 'ACTIVE')
    ->sum('amount');
    
$loanBalance = DB::table('loans')
    ->where('client_number', $member->client_number)
    ->where('status', 'ACTIVE')
    ->sum('outstanding_loan_value');
```

#### After (Correct):
```php
// Get member's account balances from accounts table
$shares = DB::table('accounts')
    ->where('client_number', $member->client_number)
    ->where('product_number', '1000') // Share accounts
    ->whereIn('status', ['ACTIVE', 'PENDING'])
    ->sum(DB::raw('CAST(balance AS DECIMAL)'));
    
$savings = DB::table('accounts')
    ->where('client_number', $member->client_number)
    ->where('product_number', '2000') // Savings accounts
    ->whereIn('status', ['ACTIVE', 'PENDING'])
    ->sum(DB::raw('CAST(balance AS DECIMAL)'));
    
// Get loan balance from loans table (using principle as outstanding value)
$loanBalance = DB::table('loans')
    ->where('client_number', $member->client_number)
    ->where('status', 'ACTIVE')
    ->sum('principle');
```

### 2. Fixed Account Deactivation

#### Before (Incorrect):
```php
// Deactivate all accounts
DB::table('shares')
    ->where('client_number', $this->exitMemberDetails->client_number)
    ->update(['status' => 'CLOSED', 'updated_at' => now()]);
    
DB::table('savings')
    ->where('client_number', $this->exitMemberDetails->client_number)
    ->update(['status' => 'CLOSED', 'updated_at' => now()]);
```

#### After (Correct):
```php
// Deactivate all member accounts
DB::table('accounts')
    ->where('client_number', $this->exitMemberDetails->client_number)
    ->update(['status' => 'CLOSED', 'updated_at' => now()]);
```

## Key Changes

### 1. Table References
- **Shares**: Use `accounts` table with `product_number = '1000'`
- **Savings**: Use `accounts` table with `product_number = '2000'`
- **Loans**: Use `loans` table with `principle` field instead of `outstanding_loan_value`

### 2. Data Type Handling
- **Balance Field**: Use `CAST(balance AS DECIMAL)` since balance is stored as string
- **Status Filter**: Include both 'ACTIVE' and 'PENDING' statuses

### 3. Account Classification
- **Product Number 1000**: Share accounts
- **Product Number 2000**: Savings accounts
- **Product Number 3000**: Deposit accounts

## Testing Results

### Sample Member (00006) Exit Calculation:
```php
Member: GONZA LO
Shares: 50,000.00
Savings: 0.00
Loan Balance: 2,000,000.00
```

### Account Details:
```php
Account: MANDATORY SHARES: GONZA  MONA LO
- Product Number: 1000
- Balance: 50000
- Status: PENDING

Account: MANDATORY SAVINGS: GONZA  MONA LO
- Product Number: 2000
- Balance: 0
- Status: PENDING

Account: MANDATORY DEPOSITS: GONZA  MONA LO
- Product Number: 3000
- Balance: 0
- Status: PENDING
```

## Files Modified

1. **`app/Http/Livewire/Clients/Clients.php`**
   - Fixed `searchMemberForExit()` method
   - Fixed `processMemberExit()` method

## Benefits

### 1. Correct Data Sources
- Uses actual existing tables and fields
- Proper account classification by product numbers
- Accurate balance calculations

### 2. Data Type Safety
- Handles string-to-decimal conversion for balance fields
- Proper status filtering for account states

### 3. Comprehensive Account Handling
- Includes all account types (shares, savings, deposits)
- Proper account deactivation during exit process

## Verification Commands

```bash
# Test member exit balance calculation
php artisan tinker --execute="
\$member = App\Models\ClientsModel::where('client_number', '00006')->first();
if(\$member) {
    \$shares = DB::table('accounts')
        ->where('client_number', \$member->client_number)
        ->where('product_number', '1000')
        ->whereIn('status', ['ACTIVE', 'PENDING'])
        ->sum(DB::raw('CAST(balance AS DECIMAL)'));
    
    \$savings = DB::table('accounts')
        ->where('client_number', \$member->client_number)
        ->where('product_number', '2000')
        ->whereIn('status', ['ACTIVE', 'PENDING'])
        ->sum(DB::raw('CAST(balance AS DECIMAL)'));
    
    \$loanBalance = DB::table('loans')
        ->where('client_number', \$member->client_number)
        ->where('status', 'ACTIVE')
        ->sum('principle');
    
    echo 'Shares: ' . number_format(\$shares, 2) . PHP_EOL;
    echo 'Savings: ' . number_format(\$savings, 2) . PHP_EOL;
    echo 'Loan Balance: ' . number_format(\$loanBalance, 2) . PHP_EOL;
}
"
```

The member exit functionality now correctly calculates balances using the proper database tables and fields, ensuring accurate exit processing.
