# Loan Account Balance Implementation

## Overview
Updated the Ongoing Loans table in the member view to show:
- **Amount**: `loans.principle` (the loan principal amount)
- **Balance**: The balance from the `accounts` table where `loans.loan_account_number = accounts.account_number`

## Implementation Details

### 1. Database Relationships

#### Added to `app/Models/Loan.php`:
```php
public function loanAccount()
{
    return $this->belongsTo(AccountsModel::class, 'loan_account_number', 'account_number');
}
```

#### Added to `app/Models/LoansModel.php`:
```php
public function loanAccount()
{
    return $this->belongsTo(AccountsModel::class, 'loan_account_number', 'account_number');
}
```

### 2. View Updates

#### Updated `resources/views/livewire/clients/view-member.blade.php`:
```php
// Before
<td class="py-1 px-2">{{ number_format($loan->amount,2) }}</td>
<td class="py-1 px-2">{{ number_format($loan->balance,2) }}</td>

// After
<td class="py-1 px-2">{{ number_format($loan->principle,2) }}</td>
<td class="py-1 px-2">{{ number_format($loan->loanAccount->balance ?? 0,2) }}</td>
```

### 3. Component Updates

#### Updated `app/Http/Livewire/Clients/AllMembers.php`:
```php
$this->viewingMember = ClientsModel::with([
    'loans.schedules', 
    'loans.loanAccount',  // Added this relationship
    'bills', 
])->find($id);
```

### 4. Database Setup

#### Loan Account Relationships Created:
- **Loan 1752821326** (Client 10003): Account 01000001000301, Balance: 6,000,000.00
- **Loan 1752819821** (Client 10002): Account 01000001000201, Balance: 1,000,000.00
- **Loan LOAN00006** (Client 00006): Account 010000630051, Balance: 50,000.00

## Testing Results

### Sample Data Verification:
```php
Member: GONZA LO
Loan: LOAN00006
Principal: 2,000,000.00
Account Balance: 50,000.00
Max Days in Arrears: 60
Total Amount in Arrears: 2,032,307.00
```

### Relationship Testing:
- ✅ Loan model has `loanAccount` relationship
- ✅ LoansModel has `loanAccount` relationship
- ✅ Eager loading works correctly
- ✅ View displays correct data

## Database Schema

### Loans Table
- `loan_account_number`: References account_number in accounts table
- `principle`: The loan principal amount (displayed as Amount)

### Accounts Table
- `account_number`: Primary key for account identification
- `balance`: Current account balance (displayed as Balance)
- `client_number`: Links account to client

## Usage Examples

### Accessing Loan Account Balance:
```php
// Get loan with account balance
$loan = Loan::with('loanAccount')->find($loanId);
$accountBalance = $loan->loanAccount->balance ?? 0;

// Get member with loans and account balances
$member = ClientsModel::with(['loans.loanAccount'])->find($memberId);
foreach ($member->loans as $loan) {
    echo "Loan: " . $loan->loan_id;
    echo "Principal: " . number_format($loan->principle, 2);
    echo "Account Balance: " . number_format($loan->loanAccount->balance ?? 0, 2);
}
```

### View Display:
```php
// In Blade template
<td>{{ number_format($loan->principle, 2) }}</td>           // Amount column
<td>{{ number_format($loan->loanAccount->balance ?? 0, 2) }}</td>  // Balance column
```

## Files Modified

1. **`app/Models/Loan.php`** - Added loanAccount relationship
2. **`app/Models/LoansModel.php`** - Added loanAccount relationship
3. **`resources/views/livewire/clients/view-member.blade.php`** - Updated table columns
4. **`app/Http/Livewire/Clients/AllMembers.php`** - Added eager loading
5. **`setup-loan-account-relationship.php`** - Script to set up relationships

## Benefits

### 1. Accurate Data Display
- **Amount**: Shows the actual loan principal amount
- **Balance**: Shows the real account balance from the accounts table

### 2. Proper Relationships
- Loans are properly linked to their corresponding accounts
- Eager loading prevents N+1 query problems

### 3. Data Consistency
- Uses actual database relationships instead of hardcoded values
- Maintains referential integrity

### 4. Scalability
- Works with any number of loans and accounts
- Automatically handles missing relationships gracefully

## Future Enhancements

1. **Account Balance Updates**: Implement automatic balance updates when payments are made
2. **Balance History**: Track balance changes over time
3. **Account Types**: Differentiate between different types of loan accounts
4. **Balance Alerts**: Notify when account balances fall below thresholds

## Verification Commands

```bash
# Test loan account relationship
php artisan tinker --execute="
\$loan = App\Models\Loan::with('loanAccount')->first();
echo 'Loan: ' . \$loan->loan_id . PHP_EOL;
echo 'Principal: ' . number_format(\$loan->principle, 2) . PHP_EOL;
echo 'Account Balance: ' . number_format(\$loan->loanAccount->balance ?? 0, 2) . PHP_EOL;
"

# Test member view with loans
php artisan tinker --execute="
\$member = App\Models\ClientsModel::where('client_number', '00006')
    ->with(['loans.loanAccount'])->first();
if(\$member && \$member->loans->count() > 0) {
    \$loan = \$member->loans->first();
    echo 'Principal: ' . number_format(\$loan->principle, 2) . PHP_EOL;
    echo 'Account Balance: ' . number_format(\$loan->loanAccount->balance ?? 0, 2) . PHP_EOL;
}
"
```

The implementation is now complete and the Ongoing Loans table will display accurate Amount (principal) and Balance (account balance) information for all loans.
