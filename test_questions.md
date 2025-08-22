# AI Agent Test Questions for SACCO Management System

## Overview
This comprehensive test suite covers all major functional areas of the SACCO Management System based on the table index analysis. Questions are organized by category and complexity level.

## Table Index Summary
**Total Tables Available: 109**

**Core Categories:**
- **Member Management** (clients, applicants, onboarding, groups)
- **Financial Products** (accounts, loans, shares, savings, deposits)
- **Transactions** (transactions, payments, bills, reconciliation)
- **Human Resources** (users, employees, roles, permissions)
- **Organizational Structure** (institutions, branches, departments)
- **Workflow Management** (approvals, notifications, audit_logs)
- **Reporting & Analytics** (reports, scheduled_reports, scores)
- **Cash Management** (tills, vaults, cash_movements)
- **Risk Management** (collateral_types, guarantors, loan_provision_settings)

---

## 1. BASIC COUNT QUERIES

### 1.1 Core Entity Counts
- How many members are registered in the system?
- How many users are in the system?
- How many active loans do we have?
- How many branches are there?
- How many employees work here?
- How many institutions are in the system?
- How many departments exist?
- How many different roles are defined?

### 1.2 Financial Account Counts
- How many liability accounts are there?
- How many asset accounts do we have?
- How many expense accounts are configured?
- How many revenue accounts exist?
- How many equity accounts are set up?
- How many savings accounts are active?
- How many loan accounts are there?
- How many share accounts exist?

### 1.3 Transaction and Activity Counts
- How many transactions were processed today?
- How many bills are pending payment?
- How many approval requests are pending?
- How many notifications were sent this month?
- How many audit log entries exist?
- How many reports are scheduled?
- How many guarantors are registered?

---

## 2. LISTING QUERIES

### 2.1 Basic Lists
- List all branch names
- List all department names
- List all user roles
- List all employee names
- List all institution names
- List all permission names
- List all service names
- List all charge types

### 2.2 Financial Lists
- List all liability account names
- List all asset account names
- List all expense account names
- List all revenue account names
- List all loan product names
- List all share product types
- List all savings product options
- List all charge categories

### 2.3 Status-Based Lists
- List all active members
- List all pending loan applications
- List all overdue loans
- List all active employees
- List all pending approvals
- List all active branches
- List all scheduled reports
- List all active roles

---

## 3. COMBINED COUNT AND LIST QUERIES

### 3.1 Member and Client Queries
- How many members are there and list their names
- How many active clients do we have and show their details
- How many pending applicants are there and list them
- How many group members exist and list the groups
- How many clients have guarantor relationships and list them

### 3.2 Financial Product Queries
- How many loan products are available and list them
- How many share products exist and list their names
- How many savings products are offered and list them
- How many deposit products are available and list them
- How many charges are defined and list them

### 3.3 Organizational Queries
- How many branches are there and list their locations
- How many departments exist and list their names
- How many employees work here and list their positions
- How many users have admin roles and list them
- How many institutions are registered and list them

---

## 4. FINANCIAL ANALYSIS QUERIES

### 4.1 Account Balance Queries
- What is the total balance of all liability accounts?
- What is the total balance of all asset accounts?
- Show me all accounts with balances greater than 1000000
- What are the top 10 accounts by balance?
- Which accounts have negative balances?

### 4.2 Loan Portfolio Analysis
- What is the total outstanding loan amount?
- How many loans are in arrears?
- What is the average loan amount?
- Which loans have the highest interest rates?
- Show me all loans disbursed this month

### 4.3 Transaction Analysis
- What is the total transaction volume for today?
- How many deposits were made this week?
- What are the largest transactions this month?
- Show me all transactions above 500000
- Which accounts have the most transaction activity?

---

## 5. WORKFLOW AND APPROVAL QUERIES

### 5.1 Approval Status
- How many approvals are pending?
- List all pending loan approvals
- Show me all rejected applications
- Which approvals are overdue?
- How many approvals were processed today?

### 5.2 Notification and Communication
- How many notifications were sent today?
- List all payment notifications
- Show me all mandatory savings notifications
- Which members have unread notifications?
- How many system notifications are active?

### 5.3 Audit and Compliance
- How many audit log entries were created today?
- List all user actions from the audit log
- Show me all system events this week
- Which users have the most audit entries?
- What are the most common audit events?

---

## 6. HUMAN RESOURCES QUERIES

### 6.1 Employee Management
- How many employees are in each department?
- List all employees with their job positions
- Show me all employees hired this year
- Which employees have pending requests?
- How many employees are on leave?

### 6.2 User Access and Security
- How many users have admin permissions?
- List all users with their assigned roles
- Show me all users who logged in today
- Which users have expired passwords?
- How many users are active vs inactive?

### 6.3 Role and Permission Management
- How many permissions are assigned to each role?
- List all roles with their permission counts
- Show me all menu actions available to managers
- Which roles have the most permissions?
- How many role assignments exist?

---

## 7. RISK MANAGEMENT QUERIES

### 7.1 Loan Risk Assessment
- How many loans have guarantors?
- List all collateral types accepted
- Show me all loans without collateral
- Which loans have the highest risk scores?
- How many loans are provisioned?

### 7.2 Credit Scoring
- How many members have credit scores?
- List all members with scores below 500
- Show me the average credit score by member type
- Which members have the highest credit scores?
- How many credit assessments were done this month?

### 7.3 Guarantor Management
- How many guarantors are registered?
- List all guarantors with their guarantee amounts
- Show me all loans with multiple guarantors
- Which guarantors have the highest exposure?
- How many guarantor relationships are active?

---

## 8. CASH MANAGEMENT QUERIES

### 8.1 Till and Vault Management
- How many tills are active?
- List all vault balances
- Show me all cash movements today
- Which tills have the highest balances?
- How many till reconciliations are pending?

### 8.2 Teller Operations
- How many tellers are assigned to each branch?
- List all teller end-of-day positions
- Show me all teller transactions today
- Which tellers processed the most transactions?
- How many teller reconciliations had variances?

---

## 9. PRODUCT MANAGEMENT QUERIES

### 9.1 Product Configuration
- How many sub-products are defined for each main product?
- List all product charges and their amounts
- Show me all products with insurance coverage
- Which products have the highest interest rates?
- How many product variations exist?

### 9.2 Service Management
- How many services are offered?
- List all services with their limits
- Show me all billable services
- Which services are most popular?
- How many service charges are defined?

---

## 10. REPORTING AND ANALYTICS QUERIES

### 10.1 Report Management
- How many reports are scheduled?
- List all available report types
- Show me all reports generated today
- Which reports are most frequently run?
- How many automated reports are active?

### 10.2 Business Intelligence
- What are the top 10 members by account balance?
- Show me monthly loan disbursement trends
- Which branches have the highest transaction volumes?
- What is the member growth rate this year?
- How many new accounts were opened this month?

---

## 11. COMPLEX MULTI-TABLE QUERIES

### 11.1 Member Financial Summary
- Show me all members with their total account balances
- List all members with active loans and their outstanding amounts
- How many members have both savings and loan accounts?
- Which members have the highest share contributions?
- Show me all members with guarantor relationships

### 11.2 Branch Performance Analysis
- How many members are registered at each branch?
- List all branches with their total account balances
- Show me transaction volumes by branch
- Which branches have the most loan applications?
- How many employees work at each branch?

### 11.3 Product Performance
- How many accounts exist for each product type?
- List all products with their total balances
- Show me loan products with their approval rates
- Which products generate the most fee income?
- How many members use each product type?

---

## 12. EDGE CASES AND ERROR HANDLING

### 12.1 Empty Result Sets
- How many members are from Mars?
- List all accounts with balance equal to exactly 999999999
- Show me all loans with negative interest rates
- How many users have the role "SuperAdmin"?
- List all branches in Antarctica

### 12.2 Invalid Table References
- How many records are in the customers table?
- List all data from the invalid_table
- Show me all accounts from the member_accounts table
- How many entries are in the non_existent_table?

### 12.3 Ambiguous Queries
- How many items are there?
- List all names
- Show me all data
- How many records exist?
- List everything

---

## 13. PERFORMANCE TEST QUERIES

### 13.1 Large Dataset Queries
- Show me all transactions from the last 5 years
- List all members with their complete transaction history
- How many audit log entries exist for each user?
- Show me all accounts with their transaction counts
- List all notifications sent to each member

### 13.2 Complex Aggregations
- What is the total value of all accounts by product type?
- Show me monthly transaction volumes for each branch
- How many approvals were processed by each user?
- List all members with their loan-to-savings ratios
- What are the top 100 transactions by amount?

---

## 14. BUSINESS LOGIC QUERIES

### 14.1 Compliance and Regulatory
- How many members have completed mandatory savings?
- List all loans that require additional documentation
- Show me all accounts that need KYC updates
- Which members have exceeded transaction limits?
- How many regulatory reports are overdue?

### 14.2 Operational Efficiency
- How many manual approvals are pending?
- List all automated processes that failed
- Show me all standing instructions that are active
- Which workflows have the longest processing times?
- How many exceptions require management attention?

---

## Test Categories Summary

1. **Basic Queries (50 questions)** - Simple count and list operations
2. **Financial Analysis (25 questions)** - Account balances, loan portfolios, transactions
3. **Workflow Management (20 questions)** - Approvals, notifications, audit trails
4. **Human Resources (15 questions)** - Employee and user management
5. **Risk Management (15 questions)** - Credit scoring, guarantors, collateral
6. **Cash Management (10 questions)** - Tills, vaults, cash operations
7. **Product Management (10 questions)** - Product configuration and services
8. **Reporting & Analytics (10 questions)** - Reports and business intelligence
9. **Complex Multi-table (15 questions)** - Advanced queries across multiple tables
10. **Edge Cases (10 questions)** - Error handling and invalid inputs
11. **Performance Tests (10 questions)** - Large datasets and complex aggregations
12. **Business Logic (10 questions)** - Compliance and operational queries

**Total Test Questions: 200**

---

## Expected Outcomes

### Success Criteria
- ✅ AI correctly identifies relevant tables
- ✅ Generates valid SQL queries
- ✅ Returns accurate results
- ✅ Handles edge cases gracefully
- ✅ Provides meaningful error messages
- ✅ Maintains performance under load

### Performance Benchmarks
- **Response Time**: < 10 seconds for simple queries
- **Accuracy**: > 95% for basic queries
- **Error Handling**: Graceful degradation for invalid inputs
- **Context Efficiency**: Proper use of 3-chunk system
- **Cache Utilization**: Effective use of context caching

---

## Usage Instructions

1. **Sequential Testing**: Run questions in order within each category
2. **Random Sampling**: Select random questions across categories
3. **Load Testing**: Run multiple queries simultaneously
4. **Edge Case Focus**: Emphasize error handling scenarios
5. **Performance Monitoring**: Track response times and accuracy

This comprehensive test suite ensures the AI agent can handle all aspects of the SACCO Management System effectively. 