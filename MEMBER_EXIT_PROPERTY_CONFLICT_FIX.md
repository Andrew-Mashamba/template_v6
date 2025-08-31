# Member Exit Property Conflict Fix

## Problem
The member exit functionality was failing with the following error:
```
number_format(): Argument #1 ($num) must be of type float, Illuminate\Database\Eloquent\Collection given
```

## Root Cause
There was a conflict between:
1. **Dynamic Properties**: The `searchMemberForExit` method was setting `$member->shares` and `$member->savings` as numeric values
2. **Eloquent Relationships**: The `ClientsModel` has `shares()` and `savings()` relationships that return Eloquent Collections

When the Blade template accessed `$exitMemberDetails->shares`, it was getting the Eloquent Collection instead of the numeric value, causing `number_format()` to fail.

## Solution

### 1. Renamed Dynamic Properties

#### Before (Conflicting):
```php
// In searchMemberForExit method
$member->shares = $shares;        // Conflicts with shares() relationship
$member->savings = $savings;      // Conflicts with savings() relationship
$member->loan_balance = $loanBalance;
```

#### After (Unique Names):
```php
// In searchMemberForExit method
$member->shares_balance = $shares;    // Unique name, no conflict
$member->savings_balance = $savings;  // Unique name, no conflict
$member->loan_balance = $loanBalance;
```

### 2. Updated Process Method

#### Before:
```php
// In processMemberExit method
$settlementAmount = $this->exitMemberDetails->shares + $this->exitMemberDetails->savings;

'shares_balance' => $this->exitMemberDetails->shares,
'savings_balance' => $this->exitMemberDetails->savings,
```

#### After:
```php
// In processMemberExit method
$settlementAmount = $this->exitMemberDetails->shares_balance + $this->exitMemberDetails->savings_balance;

'shares_balance' => $this->exitMemberDetails->shares_balance,
'savings_balance' => $this->exitMemberDetails->savings_balance,
```

### 3. Updated Blade Template

#### Before:
```php
<!-- In clients.blade.php -->
<p class="text-xl font-bold text-blue-900">TZS {{ number_format($exitMemberDetails->shares ?? 0, 2) }}</p>
<p class="text-xl font-bold text-green-900">TZS {{ number_format($exitMemberDetails->savings ?? 0, 2) }}</p>
```

#### After:
```php
<!-- In clients.blade.php -->
<p class="text-xl font-bold text-blue-900">TZS {{ number_format($exitMemberDetails->shares_balance ?? 0, 2) }}</p>
<p class="text-xl font-bold text-green-900">TZS {{ number_format($exitMemberDetails->savings_balance ?? 0, 2) }}</p>
```

## Key Changes

### 1. Property Naming Convention
- **Dynamic Properties**: Use `shares_balance` and `savings_balance` for calculated values
- **Eloquent Relationships**: Keep `shares()` and `savings()` for relationship access

### 2. Data Type Safety
- **Dynamic Properties**: Always numeric values (string/integer)
- **Eloquent Relationships**: Always Eloquent Collections

### 3. Clear Separation
- **Calculated Values**: Used for display and calculations
- **Relationship Data**: Used for detailed account information

## Testing Results

### Sample Member (00006) Properties:
```php
Member: GONZA LO
Shares Balance: 50,000.00 (type: string)
Savings Balance: 0.00 (type: integer)
```

### Property Access:
```php
// Dynamic properties (numeric values)
$member->shares_balance    // 50000 (string)
$member->savings_balance   // 0 (integer)

// Eloquent relationships (collections)
$member->shares            // Eloquent Collection
$member->savings           // Eloquent Collection
```

## Files Modified

1. **`app/Http/Livewire/Clients/Clients.php`**
   - Updated `searchMemberForExit()` method to use `shares_balance` and `savings_balance`
   - Updated `processMemberExit()` method to use new property names

2. **`resources/views/livewire/clients/clients.blade.php`**
   - Updated template to use `shares_balance` and `savings_balance`

## Benefits

### 1. No Property Conflicts
- Clear separation between calculated values and relationships
- No ambiguity in property access
- Predictable data types

### 2. Type Safety
- `number_format()` receives proper numeric values
- No runtime type errors
- Consistent data handling

### 3. Maintainability
- Clear naming convention
- Easy to understand property purposes
- Future-proof for additional properties

## Verification Commands

```bash
# Test member exit balance properties
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
    
    \$member->shares_balance = \$shares;
    \$member->savings_balance = \$savings;
    
    echo 'Shares Balance: ' . number_format(\$member->shares_balance, 2) . ' (type: ' . gettype(\$member->shares_balance) . ')' . PHP_EOL;
    echo 'Savings Balance: ' . number_format(\$member->savings_balance, 2) . ' (type: ' . gettype(\$member->savings_balance) . ')' . PHP_EOL;
}
"
```

## Related Fixes

This fix complements the previous fixes:
1. **Member Exit Balance Calculation Fix** - Corrected database queries
2. **Shares and Savings Model Fix** - Fixed Eloquent model relationships
3. **Property Conflict Fix** - Resolved naming conflicts between properties and relationships

The member exit functionality now works correctly with proper data types and no conflicts between calculated values and Eloquent relationships.
