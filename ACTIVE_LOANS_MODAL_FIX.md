# Active Loans Modal - Repayment Schedule Fix

## Date: August 18, 2025

## Issue Description
The Detailed Loan Information Modal in the Active Loans table was failing to show repayment schedules and collaterals.

### Root Cause
The modal was using `$selectedLoan->id` (primary key) instead of `$selectedLoan->loan_id` (the actual loan ID string) when querying related tables.

### Database Structure
- **loans table**: 
  - `id` (primary key): Integer (e.g., 7)
  - `loan_id` (unique identifier): String (e.g., 'LN202508174418')
  
- **loans_schedules table**:
  - `loan_id` column stores: String values like 'LN202508174418'
  
- **collaterals table**:
  - `loan_id` column stores: String values like 'LN202508174418'

## Files Fixed

### 1. app/Http/Livewire/ActiveLoan/AllTable.php

#### Changes in `loadLoanDetails()` method:

**Before:**
```php
$this->loanSchedule = DB::table('loans_schedules')
    ->where('loan_id', $loanId)  // Using primary key ID
    ->orderBy('installment_date', 'asc')
    ->get();
```

**After:**
```php
if ($this->loanDetails) {
    $this->loanSchedule = DB::table('loans_schedules')
        ->where('loan_id', $this->loanDetails->loan_id)  // Using actual loan_id string
        ->orderBy('installment_date', 'asc')
        ->get();
}
```

Similar fixes applied to:
- `loan_status_changes` query
- `loan_restructures` query

### 2. resources/views/livewire/active-loan/all-table.blade.php

#### Three locations fixed:

1. **Line 716 - Outstanding Balance Calculation:**
   - Before: `->where('loan_id', $selectedLoan->id)`
   - After: `->where('loan_id', $selectedLoan->loan_id)`

2. **Line 827 - Collateral Query:**
   - Before: `DB::table('collaterals')->where('loan_id', $selectedLoan->id)`
   - After: `DB::table('collaterals')->where('loan_id', $selectedLoan->loan_id)`

3. **Line 1201 - Footer Display:**
   - Before: `Loan ID: {{ $selectedLoan->id }}`
   - After: `Loan ID: {{ $selectedLoan->loan_id }}`

## Test Results

### Before Fix:
- Schedules found using primary key (7): **0**
- Collaterals found using primary key: **0**
- Modal showed empty schedule section

### After Fix:
- Schedules found using loan_id ('LN202508174418'): **13**
- Collaterals found using loan_id: **Correctly queried**
- Modal properly displays all schedules with dates, amounts, and statuses

## Impact

This fix ensures:
1. ✅ Repayment schedules are displayed correctly in the modal
2. ✅ Collateral information is retrieved properly
3. ✅ Outstanding balance calculation uses correct data
4. ✅ Loan ID is displayed correctly in the modal footer

## Verification

To verify the fix works:
1. Navigate to Active Loans page
2. Click on any loan row to open the details modal
3. Scroll down to see the "Complete Payment Schedule" section
4. Confirm schedules are displayed with proper data

## Technical Note

The confusion arose because Laravel models use `id` as the primary key by convention, but the business logic uses `loan_id` as the actual identifier for relationships. This is a common pattern in legacy systems where the primary key and business key are different.

## Recommendation

Consider standardizing the relationship keys across the application to prevent similar issues in the future. Either:
1. Use foreign key constraints with the primary key `id`
2. Or consistently use `loan_id` as the relationship key

For now, the fix maintains backward compatibility with the existing data structure.