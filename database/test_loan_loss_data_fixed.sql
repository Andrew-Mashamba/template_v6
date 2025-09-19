-- Test Data for Loan Loss Reserve Management
-- Fixed version for actual table structure

-- First, let's create test clients if they don't exist
INSERT INTO clients (client_number, first_name, middle_name, last_name, status, created_at, updated_at)
SELECT * FROM (VALUES 
    ('TEST001', 'John', 'Robert', 'Smith', 'ACTIVE', NOW(), NOW()),
    ('TEST002', 'Mary', 'Jane', 'Johnson', 'ACTIVE', NOW(), NOW()),
    ('TEST003', 'Michael', 'David', 'Williams', 'ACTIVE', NOW(), NOW()),
    ('TEST004', 'Sarah', 'Elizabeth', 'Brown', 'ACTIVE', NOW(), NOW()),
    ('TEST005', 'James', 'Alexander', 'Davis', 'ACTIVE', NOW(), NOW()),
    ('TEST006', 'Patricia', 'Ann', 'Miller', 'ACTIVE', NOW(), NOW()),
    ('TEST007', 'Robert', 'Thomas', 'Wilson', 'ACTIVE', NOW(), NOW()),
    ('TEST008', 'Jennifer', 'Marie', 'Moore', 'ACTIVE', NOW(), NOW())
) AS v(client_number, first_name, middle_name, last_name, status, created_at, updated_at)
WHERE NOT EXISTS (SELECT 1 FROM clients WHERE client_number = v.client_number);

-- Insert test loans with various statuses and delinquency levels
-- Using the actual table structure

-- Loan 1: Current (0-30 days) - Performing well
INSERT INTO loans (
    loan_account_number, client_number, principle, interest, 
    loan_balance, total_principal_paid, total_interest_paid, status,
    created_at, updated_at
) 
SELECT 'LN2024TEST001', 'TEST001', 5000000.00, 900000.00,
    4500000.00, 500000.00, 100000.00, 'ACTIVE', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM loans WHERE loan_account_number = 'LN2024TEST001');

-- Loan 2: Watch (31-60 days) - Starting to fall behind
INSERT INTO loans (
    loan_account_number, client_number, principle, interest,
    loan_balance, total_principal_paid, total_interest_paid, status,
    created_at, updated_at
) 
SELECT 'LN2024TEST002', 'TEST002', 2000000.00, 480000.00,
    1800000.00, 200000.00, 50000.00, 'ACTIVE', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM loans WHERE loan_account_number = 'LN2024TEST002');

-- Loan 3: Substandard (61-90 days) - Significant arrears
INSERT INTO loans (
    loan_account_number, client_number, principle, interest,
    loan_balance, total_principal_paid, total_interest_paid, status,
    created_at, updated_at
) 
SELECT 'LN2024TEST003', 'TEST003', 3000000.00, 540000.00,
    2900000.00, 100000.00, 20000.00, 'ACTIVE', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM loans WHERE loan_account_number = 'LN2024TEST003');

-- Loan 4: Doubtful (91-180 days) - High risk
INSERT INTO loans (
    loan_account_number, client_number, principle, interest,
    loan_balance, total_principal_paid, total_interest_paid, status,
    created_at, updated_at
) 
SELECT 'LN2024TEST004', 'TEST004', 500000.00, 150000.00,
    490000.00, 10000.00, 5000.00, 'ACTIVE', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM loans WHERE loan_account_number = 'LN2024TEST004');

-- Loan 5: Loss (>180 days) - Candidate for write-off
INSERT INTO loans (
    loan_account_number, client_number, principle, interest,
    loan_balance, total_principal_paid, total_interest_paid, status,
    created_at, updated_at
) 
SELECT 'LN2024TEST005', 'TEST005', 1000000.00, 240000.00,
    1000000.00, 0.00, 0.00, 'ACTIVE', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM loans WHERE loan_account_number = 'LN2024TEST005');

-- Loan 6: Another Loss (>180 days) - Candidate for write-off
INSERT INTO loans (
    loan_account_number, client_number, principle, interest,
    loan_balance, total_principal_paid, total_interest_paid, status,
    created_at, updated_at
) 
SELECT 'LN2024TEST006', 'TEST006', 8000000.00, 1440000.00,
    7950000.00, 50000.00, 10000.00, 'ACTIVE', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM loans WHERE loan_account_number = 'LN2024TEST006');

-- Loan 7: Written Off - For recovery testing
INSERT INTO loans (
    loan_account_number, client_number, principle, interest,
    loan_balance, total_principal_paid, total_interest_paid, status,
    total_recovered, created_at, updated_at
) 
SELECT 'LN2024TEST007', 'TEST007', 1500000.00, 360000.00,
    1450000.00, 50000.00, 10000.00, 'WRITTEN_OFF', 0.00, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM loans WHERE loan_account_number = 'LN2024TEST007');

-- Loan 8: Written Off with partial recovery - For recovery testing
INSERT INTO loans (
    loan_account_number, client_number, principle, interest,
    loan_balance, total_principal_paid, total_interest_paid, status,
    total_recovered, created_at, updated_at
) 
SELECT 'LN2024TEST008', 'TEST008', 300000.00, 90000.00,
    280000.00, 20000.00, 5000.00, 'WRITTEN_OFF', 50000.00, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM loans WHERE loan_account_number = 'LN2024TEST008');

-- Now insert loan schedules for each loan
-- Clean up any existing test schedules first
DELETE FROM loans_schedules WHERE loan_id IN (
    SELECT CAST(id AS VARCHAR) FROM loans WHERE loan_account_number LIKE 'LN2024TEST%'
);

-- Insert schedules for Loan 1: Current (15 days overdue on last installment)
INSERT INTO loans_schedules (
    loan_id, installment, principle, interest, payment,
    principle_payment, interest_payment, bank_account_number,
    installment_date, last_payment_date, days_in_arrears,
    completion_status, created_at, updated_at
)
SELECT 
    CAST(l.id AS VARCHAR),
    491666.67, -- monthly installment
    416666.67, -- principal portion
    75000.00,  -- interest portion
    CASE 
        WHEN generate_series = 12 THEN 0 -- Last installment unpaid
        ELSE 491666.67 
    END as payment,
    CASE 
        WHEN generate_series = 12 THEN 0
        ELSE 416666.67
    END as principle_payment,
    CASE 
        WHEN generate_series = 12 THEN 0
        ELSE 75000.00
    END as interest_payment,
    l.loan_account_number,
    CURRENT_DATE - INTERVAL '12 months' + (generate_series * INTERVAL '1 month'),
    CASE 
        WHEN generate_series = 12 THEN NULL
        ELSE CURRENT_DATE - INTERVAL '12 months' + (generate_series * INTERVAL '1 month')
    END,
    CASE 
        WHEN generate_series = 12 THEN 15
        ELSE 0
    END as days_in_arrears,
    CASE 
        WHEN generate_series = 12 THEN 'PENDING'
        ELSE 'PAID'
    END,
    NOW(), NOW()
FROM loans l, generate_series(1, 12)
WHERE l.loan_account_number = 'LN2024TEST001';

-- Insert schedules for Loan 2: Watch (45 days overdue)
INSERT INTO loans_schedules (
    loan_id, installment, principle, interest, payment,
    principle_payment, interest_payment, bank_account_number,
    installment_date, last_payment_date, days_in_arrears,
    completion_status, created_at, updated_at
)
SELECT 
    CAST(l.id AS VARCHAR),
    206666.67,
    166666.67,
    40000.00,
    CASE 
        WHEN generate_series >= 11 THEN 0 -- Last 2 installments unpaid
        ELSE 206666.67 
    END,
    CASE 
        WHEN generate_series >= 11 THEN 0
        ELSE 166666.67
    END,
    CASE 
        WHEN generate_series >= 11 THEN 0
        ELSE 40000.00
    END,
    l.loan_account_number,
    CURRENT_DATE - INTERVAL '12 months' + (generate_series * INTERVAL '1 month'),
    CASE 
        WHEN generate_series >= 11 THEN NULL
        ELSE CURRENT_DATE - INTERVAL '12 months' + (generate_series * INTERVAL '1 month')
    END,
    CASE 
        WHEN generate_series = 11 THEN 45
        WHEN generate_series = 12 THEN 15
        ELSE 0
    END,
    CASE 
        WHEN generate_series >= 11 THEN 'PENDING'
        ELSE 'PAID'
    END,
    NOW(), NOW()
FROM loans l, generate_series(1, 12)
WHERE l.loan_account_number = 'LN2024TEST002';

-- Insert schedules for Loan 3: Substandard (75 days overdue)
INSERT INTO loans_schedules (
    loan_id, installment, principle, interest, payment,
    principle_payment, interest_payment, bank_account_number,
    installment_date, last_payment_date, days_in_arrears,
    completion_status, created_at, updated_at
)
SELECT 
    CAST(l.id AS VARCHAR),
    295000.00,
    250000.00,
    45000.00,
    CASE 
        WHEN generate_series >= 10 THEN 0 -- Last 3 installments unpaid
        ELSE 295000.00 
    END,
    CASE 
        WHEN generate_series >= 10 THEN 0
        ELSE 250000.00
    END,
    CASE 
        WHEN generate_series >= 10 THEN 0
        ELSE 45000.00
    END,
    l.loan_account_number,
    CURRENT_DATE - INTERVAL '12 months' + (generate_series * INTERVAL '1 month'),
    CASE 
        WHEN generate_series >= 10 THEN NULL
        ELSE CURRENT_DATE - INTERVAL '12 months' + (generate_series * INTERVAL '1 month')
    END,
    CASE 
        WHEN generate_series = 10 THEN 75
        WHEN generate_series = 11 THEN 45
        WHEN generate_series = 12 THEN 15
        ELSE 0
    END,
    CASE 
        WHEN generate_series >= 10 THEN 'PENDING'
        ELSE 'PAID'
    END,
    NOW(), NOW()
FROM loans l, generate_series(1, 12)
WHERE l.loan_account_number = 'LN2024TEST003';

-- Insert schedules for Loan 4: Doubtful (120 days overdue)
INSERT INTO loans_schedules (
    loan_id, installment, principle, interest, payment,
    principle_payment, interest_payment, bank_account_number,
    installment_date, last_payment_date, days_in_arrears,
    completion_status, created_at, updated_at
)
SELECT 
    CAST(l.id AS VARCHAR),
    54166.67,
    41666.67,
    12500.00,
    CASE 
        WHEN generate_series > 2 THEN 0 -- Only first 2 installments paid
        ELSE 54166.67 
    END,
    CASE 
        WHEN generate_series > 2 THEN 0
        ELSE 41666.67
    END,
    CASE 
        WHEN generate_series > 2 THEN 0
        ELSE 12500.00
    END,
    l.loan_account_number,
    CURRENT_DATE - INTERVAL '12 months' + (generate_series * INTERVAL '1 month'),
    CASE 
        WHEN generate_series > 2 THEN NULL
        ELSE CURRENT_DATE - INTERVAL '12 months' + (generate_series * INTERVAL '1 month')
    END,
    CASE 
        WHEN generate_series = 3 THEN 285
        WHEN generate_series = 4 THEN 255
        WHEN generate_series = 5 THEN 225
        WHEN generate_series = 6 THEN 195
        WHEN generate_series = 7 THEN 165
        WHEN generate_series = 8 THEN 135
        WHEN generate_series = 9 THEN 105
        WHEN generate_series >= 10 THEN 120
        ELSE 0
    END,
    CASE 
        WHEN generate_series > 2 THEN 'PENDING'
        ELSE 'PAID'
    END,
    NOW(), NOW()
FROM loans l, generate_series(1, 12)
WHERE l.loan_account_number = 'LN2024TEST004';

-- Insert schedules for Loan 5: Loss (>180 days, no payments)
INSERT INTO loans_schedules (
    loan_id, installment, principle, interest, payment,
    principle_payment, interest_payment, bank_account_number,
    installment_date, last_payment_date, days_in_arrears,
    completion_status, created_at, updated_at
)
SELECT 
    CAST(l.id AS VARCHAR),
    103333.33,
    83333.33,
    20000.00,
    0, -- No payments made
    0,
    0,
    l.loan_account_number,
    CURRENT_DATE - INTERVAL '12 months' + (generate_series * INTERVAL '1 month'),
    NULL,
    CASE 
        WHEN generate_series <= 6 THEN 365 - (generate_series * 30)
        ELSE 185
    END,
    'PENDING',
    NOW(), NOW()
FROM loans l, generate_series(1, 12)
WHERE l.loan_account_number = 'LN2024TEST005';

-- Insert schedules for Loan 6: Loss (>180 days, minimal payments)
INSERT INTO loans_schedules (
    loan_id, installment, principle, interest, payment,
    principle_payment, interest_payment, bank_account_number,
    installment_date, last_payment_date, days_in_arrears,
    completion_status, created_at, updated_at
)
SELECT 
    CAST(l.id AS VARCHAR),
    786666.67,
    666666.67,
    120000.00,
    CASE 
        WHEN generate_series = 1 THEN 786666.67 -- Only first payment made
        ELSE 0
    END,
    CASE 
        WHEN generate_series = 1 THEN 50000.00 -- Partial principal payment
        ELSE 0
    END,
    CASE 
        WHEN generate_series = 1 THEN 10000.00 -- Partial interest payment
        ELSE 0
    END,
    l.loan_account_number,
    CURRENT_DATE - INTERVAL '12 months' + (generate_series * INTERVAL '1 month'),
    CASE 
        WHEN generate_series = 1 THEN CURRENT_DATE - INTERVAL '11 months'
        ELSE NULL
    END,
    CASE 
        WHEN generate_series = 1 THEN 0
        WHEN generate_series = 2 THEN 330
        WHEN generate_series = 3 THEN 300
        WHEN generate_series = 4 THEN 270
        WHEN generate_series = 5 THEN 240
        WHEN generate_series = 6 THEN 210
        ELSE 181
    END,
    CASE 
        WHEN generate_series = 1 THEN 'PARTIAL'
        ELSE 'PENDING'
    END,
    NOW(), NOW()
FROM loans l, generate_series(1, 12)
WHERE l.loan_account_number = 'LN2024TEST006';

-- Insert schedules for Written Off Loans (for recovery testing)
INSERT INTO loans_schedules (
    loan_id, installment, principle, interest, payment,
    principle_payment, interest_payment, bank_account_number,
    installment_date, last_payment_date, days_in_arrears,
    completion_status, created_at, updated_at
)
SELECT 
    CAST(l.id AS VARCHAR),
    155000.00,
    125000.00,
    30000.00,
    0,
    0,
    0,
    l.loan_account_number,
    CURRENT_DATE - INTERVAL '15 months' + (generate_series * INTERVAL '1 month'),
    NULL,
    400, -- Very old arrears
    'WRITTEN_OFF',
    NOW(), NOW()
FROM loans l, generate_series(1, 12)
WHERE l.loan_account_number IN ('LN2024TEST007', 'LN2024TEST008');

-- Summary of test data
SELECT 
    'Test Loans Created' as description,
    COUNT(*) as count
FROM loans 
WHERE loan_account_number LIKE 'LN2024TEST%'
UNION ALL
SELECT 
    'Active Test Loans',
    COUNT(*)
FROM loans 
WHERE loan_account_number LIKE 'LN2024TEST%' AND status = 'ACTIVE'
UNION ALL
SELECT 
    'Written Off Test Loans',
    COUNT(*)
FROM loans 
WHERE loan_account_number LIKE 'LN2024TEST%' AND status = 'WRITTEN_OFF'
UNION ALL
SELECT 
    'Test Loan Schedules Created',
    COUNT(*)
FROM loans_schedules 
WHERE bank_account_number LIKE 'LN2024TEST%';

-- Check loan aging distribution
SELECT 
    CASE 
        WHEN days_in_arrears = 0 THEN 'Current (0 days)'
        WHEN days_in_arrears BETWEEN 1 AND 30 THEN 'Current (1-30 days)'
        WHEN days_in_arrears BETWEEN 31 AND 60 THEN 'Watch (31-60 days)'
        WHEN days_in_arrears BETWEEN 61 AND 90 THEN 'Substandard (61-90 days)'
        WHEN days_in_arrears BETWEEN 91 AND 180 THEN 'Doubtful (91-180 days)'
        WHEN days_in_arrears > 180 THEN 'Loss (>180 days)'
    END as aging_category,
    COUNT(*) as schedule_count,
    SUM(installment - COALESCE(payment, 0)) as total_outstanding
FROM loans_schedules
WHERE bank_account_number LIKE 'LN2024TEST%'
GROUP BY aging_category
ORDER BY 
    CASE aging_category
        WHEN 'Current (0 days)' THEN 1
        WHEN 'Current (1-30 days)' THEN 2
        WHEN 'Watch (31-60 days)' THEN 3
        WHEN 'Substandard (61-90 days)' THEN 4
        WHEN 'Doubtful (91-180 days)' THEN 5
        WHEN 'Loss (>180 days)' THEN 6
    END;