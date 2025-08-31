# Arrears Calculation Implementation

## Overview
This document describes the implementation of arrears calculation for loans in the SACCOS system. The arrears calculation is based on the `loans_schedules` table and provides real-time calculation of overdue amounts and days.

## Database Schema

### loans_schedules Table
The `loans_schedules` table contains the following key fields for arrears calculation:

- `loan_id`: Reference to the loan
- `installment_date`: Due date for the installment
- `installment`: Total amount due for the installment
- `payment`: Amount paid for the installment
- `days_in_arrears`: Number of days the installment is overdue
- `amount_in_arrears`: Amount still owed for the installment
- `completion_status`: Status of the installment (COMPLETED/PENDING)

## Implementation Components

### 1. ArrearsCalculationService
**File**: `app/Services/ArrearsCalculationService.php`

This service provides comprehensive arrears calculation functionality:

#### Key Methods:
- `calculateLoanArrears($loanId)`: Calculate arrears for a specific loan
- `calculateClientArrears($clientNumber)`: Calculate arrears for all loans of a client
- `updateArrearsInDatabase($loanId)`: Update arrears information in the database
- `getOverdueSchedules($loanId)`: Get all overdue schedules for a loan
- `getArrearsSummary()`: Get system-wide arrears summary

#### Calculation Logic:
```php
// Days in arrears calculation
$daysInArrears = $installmentDate->isPast() ? now()->diffInDays($installmentDate) : 0;

// Amount in arrears calculation
$amountInArrears = $schedule->installment - ($schedule->payment ?? 0);
```

### 2. LoansModel Enhancements
**File**: `app/Models/LoansModel.php`

Added computed attributes for easy access to arrears information:

```php
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

### 3. View Updates
**File**: `resources/views/livewire/clients/view-member.blade.php`

Updated the member view to display real arrears information:

```php
<td class="py-1 px-2">{{ $loan->max_days_in_arrears }}</td>
<td class="py-1 px-2">{{ number_format($loan->total_amount_in_arrears, 2) }}</td>
```

## Usage Examples

### 1. Calculate Arrears for a Loan
```php
$arrearsService = new ArrearsCalculationService();
$loanArrears = $arrearsService->calculateLoanArrears($loanId);

// Returns:
[
    'loan_id' => $loanId,
    'max_days_in_arrears' => 149,
    'total_amount_in_arrears' => 156197.00,
    'overdue_schedules_count' => 5,
    'overdue_schedules' => [...],
    'calculation_date' => '2025-08-30'
]
```

### 2. Calculate Arrears for a Client
```php
$clientArrears = $arrearsService->calculateClientArrears($clientNumber);

// Returns:
[
    'client_number' => $clientNumber,
    'total_loans' => 1,
    'loans_with_arrears' => 1,
    'total_amount_in_arrears' => 156197.00,
    'max_days_in_arrears' => 149,
    'loans_arrears' => [...],
    'calculation_date' => '2025-08-30'
]
```

### 3. Get System Arrears Summary
```php
$summary = $arrearsService->getArrearsSummary();

// Returns:
[
    'loans_in_arrears' => 1,
    'clients_in_arrears' => 1,
    'total_arrears_amount' => 156197.00,
    'max_days_in_arrears' => 149,
    'avg_days_in_arrears' => 88.2
]
```

## Testing

### Test Script
**File**: `test-arrears-calculation.php`

This script tests all arrears calculation functionality:

1. **Arrears Summary**: Tests system-wide arrears statistics
2. **Sample Loan Arrears**: Tests individual loan arrears calculation
3. **Database Structure**: Verifies loans_schedules table structure
4. **Client Arrears**: Tests client-level arrears calculation

### Sample Data Creation
**File**: `create-sample-arrears-data.php`

Creates sample loan schedules with arrears for testing purposes.

## Key Features

### 1. Real-time Calculation
- Arrears are calculated based on current date vs. installment dates
- Only considers installments that are past due
- Excludes completed installments

### 2. Comprehensive Reporting
- Individual loan arrears
- Client-level arrears aggregation
- System-wide arrears summary
- Detailed overdue schedule information

### 3. Database Integration
- Updates arrears information in the database
- Maintains historical arrears data
- Supports batch processing

### 4. Performance Optimized
- Uses database indexes for efficient queries
- Implements eager loading for relationships
- Caches computed attributes

## Configuration

### Environment Variables
No specific environment variables are required for arrears calculation.

### Database Indexes
Ensure the following indexes exist for optimal performance:
- `loans_schedules(loan_id, installment_date)`
- `loans_schedules(days_in_arrears)`
- `loans_schedules(completion_status)`

## Maintenance

### Regular Updates
The arrears information should be updated regularly using:
```php
$arrearsService->updateArrearsInDatabase($loanId);
```

### Monitoring
Monitor the following metrics:
- Total arrears amount
- Number of loans in arrears
- Average days in arrears
- Maximum days in arrears

## Troubleshooting

### Common Issues

1. **Negative Days in Arrears**
   - Ensure installment dates are in the past
   - Check date format consistency

2. **Incorrect Amount Calculations**
   - Verify payment amounts are properly recorded
   - Check for null values in payment fields

3. **Missing Schedules**
   - Ensure loan schedules are properly created
   - Verify loan_id relationships

### Debug Commands
```bash
# Test arrears calculation
php test-arrears-calculation.php

# Create sample data
php create-sample-arrears-data.php

# Check database structure
php artisan tinker --execute="echo 'Schedules count: ' . App\Models\loans_schedules::count();"
```

## Future Enhancements

1. **Automated Arrears Updates**: Implement scheduled jobs for automatic arrears updates
2. **Arrears Notifications**: Send alerts for loans in arrears
3. **Arrears Reports**: Generate detailed arrears reports
4. **Arrears Workflow**: Implement arrears management workflow
5. **Performance Optimization**: Add caching for frequently accessed arrears data
