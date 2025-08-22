# Till and Cash Management System Documentation

## Table of Contents
1. [System Overview](#system-overview)
2. [Architecture & Components](#architecture--components)
3. [User Roles & Permissions](#user-roles--permissions)
4. [Installation & Setup](#installation--setup)
5. [Daily Operations Workflow](#daily-operations-workflow)
6. [User Guide by Role](#user-guide-by-role)
7. [Technical Specifications](#technical-specifications)
8. [Security & Compliance](#security--compliance)
9. [Troubleshooting](#troubleshooting)
10. [API Documentation](#api-documentation)

---

## System Overview

The Till and Cash Management System is a comprehensive cash operations platform designed for deposit-taking institutions such as SACCOs, microfinance institutions, and banks. It provides secure, auditable, and professional cash handling workflows with role-based access control.

### Key Features
- **Till Management**: Complete lifecycle from opening to closing
- **Transaction Processing**: Deposits, withdrawals, and vault transfers
- **Reconciliation**: End-of-day cash counting and variance tracking
- **Strongroom Operations**: Centralized vault management with supervisor controls
- **Audit Trail**: Complete logging of all cash movements
- **Real-time Reporting**: Live statistics and transaction monitoring

### Business Benefits
- ✅ **Compliance**: Meets banking industry standards for cash management
- ✅ **Security**: Role-based access with two-person control for critical operations
- ✅ **Accuracy**: Automated calculations and variance detection
- ✅ **Efficiency**: Streamlined workflows reduce processing time
- ✅ **Transparency**: Complete audit trail for regulatory compliance

---

## Architecture & Components

### Database Schema

#### Core Tables
```sql
-- Tills: Cash drawers assigned to tellers
tills (
    id, teller_id, opening_balance, current_balance, closing_balance,
    status, opened_at, closed_at, opened_by, closed_by, timestamps
)

-- Tellers: Staff members who operate tills
tellers (
    id, user_id, till_id, status, hired_date, supervisor_id, timestamps
)

-- Strongroom: Vault cash management
strongroom_ledger (
    id, balance, last_audit_date, last_audit_by, security_level, location, timestamps
)

-- Transaction Records
till_transactions (
    id, till_id, member_id, account_id, type, amount, 
    balance_before, balance_after, narration, reference, created_by, timestamps
)

-- Cash Movements: Transfers between tills and vault
cash_movements (
    id, from_type, to_type, from_id, to_id, amount, narration,
    created_by, approved_by, status, timestamps
)

-- Reconciliation Records
till_reconciliations (
    id, till_id, expected_balance, counted_balance, difference,
    notes, reconciled_by, reviewed_by, reconciled_at, reviewed_at, timestamps
)
```

#### Relationships
- **Users** → **Tellers** (1:1): Each user can be assigned as a teller
- **Tellers** → **Tills** (1:1): Each teller is assigned one till
- **Tills** → **TillTransactions** (1:many): Till can have multiple transactions
- **Tills** → **TillReconciliations** (1:many): Till can have multiple reconciliations
- **Users** → **CashMovements** (1:many): Users create and approve movements

### Laravel Components

#### Models
- `Till.php` - Till management with status tracking
- `Teller.php` - Teller assignments and relationships
- `StrongroomLedger.php` - Vault balance and audit tracking
- `TillTransaction.php` - Individual transaction records
- `CashMovement.php` - Transfer logging between tills/vault
- `TillReconciliation.php` - End-of-day reconciliation records

#### Livewire Component
- `TillAndCashManagement.php` - Main component handling all till operations
- Methods: `openTill()`, `closeTill()`, `processTransaction()`, `reconcileTill()`, `processVaultTransfer()`

#### Views
- `till-and-cash-management.blade.php` - Complete UI with tabs and modals

---

## User Roles & Permissions

### 👤 Teller Role
**Responsibilities:**
- Open and close assigned till
- Process customer transactions (deposits/withdrawals)
- Perform end-of-day reconciliation
- Request vault transfers (with supervisor approval)

**Access Level:**
- ✅ Till Dashboard
- ✅ Transaction Processing
- ✅ Transaction History (own till)
- ✅ Till Reconciliation
- ❌ Strongroom Management
- ❌ Other Tills Overview

### 👨‍💼 Supervisor Role
**Responsibilities:**
- All teller functions
- Manage strongroom operations
- Approve cash transfers
- Monitor all tills
- Review reconciliation discrepancies

**Access Level:**
- ✅ All Teller Functions
- ✅ Strongroom Management
- ✅ All Tills Overview
- ✅ Vault Transfer Authorization
- ✅ Cash Movement History

### 👨‍💻 Admin Role
**Responsibilities:**
- Complete system access
- User and till setup
- System configuration
- Audit and compliance reporting

**Access Level:**
- ✅ All Functions
- ✅ System Administration
- ✅ User Management
- ✅ Configuration Settings

---

## Installation & Setup

### Prerequisites
```bash
- PHP 8.1+
- Laravel 10+
- MySQL 8.0+
- Livewire 3.0+
- Tailwind CSS 3.0+
```

### Step 1: Database Migration
```bash
# Run the migrations to create required tables
php artisan migrate --path=database/migrations/2025_07_07_065547_create_internal_transfers_table.php
php artisan migrate --path=database/migrations/xxxx_create_tills_table.php
php artisan migrate --path=database/migrations/xxxx_create_tellers_table.php
php artisan migrate --path=database/migrations/xxxx_create_strongroom_ledger_table.php
php artisan migrate --path=database/migrations/xxxx_create_cash_movements_table.php
php artisan migrate --path=database/migrations/xxxx_create_till_transactions_table.php
php artisan migrate --path=database/migrations/xxxx_create_till_reconciliations_table.php
```

### Step 2: Seed Initial Data
```php
// Create strongroom record
StrongroomLedger::create([
    'balance' => 50000.00,
    'last_audit_date' => now(),
    'security_level' => 'high',
    'location' => 'Main Vault'
]);

// Create sample tills
Till::create([
    'teller_id' => null,
    'opening_balance' => 0,
    'current_balance' => 0,
    'status' => 'closed'
]);
```

### Step 3: User Role Assignment
```php
// Assign roles to users
$user = User::find(1);
$user->assignRole('teller'); // or 'supervisor' or 'admin'

// Create teller record
Teller::create([
    'user_id' => $user->id,
    'till_id' => 1,
    'status' => 'active',
    'hired_date' => now()
]);
```

### Step 4: Route Configuration
```php
// Add to web.php
Route::middleware(['auth'])->group(function () {
    Route::get('/accounting/till-management', TillAndCashManagement::class)
        ->name('till-management');
});
```

---

## Daily Operations Workflow

### 🌅 Start of Day Process

#### 1. Teller Login
```
1. Teller logs into system
2. Navigates to Till & Cash Management
3. System shows assigned till status: "Closed"
```

#### 2. Till Opening
```
1. Click "Open Till" button
2. Enter opening balance (from vault or previous day)
3. System validation:
   - Amount > 0
   - User has teller permissions
   - Till is currently closed
4. System updates:
   - Till status → "Open"
   - Current balance = Opening balance
   - Records opening timestamp and user
5. Till ready for transactions
```

### 💰 Transaction Processing

#### Cash Deposit Process
```
1. Click "New Transaction" button
2. Select "Cash Deposit"
3. Enter:
   - Amount
   - Member/Account details
   - Description
4. System validation:
   - Till must be open
   - Amount > 0
   - Required fields completed
5. System updates:
   - Till balance increases
   - Creates transaction record
   - Updates balance_after field
6. Receipt generated (optional)
```

#### Cash Withdrawal Process
```
1. Click "New Transaction" button
2. Select "Cash Withdrawal"
3. Enter transaction details
4. System validation:
   - Till must be open
   - Sufficient till balance
   - Amount > 0
5. System updates:
   - Till balance decreases
   - Creates transaction record
   - Updates balance_after field
```

#### Vault Transfer Process
```
Teller Request:
1. Select "Transfer to Vault" or "Transfer from Vault"
2. Enter amount and description
3. System creates pending transfer request

Supervisor Approval:
1. Supervisor accesses Strongroom tab
2. Reviews transfer request
3. Approves/rejects transfer
4. System processes approved transfers
```

### 🌙 End of Day Process

#### Till Reconciliation
```
1. Teller clicks "Close Till"
2. System displays reconciliation form:
   - Opening balance
   - Expected balance (calculated)
   - Input field for counted cash
3. Teller counts physical cash
4. Enters counted amount
5. System calculates difference:
   - Balanced: Counted = Expected
   - Over: Counted > Expected
   - Short: Counted < Expected
6. System creates reconciliation record
7. Till status → "Closed"
8. Generates reconciliation report
```

#### Supervisor Review
```
1. Supervisor reviews reconciliation reports
2. Investigates any discrepancies
3. Documents findings
4. Approves till closure
5. Authorizes next day opening balance
```

---

## User Guide by Role

### 👤 Teller Daily Operations

#### Login and Dashboard
```
1. Login to system
2. Navigate to Till & Cash Management
3. Dashboard shows:
   - Your till status and balance
   - Today's transaction summary
   - Quick action buttons
   - Recent activity feed
```

#### Opening Your Till
```
1. Ensure you have opening cash from vault/supervisor
2. Click "Open Till" in dashboard
3. Enter exact amount of cash in till
4. Click "Open Till" button
5. Verify till status shows "Open"
```

#### Processing Transactions
```
For Deposits:
1. Click "New Transaction" 
2. Select "Cash Deposit"
3. Enter customer details and amount
4. Add description
5. Click "Process Transaction"
6. Verify balance update

For Withdrawals:
1. Verify customer identity and account balance
2. Click "New Transaction"
3. Select "Cash Withdrawal" 
4. Enter details and amount
5. Verify sufficient till cash
6. Process transaction
```

#### Viewing Transaction History
```
1. Click "Transactions" tab
2. Use filters to find specific transactions:
   - Date range
   - Transaction type
   - Search by description
3. View detailed transaction records
4. Export reports if needed
```

#### Closing Your Till
```
1. Complete all pending transactions
2. Count physical cash in till
3. Click "Close Till" button
4. Enter counted cash amount
5. Review difference (if any)
6. Add notes explaining variances
7. Submit reconciliation
8. Print reconciliation report
```

### 👨‍💼 Supervisor Operations

#### Monitoring All Tills
```
1. Access "Strongroom" tab
2. View "All Tills Overview":
   - Till status (open/closed)
   - Current balances
   - Assigned tellers
   - Activity timestamps
3. Identify issues requiring attention
```

#### Processing Vault Transfers
```
1. Navigate to Strongroom tab
2. Click "Transfer Funds" 
3. Select direction (to/from till)
4. Choose target till
5. Enter transfer amount
6. Verify balances
7. Process transfer
8. Notify teller of completed transfer
```

#### Reviewing Reconciliations
```
1. Access "Reconciliation" tab
2. Review daily reconciliation reports
3. Investigate discrepancies:
   - Contact tellers for explanations
   - Review transaction history
   - Document findings
4. Approve satisfactory reconciliations
5. Escalate significant variances
```

#### Cash Movement Oversight
```
1. Monitor all cash movements
2. Review transfer logs
3. Ensure proper authorization
4. Verify audit trail completeness
5. Generate movement reports
```

---

## Technical Specifications

### Frontend Technology Stack
```
- **Framework**: Livewire 3.0 (Laravel)
- **Styling**: Tailwind CSS 3.0
- **JavaScript**: Alpine.js (via Livewire)
- **Icons**: Heroicons
- **Responsive**: Mobile-first design
```

### Backend Technology Stack
```
- **Framework**: Laravel 10+
- **Database**: MySQL 8.0+
- **Authentication**: Laravel Sanctum/Breeze
- **Authorization**: Spatie Laravel Permission
- **Validation**: Laravel Form Requests
- **Logging**: Laravel Log Channels
```

### Performance Optimizations
```
- Database indexing on frequently queried fields
- Livewire pagination for large datasets
- Eager loading for relationships
- Query optimization for reporting
- Caching for frequently accessed data
```

### API Endpoints (Future Enhancement)
```php
// Transaction endpoints
POST /api/till/transactions
GET  /api/till/transactions/{tillId}
PUT  /api/till/transactions/{id}

// Till management
POST /api/till/open
POST /api/till/close
GET  /api/till/status/{tillId}

// Reconciliation
POST /api/till/reconcile
GET  /api/reconciliation/history

// Strongroom operations
POST /api/strongroom/transfer
GET  /api/strongroom/balance
GET  /api/strongroom/movements
```

---

## Security & Compliance

### Security Measures

#### Authentication & Authorization
```
- Multi-factor authentication support
- Role-based access control (RBAC)
- Session timeout configuration
- Password complexity requirements
- Failed login attempt monitoring
```

#### Data Protection
```
- All sensitive data encrypted at rest
- TLS encryption for data in transit
- Database connection encryption
- Audit trail immutability
- Secure file upload handling
```

#### Transaction Security
```
- Double-entry validation
- Balance integrity checks
- Concurrent transaction handling
- Database transaction rollback
- Anti-fraud monitoring
```

### Compliance Features

#### Audit Requirements
```
- Complete transaction logging
- User action tracking
- Timestamp integrity
- Change history maintenance
- Regulatory report generation
```

#### Banking Standards
```
- Two-person authorization for vault operations
- Segregation of duties
- Daily reconciliation requirements
- Cash limit enforcement
- Supervisor approval workflows
```

#### Data Retention
```
- 7+ years transaction history
- Reconciliation record preservation
- User activity log retention
- System backup procedures
- Archive and retrieval processes
```

---

## Troubleshooting

### Common Issues and Solutions

#### Till Won't Open
```
Problem: "Till is already open" error
Solution: 
1. Check till status in database
2. Verify no duplicate teller assignments
3. Review previous closure process
4. Contact supervisor for override
```

#### Transaction Failures
```
Problem: "Insufficient till balance" 
Solution:
1. Verify current till balance
2. Check for pending transactions
3. Request vault transfer if needed
4. Refresh till balance display

Problem: "Till must be open to process transactions"
Solution:
1. Verify till status
2. Re-open till if necessary
3. Check user permissions
4. Verify teller assignment
```

#### Reconciliation Discrepancies
```
Problem: Large variances in cash count
Solution:
1. Recount physical cash
2. Review all transactions for the day
3. Check for unprocessed transactions
4. Verify opening balance accuracy
5. Document investigation findings
```

#### System Performance Issues
```
Problem: Slow transaction processing
Solution:
1. Check database performance
2. Review network connectivity
3. Clear browser cache
4. Optimize database queries
5. Monitor server resources
```

### Error Codes and Messages

#### Validation Errors
```
E001: "Amount must be greater than zero"
E002: "Till must be open to process transactions"
E003: "Insufficient till balance for withdrawal"
E004: "From and to accounts must be different"
E005: "Both accounts must be active"
```

#### System Errors
```
E101: "Database connection failed"
E102: "Transaction rollback occurred"
E103: "File upload failed"
E104: "Permission denied for operation"
E105: "Session expired, please login again"
```

#### Business Logic Errors
```
E201: "Till is already open"
E202: "User is not assigned to a till"
E203: "Supervisor approval required"
E204: "Reconciliation already completed"
E205: "Invalid account type for transfer"
```

---

## Best Practices

### Daily Operations
```
1. Always count cash before opening till
2. Process transactions immediately
3. Keep physical receipts organized
4. Report discrepancies immediately
5. Complete reconciliation daily
6. Secure till when away from desk
7. Verify customer identity for withdrawals
8. Document unusual transactions
```

### System Usage
```
1. Log out when leaving workstation
2. Use strong, unique passwords
3. Report system issues immediately
4. Keep software updated
5. Regular data backups
6. Monitor for suspicious activity
7. Follow authorization procedures
8. Maintain audit trail integrity
```

### Cash Management
```
1. Minimize cash exposure time
2. Use dual control for large amounts
3. Regular cash limit reviews
4. Immediate reporting of losses
5. Secure storage protocols
6. Regular vault audits
7. Insurance coverage verification
8. Emergency procedures training
```

---

## Conclusion

The Till and Cash Management System provides a comprehensive, secure, and compliant platform for financial institution cash operations. By following the processes and procedures outlined in this documentation, institutions can ensure efficient, accurate, and auditable cash management while meeting regulatory requirements.

For additional support or custom modifications, please contact the development team.

---

**Document Version**: 1.0  
**Last Updated**: January 2025  
**Prepared By**: Development Team  
**Review Date**: January 2026 