# Loan Schedules Relationship Fix

## Issue Description
The error `Call to undefined relationship [schedules] on model [App\Models\Loan]` was occurring because the `Loan` model (used in the `ClientsModel` relationship) didn't have the `schedules` relationship defined, while the `LoansModel` did.

## Root Cause
The `ClientsModel` has a relationship to `Loan::class`:
```php
public function loans()
{
    return $this->hasMany(Loan::class, 'client_number', 'client_number');
}
```

But the `Loan` model was missing the `schedules` relationship that was needed for the `AllMembers` component's eager loading:
```php
$this->viewingMember = ClientsModel::with([
    'loans.schedules',  // This was failing
    'bills',
])->find($id);
```

## Solution Implemented

### 1. Added Schedules Relationship to Loan Model
**File**: `app/Models/Loan.php`

Added the missing `schedules` relationship and computed attributes:

```php
public function schedules()
{
    return $this->hasMany(loans_schedules::class, 'loan_id', 'loan_id');
}

/**
 * Get the maximum days in arrears for this loan
 */
public function getMaxDaysInArrearsAttribute()
{
    return $this->schedules()->max('days_in_arrears') ?? 0;
}

/**
 * Get the total amount in arrears for this loan
 */
public function getTotalAmountInArrearsAttribute()
{
    return $this->schedules()->sum('amount_in_arrears') ?? 0;
}
```

### 2. Relationship Consistency
Now both `Loan` and `LoansModel` have the same schedules relationship, ensuring consistency across the application.

## Testing Results

### Before Fix
- ❌ Error: `Call to undefined relationship [schedules] on model [App\Models\Loan]`
- ❌ AllMembers component failed to load member details
- ❌ Eager loading of schedules failed

### After Fix
- ✅ `Loan` model has `schedules` relationship
- ✅ AllMembers component loads successfully
- ✅ Eager loading works correctly
- ✅ Arrears calculation attributes work

### Test Results
```php
// Testing member with loan and schedules
Member: GONZA LO
Loan: LOAN00006
Schedules count: 24
Max days in arrears: 60
Total amount in arrears: 2,032,307.00
```

## Files Modified

1. **`app/Models/Loan.php`** - Added schedules relationship and computed attributes

## Impact

### Components Now Working
- **AllMembers Component**: Can now load member details with loan schedules
- **Member View**: Displays real arrears information instead of hardcoded zeros
- **Arrears Calculation**: Works with both `Loan` and `LoansModel` instances

### Relationships Available
```php
// Now works correctly
$member = ClientsModel::with(['loans.schedules', 'bills'])->find($id);

// Access schedules
$loan = $member->loans->first();
$schedules = $loan->schedules;

// Access arrears attributes
$maxDaysInArrears = $loan->max_days_in_arrears;
$totalAmountInArrears = $loan->total_amount_in_arrears;
```

## Verification

### Test Commands
```bash
# Test the relationship
php artisan tinker --execute="
\$member = App\Models\ClientsModel::where('client_number', '00006')
    ->with('loans.schedules')->first();
if(\$member && \$member->loans->count() > 0) {
    \$loan = \$member->loans->first();
    echo 'Schedules: ' . \$loan->schedules->count() . PHP_EOL;
    echo 'Max arrears: ' . \$loan->max_days_in_arrears . PHP_EOL;
    echo 'Total arrears: ' . number_format(\$loan->total_amount_in_arrears, 2) . PHP_EOL;
}
"

# Test AllMembers component
php artisan tinker --execute="
\$allMembers = new App\Http\Livewire\Clients\AllMembers();
\$allMembers->viewMember(6);
echo 'viewMember method executed successfully' . PHP_EOL;
"
```

## Future Considerations

1. **Model Consistency**: Ensure both `Loan` and `LoansModel` have the same relationships and attributes
2. **Code Review**: Review other models to ensure all necessary relationships are defined
3. **Testing**: Add unit tests for relationship loading
4. **Documentation**: Update model documentation to reflect all available relationships

## Conclusion

The fix ensures that the `Loan` model has all the necessary relationships and computed attributes that are expected by the application components. This resolves the relationship error and allows the member view to display accurate arrears information.
