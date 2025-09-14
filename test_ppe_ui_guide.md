# PPE Lifecycle Testing Guide - UI Testing

## Prerequisites
1. Login to the SACCOS system
2. Navigate to **Accounting → PPE Management**
3. Ensure you have at least one active PPE asset

## Test Scenarios

### 1. TEST ACQUISITION (New PPE with Additional Costs)

**Steps:**
1. Click **"Add PPE"** button
2. Fill in the form:
   - Name: `Test Laptop Computer`
   - Category: `Computer Equipment`
   - Purchase Price: `2000`
   - Purchase Date: Today's date
   - Useful Life: `3` years
   - Salvage Value: `200`
3. Add Additional Costs:
   - Transport Cost: `50`
   - Installation Cost: `100`
   - Legal Fees: `25`
4. Click **Save**

**Verify:**
- Check General Ledger for entries:
  ```sql
  -- Run this query to verify GL entries
  SELECT * FROM general_ledger 
  WHERE narration LIKE '%Test Laptop Computer%' 
  ORDER BY created_at DESC;
  ```
- Expected: 4 entries (main + 3 additional costs)
- All should DEBIT the PPE account and CREDIT Bank account

### 2. TEST DISPOSAL

**Steps:**
1. Find PPE ID #5 (Status: disposed) or any active PPE
2. Click **Actions → Disposal**
3. Enter disposal details:
   - Sale Price: `1500`
   - Disposal Date: Today
   - Reason: `Upgraded to newer model`
4. Click **Process Disposal**

**Verify:**
```sql
-- Check disposal entries
SELECT * FROM general_ledger 
WHERE action = 'ppe_disposal' 
AND created_at > NOW() - INTERVAL '1 hour'
ORDER BY created_at DESC;

-- Check disposal record
SELECT * FROM ppe_disposals 
WHERE ppe_id = [your_ppe_id]
ORDER BY created_at DESC;
```

### 3. TEST MAINTENANCE

**Steps:**

#### 3a. Routine Maintenance (Expense)
1. Select any active PPE
2. Click **Actions → Maintenance**
3. Fill form:
   - Type: `Preventive`
   - Cost: `75`
   - Description: `Quarterly cleaning and checkup`
   - Capitalize: **NO** (unchecked)
4. Click **Save**

#### 3b. Capital Improvement (Capitalize)
1. Same PPE
2. Click **Actions → Maintenance**
3. Fill form:
   - Type: `Corrective`
   - Cost: `500`
   - Description: `RAM upgrade from 8GB to 32GB`
   - Capitalize: **YES** (checked)
4. Click **Save**

**Verify:**
```sql
-- Check maintenance entries
SELECT * FROM general_ledger 
WHERE action = 'ppe_maintenance'
AND created_at > NOW() - INTERVAL '1 hour';

-- Routine should debit Expense account
-- Capital should debit PPE account
```

### 4. TEST TRANSFER

**Steps:**
1. Select any active PPE
2. Click **Actions → Transfer**
3. Fill form:
   - Transfer To Location: `Branch Office`
   - Transfer To Department: `Finance`
   - Transfer To Custodian: `Jane Smith`
   - Transfer Date: Today
   - Transfer Type: `Inter-company` (to trigger GL entry)
4. Click **Process Transfer**

**Verify:**
```sql
-- Check transfer record
SELECT * FROM ppe_transfers 
WHERE created_at > NOW() - INTERVAL '1 hour';

-- Check GL entry (only for inter-company)
SELECT * FROM general_ledger 
WHERE action = 'ppe_transfer'
AND created_at > NOW() - INTERVAL '1 hour';
```

### 5. TEST INSURANCE

**Steps:**
1. Select any active PPE
2. Click **Actions → Insurance**
3. Fill form:
   - Policy Number: `POL-2024-001`
   - Insurance Company: `ABC Insurance`
   - Coverage Type: `Comprehensive`
   - Premium Amount: `600` (annual)
   - Start Date: Today
   - End Date: 1 year from today
4. Click **Save Insurance**

**Verify:**
```sql
-- Check insurance record
SELECT * FROM ppe_insurance 
WHERE created_at > NOW() - INTERVAL '1 hour';

-- Check GL entry
SELECT * FROM general_ledger 
WHERE action = 'ppe_insurance'
AND narration LIKE '%insurance premium%'
ORDER BY created_at DESC;
```

### 6. TEST REVALUATION

**Steps:**

#### 6a. Upward Revaluation (Appreciation)
1. Select PPE with ID #1 or #2
2. Click **Actions → Revaluation**
3. Fill form:
   - New Value: `1500` (higher than current)
   - Revaluation Date: Today
   - Reason: `Market value increase`
   - Valuation Method: `Market Comparison`
4. Click **Save Revaluation**

#### 6b. Downward Revaluation (Impairment)
1. Select another PPE
2. Click **Actions → Revaluation**
3. Fill form:
   - New Value: `500` (lower than current)
   - Revaluation Date: Today
   - Reason: `Physical damage`
   - Valuation Method: `Physical Inspection`
4. Click **Save Revaluation**

**Verify:**
```sql
-- Check revaluation entries
SELECT * FROM general_ledger 
WHERE action = 'asset_revaluation'
AND created_at > NOW() - INTERVAL '1 hour';

-- Check revaluation records
SELECT * FROM ppe_revaluations 
WHERE created_at > NOW() - INTERVAL '1 hour';

-- Upward should credit Revaluation Reserve
-- Downward should debit Impairment Loss
```

## Quick Verification Queries

### Check All Recent PPE Transactions
```sql
SELECT 
    gl.created_at,
    gl.action,
    gl.debit_account,
    gl.credit_account,
    gl.amount,
    gl.narration
FROM general_ledger gl
WHERE gl.action IN (
    'ppe_acquisition',
    'ppe_additional_cost', 
    'ppe_disposal',
    'ppe_maintenance',
    'ppe_transfer',
    'ppe_insurance',
    'asset_revaluation'
)
AND gl.created_at > NOW() - INTERVAL '1 hour'
ORDER BY gl.created_at DESC;
```

### Check Account Balances
```sql
-- Check PPE account balance changes
SELECT 
    account_number,
    account_name,
    balance,
    updated_at
FROM accounts
WHERE account_number IN (
    '010110001600', -- PPE account
    '010110002000', -- Accumulated Depreciation
    '030300001000', -- Revaluation Reserve
    '050200001000', -- Maintenance Expense
    '050400001000'  -- Impairment Loss
)
ORDER BY updated_at DESC;
```

### View PPE Summary
```sql
SELECT 
    p.id,
    p.name,
    p.purchase_price,
    p.accumulated_depreciation,
    p.closing_value,
    p.status,
    COUNT(DISTINCT pm.id) as maintenance_count,
    COUNT(DISTINCT pt.id) as transfer_count,
    COUNT(DISTINCT pi.id) as insurance_count,
    COUNT(DISTINCT pr.id) as revaluation_count
FROM ppes p
LEFT JOIN ppe_maintenance_records pm ON p.id = pm.ppe_id
LEFT JOIN ppe_transfers pt ON p.id = pt.ppe_id
LEFT JOIN ppe_insurance pi ON p.id = pi.ppe_id
LEFT JOIN ppe_revaluations pr ON p.id = pr.ppe_id
GROUP BY p.id
ORDER BY p.id;
```

## Expected Results

✅ **Each action should:**
1. Create appropriate GL entries
2. Update account balances
3. Save transaction history
4. Show success message
5. Update PPE status/values where applicable

❌ **Common Issues to Watch:**
- Missing account configurations in institutions table
- Insufficient permissions
- Invalid date formats
- Negative amounts where not allowed

## Running the Automated Test

For automated testing, run:
```bash
php test_ppe_lifecycle.php
```

This will test all scenarios automatically and show a summary.

## Monitoring Logs

Watch the Laravel log for detailed information:
```bash
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | grep -E "PPE|ppe_|asset_"
```

---
*Testing Guide for SACCOS PPE Module*
*All transactions should follow double-entry bookkeeping*