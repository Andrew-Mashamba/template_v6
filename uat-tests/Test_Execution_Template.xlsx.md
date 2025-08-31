# Test Execution Template

This document provides a template structure for tracking UAT test execution. You can copy this into an Excel spreadsheet for easier tracking.

## Test Execution Tracking Sheet

| Test Case ID | Module | Test Case Description | Priority | Assigned To | Test Date | Status | Actual Result | Defect ID | Notes |
|--------------|--------|----------------------|----------|-------------|-----------|--------|---------------|-----------|-------|
| MM-01 | Member Management | Add new member with valid details | Critical | | | | | | |
| MM-02 | Member Management | Add member with missing mandatory fields | High | | | | | | |
| MM-03 | Member Management | Search for existing member | Medium | | | | | | |
| MM-04 | Member Management | Update member details | High | | | | | | |
| MM-05 | Member Management | Deactivate/Exit member | Critical | | | | | | |
| MM-06 | Member Management | Prevent duplicate member registration | High | | | | | | |
| SV-01 | Savings Management | Deposit savings | Critical | | | | | | |
| SV-02 | Savings Management | Withdraw savings within balance | Critical | | | | | | |
| SV-03 | Savings Management | Withdraw savings exceeding balance | High | | | | | | |
| SV-04 | Savings Management | View savings statement | Medium | | | | | | |
| SM-001 | Share Management | Share Purchase | Critical | | | | | | |
| SM-002 | Share Management | Share Redemption | Critical | | | | | | |
| SM-003 | Share Management | Share Statement | Medium | | | | | | |
| LM-01 | Loans Management | Apply for loan with valid details | Critical | | | | | | |
| LM-02 | Loans Management | Apply for loan with missing requirements | High | | | | | | |
| LM-03 | Loans Management | Approval Procedures of loan | Critical | | | | | | |
| LM-04 | Loans Management | Disburse approved loan | Critical | | | | | | |
| LM-05 | Loans Management | Repay loan (valid installment) | Critical | | | | | | |
| AC-01 | Accounting & Finance | Double Entry Validation | Critical | | | | | | |
| AC-02 | Accounting & Finance | Journal Entry Recording | High | | | | | | |
| AC-03 | Accounting & Finance | General Ledger accounts | Medium | | | | | | |
| AC-04 | Accounting & Finance | Trial Balance Generation | Critical | | | | | | |
| AC-05 | Accounting & Finance | Generate Financial Reports | High | | | | | | |
| AC-06 | Accounting & Finance | Chart of Accounts | Medium | | | | | | |
| AC-07 | Accounting & Finance | Reconciliations | High | | | | | | |
| AC-08 | Accounting & Finance | Audit Trail | Critical | | | | | | |
| RM-01 | Reports Management | Generate member reports | Medium | | | | | | |
| RM-02 | Reports Management | Generate loan reports | High | | | | | | |
| RM-03 | Reports Management | Generate Saving Reports | Medium | | | | | | |
| RM-04 | Reports Management | Accounting Reports | Critical | | | | | | |
| RM-05 | Reports Management | Share reports | Medium | | | | | | |
| RM-06 | Reports Management | TCDC, BoT compliance reports | High | | | | | | |
| US-01 | User & Security | Login with valid credentials | Critical | | | | | | |
| US-02 | User & Security | Login with invalid credentials | High | | | | | | |
| US-03 | User & Security | Change password | Medium | | | | | | |
| US-05 | User & Security | Unauthorized access attempt | Critical | | | | | | |
| SY-01 | System | Backup system data | Critical | | | | | | |
| SY-02 | System | Restore system from backup | Critical | | | | | | |
| SY-03 | System | Offline operations | Medium | | | | | | |
| SY-04 | System | High availability deployment | High | | | | | | |
| SY-05 | System | Interfaces; web, App, USSD etc. | High | | | | | | |
| SY-06 | System | API Integrations: MNOs, banks etc. | High | | | | | | |
| SY-07 | System | Support matrix | Medium | | | | | | |
| SY-08 | System | System licenses | Medium | | | | | | |
| SY-09 | System | Audit trail | Critical | | | | | | |

## Status Legend:
- **PASS** - Test case executed successfully
- **FAIL** - Test case failed
- **BLOCKED** - Test case cannot be executed
- **NOT TESTED** - Test case not yet executed

## Priority Legend:
- **Critical** - Must pass for system to be acceptable
- **High** - Important functionality
- **Medium** - Standard functionality
- **Low** - Nice to have features

## Instructions for Use:
1. Copy this template into an Excel spreadsheet
2. Fill in the "Assigned To" column with tester names
3. Update "Test Date" when executing each test case
4. Mark "Status" as PASS/FAIL/BLOCKED/NOT TESTED
5. Document "Actual Result" for any failures
6. Reference "Defect ID" if a bug is found
7. Add any additional "Notes" as needed
