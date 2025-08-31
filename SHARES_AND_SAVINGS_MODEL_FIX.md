# Shares and Savings Model Fix

## Problem
The application was failing with the following error when trying to access member shares and savings:
```
SQLSTATE[42P01]: Undefined table: 7 ERROR: relation "shares" does not exist
```

## Root Cause
The `Share` and `Saving` models were trying to access non-existent tables:
- `Share` model was using `shares` table (doesn't exist)
- `Saving` model was using `savings` table (doesn't exist)

## Solution

### 1. Fixed Share Model

#### Before (Incorrect):
```php
// app/Models/Share.php
protected $table = 'shares';

public function member()
{
    return $this->belongsTo(Member::class);
}
```

#### After (Correct):
```php
// app/Models/Share.php
protected $table = 'accounts';

public function member()
{
    return $this->belongsTo(ClientsModel::class, 'client_number', 'client_number');
}

/**
 * Scope to only include share accounts
 */
public function scopeShares($query)
{
    return $query->where('product_number', '1000');
}
```

### 2. Fixed Saving Model

#### Before (Incorrect):
```php
// app/Models/Saving.php
protected $table = 'savings';

public function client()
{
    return $this->belongsTo(ClientsModel::class, 'client_number', 'client_number');
}
```

#### After (Correct):
```php
// app/Models/Saving.php
protected $table = 'accounts';

public function client()
{
    return $this->belongsTo(ClientsModel::class, 'client_number', 'client_number');
}

/**
 * Scope to only include savings accounts
 */
public function scopeSavings($query)
{
    return $query->where('product_number', '2000');
}
```

### 3. Updated ClientsModel Relationships

#### Before (Incorrect):
```php
// app/Models/ClientsModel.php
public function shares()
{
    return $this->hasMany(Share::class, 'client_number', 'client_number');
}

public function savings()
{
    return $this->hasMany(Saving::class, 'client_number', 'client_number');
}
```

#### After (Correct):
```php
// app/Models/ClientsModel.php
public function shares()
{
    return $this->hasMany(Share::class, 'client_number', 'client_number')->shares();
}

public function savings()
{
    return $this->hasMany(Saving::class, 'client_number', 'client_number')->savings();
}
```

## Key Changes

### 1. Table References
- **Share Model**: Now uses `accounts` table with `product_number = '1000'` filter
- **Saving Model**: Now uses `accounts` table with `product_number = '2000'` filter

### 2. Relationship Updates
- **Member Relationship**: Updated to use `ClientsModel` instead of non-existent `Member` model
- **Scope Methods**: Added scope methods to filter accounts by product type

### 3. Account Classification
- **Product Number 1000**: Share accounts
- **Product Number 2000**: Savings accounts
- **Product Number 3000**: Deposit accounts

## Testing Results

### Sample Member (00006) Relationships:
```php
Member: GONZA LO
Shares count: 1
Savings count: 1
First share balance: 50000
First savings balance: 0
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
```

## Files Modified

1. **`app/Models/Share.php`**
   - Changed table from `shares` to `accounts`
   - Updated member relationship to use `ClientsModel`
   - Added `shares()` scope method

2. **`app/Models/Saving.php`**
   - Changed table from `savings` to `accounts`
   - Added `savings()` scope method

3. **`app/Models/ClientsModel.php`**
   - Updated `shares()` relationship to use scope
   - Updated `savings()` relationship to use scope

## Benefits

### 1. Correct Data Sources
- Uses actual existing `accounts` table
- Proper filtering by product numbers
- Consistent with database structure

### 2. Relationship Integrity
- Proper foreign key relationships
- Scope-based filtering for account types
- Consistent with existing account structure

### 3. Backward Compatibility
- Maintains existing relationship methods
- Adds scope-based filtering
- No breaking changes to existing code

## Verification Commands

```bash
# Test Share and Saving model relationships
php artisan tinker --execute="
\$member = App\Models\ClientsModel::where('client_number', '00006')->first();
if(\$member) {
    echo 'Member: ' . \$member->first_name . ' ' . \$member->last_name . PHP_EOL;
    echo 'Shares count: ' . \$member->shares->count() . PHP_EOL;
    echo 'Savings count: ' . \$member->savings->count() . PHP_EOL;
    if(\$member->shares->count() > 0) {
        echo 'First share balance: ' . (\$member->shares->first()->balance ?? 'N/A') . PHP_EOL;
    }
    if(\$member->savings->count() > 0) {
        echo 'First savings balance: ' . (\$member->savings->first()->balance ?? 'N/A') . PHP_EOL;
    }
}
"
```

## Related Fixes

This fix is related to the previous **Member Exit Balance Calculation Fix** where we corrected the direct database queries. This fix addresses the Eloquent model relationships that were also trying to access the non-existent tables.

The Share and Saving models now correctly use the `accounts` table with proper filtering, ensuring all share and savings-related functionality works correctly throughout the application.
