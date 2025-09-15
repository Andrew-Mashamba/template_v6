-- Test Data for Loan Loss Reserve Management
-- Final corrected version

-- First, create test clients with proper account_number field
INSERT INTO clients (account_number, client_number, first_name, middle_name, last_name, status, created_at, updated_at)
SELECT * FROM (VALUES 
    ('ACCTEST001', 'TEST001', 'John', 'Robert', 'Smith', 'ACTIVE', NOW(), NOW()),
    ('ACCTEST002', 'TEST002', 'Mary', 'Jane', 'Johnson', 'ACTIVE', NOW(), NOW()),
    ('ACCTEST003', 'TEST003', 'Michael', 'David', 'Williams', 'ACTIVE', NOW(), NOW()),
    ('ACCTEST004', 'TEST004', 'Sarah', 'Elizabeth', 'Brown', 'ACTIVE', NOW(), NOW()),
    ('ACCTEST005', 'TEST005', 'James', 'Alexander', 'Davis', 'ACTIVE', NOW(), NOW()),
    ('ACCTEST006', 'TEST006', 'Patricia', 'Ann', 'Miller', 'ACTIVE', NOW(), NOW()),
    ('ACCTEST007', 'TEST007', 'Robert', 'Thomas', 'Wilson', 'ACTIVE', NOW(), NOW()),
    ('ACCTEST008', 'TEST008', 'Jennifer', 'Marie', 'Moore', 'ACTIVE', NOW(), NOW())
) AS v(account_number, client_number, first_name, middle_name, last_name, status, created_at, updated_at)
WHERE NOT EXISTS (SELECT 1 FROM clients WHERE client_number = v.client_number);

-- Insert test loans with various delinquency levels
-- Using only the columns that exist in the loans table

-- Loan 1: Current (0-30 days) - Performing well
INSERT INTO loans (
    loan_account_number, client_number, principle, interest, 
    total_principal_paid, total_interest_paid, status,
    created_at, updated_at
) 
SELECT 'LN2024TEST001', 'TEST001', 5000000.00, 900000.00,
    500000.00, 100000.00, 'ACTIVE', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM loans WHERE loan_account_number = 'LN2024TEST001');

-- Loan 2: Watch (31-60 days) - Starting to fall behind
INSERT INTO loans (
    loan_account_number, client_number, principle, interest,
    total_principal_paid, total_interest_paid, status,
    created_at, updated_at
) 
SELECT 'LN2024TEST002', 'TEST002', 2000000.00, 480000.00,
    200000.00, 50000.00, 'ACTIVE', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM loans WHERE loan_account_number = 'LN2024TEST002');

-- Loan 3: Substandard (61-90 days) - Significant arrears
INSERT INTO loans (
    loan_account_number, client_number, principle, interest,
    total_principal_paid, total_interest_paid, status,
    created_at, updated_at
) 
SELECT 'LN2024TEST003', 'TEST003', 3000000.00, 540000.00,
    100000.00, 20000.00, 'ACTIVE', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM loans WHERE loan_account_number = 'LN2024TEST003');

-- Loan 4: Doubtful (91-180 days) - High risk
INSERT INTO loans (
    loan_account_number, client_number, principle, interest,
    total_principal_paid, total_interest_paid, status,
    created_at, updated_at
) 
SELECT 'LN2024TEST004', 'TEST004', 500000.00, 150000.00,
    10000.00, 5000.00, 'ACTIVE', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM loans WHERE loan_account_number = 'LN2024TEST004');

-- Loan 5: Loss (>180 days) - Candidate for write-off
INSERT INTO loans (
    loan_account_number, client_number, principle, interest,
    total_principal_paid, total_interest_paid, status,
    created_at, updated_at
) 
SELECT 'LN2024TEST005', 'TEST005', 1000000.00, 240000.00,
    0.00, 0.00, 'ACTIVE', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM loans WHERE loan_account_number = 'LN2024TEST005');

-- Loan 6: Another Loss (>180 days) - Candidate for write-off
INSERT INTO loans (
    loan_account_number, client_number, principle, interest,
    total_principal_paid, total_interest_paid, status,
    created_at, updated_at
) 
SELECT 'LN2024TEST006', 'TEST006', 8000000.00, 1440000.00,
    50000.00, 10000.00, 'ACTIVE', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM loans WHERE loan_account_number = 'LN2024TEST006');

-- Loan 7: Written Off - For recovery testing
INSERT INTO loans (
    loan_account_number, client_number, principle, interest,
    total_principal_paid, total_interest_paid, status,
    total_recovered, created_at, updated_at
) 
SELECT 'LN2024TEST007', 'TEST007', 1500000.00, 360000.00,
    50000.00, 10000.00, 'WRITTEN_OFF', 0.00, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM loans WHERE loan_account_number = 'LN2024TEST007');

-- Loan 8: Written Off with partial recovery
INSERT INTO loans (
    loan_account_number, client_number, principle, interest,
    total_principal_paid, total_interest_paid, status,
    total_recovered, created_at, updated_at
) 
SELECT 'LN2024TEST008', 'TEST008', 300000.00, 90000.00,
    20000.00, 5000.00, 'WRITTEN_OFF', 50000.00, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM loans WHERE loan_account_number = 'LN2024TEST008');

-- Clean up any existing test schedules
DELETE FROM loans_schedules WHERE loan_id IN (
    SELECT CAST(id AS VARCHAR) FROM loans WHERE loan_account_number LIKE 'LN2024TEST%'
);

-- Create schedules using simpler approach
DO $$
DECLARE
    loan_rec RECORD;
    i INTEGER;
    installment_amt NUMERIC;
    principal_amt NUMERIC;
    interest_amt NUMERIC;
BEGIN
    -- Process each test loan
    FOR loan_rec IN SELECT * FROM loans WHERE loan_account_number LIKE 'LN2024TEST%' LOOP
        
        -- Calculate installment amounts (12 month loans)
        installment_amt := (loan_rec.principle + loan_rec.interest) / 12;
        principal_amt := loan_rec.principle / 12;
        interest_amt := loan_rec.interest / 12;
        
        -- Generate 12 schedules for each loan
        FOR i IN 1..12 LOOP
            
            -- Insert schedule based on loan type
            IF loan_rec.loan_account_number = 'LN2024TEST001' THEN
                -- Current: All paid except last (15 days overdue)
                INSERT INTO loans_schedules (
                    loan_id, installment, principle, interest,
                    payment, principle_payment, interest_payment,
                    bank_account_number, installment_date,
                    days_in_arrears, completion_status,
                    created_at, updated_at
                ) VALUES (
                    CAST(loan_rec.id AS VARCHAR), installment_amt, principal_amt, interest_amt,
                    CASE WHEN i = 12 THEN 0 ELSE installment_amt END,
                    CASE WHEN i = 12 THEN 0 ELSE principal_amt END,
                    CASE WHEN i = 12 THEN 0 ELSE interest_amt END,
                    loan_rec.loan_account_number,
                    CURRENT_DATE - INTERVAL '12 months' + (i * INTERVAL '1 month'),
                    CASE WHEN i = 12 THEN 15 ELSE 0 END,
                    CASE WHEN i = 12 THEN 'PENDING' ELSE 'PAID' END,
                    NOW(), NOW()
                );
                
            ELSIF loan_rec.loan_account_number = 'LN2024TEST002' THEN
                -- Watch: Last 2 unpaid (45 days)
                INSERT INTO loans_schedules (
                    loan_id, installment, principle, interest,
                    payment, principle_payment, interest_payment,
                    bank_account_number, installment_date,
                    days_in_arrears, completion_status,
                    created_at, updated_at
                ) VALUES (
                    CAST(loan_rec.id AS VARCHAR), installment_amt, principal_amt, interest_amt,
                    CASE WHEN i >= 11 THEN 0 ELSE installment_amt END,
                    CASE WHEN i >= 11 THEN 0 ELSE principal_amt END,
                    CASE WHEN i >= 11 THEN 0 ELSE interest_amt END,
                    loan_rec.loan_account_number,
                    CURRENT_DATE - INTERVAL '12 months' + (i * INTERVAL '1 month'),
                    CASE WHEN i = 11 THEN 45 WHEN i = 12 THEN 15 ELSE 0 END,
                    CASE WHEN i >= 11 THEN 'PENDING' ELSE 'PAID' END,
                    NOW(), NOW()
                );
                
            ELSIF loan_rec.loan_account_number = 'LN2024TEST003' THEN
                -- Substandard: Last 3 unpaid (75 days)
                INSERT INTO loans_schedules (
                    loan_id, installment, principle, interest,
                    payment, principle_payment, interest_payment,
                    bank_account_number, installment_date,
                    days_in_arrears, completion_status,
                    created_at, updated_at
                ) VALUES (
                    CAST(loan_rec.id AS VARCHAR), installment_amt, principal_amt, interest_amt,
                    CASE WHEN i >= 10 THEN 0 ELSE installment_amt END,
                    CASE WHEN i >= 10 THEN 0 ELSE principal_amt END,
                    CASE WHEN i >= 10 THEN 0 ELSE interest_amt END,
                    loan_rec.loan_account_number,
                    CURRENT_DATE - INTERVAL '12 months' + (i * INTERVAL '1 month'),
                    CASE WHEN i = 10 THEN 75 WHEN i = 11 THEN 45 WHEN i = 12 THEN 15 ELSE 0 END,
                    CASE WHEN i >= 10 THEN 'PENDING' ELSE 'PAID' END,
                    NOW(), NOW()
                );
                
            ELSIF loan_rec.loan_account_number = 'LN2024TEST004' THEN
                -- Doubtful: Only 2 paid (120+ days)
                INSERT INTO loans_schedules (
                    loan_id, installment, principle, interest,
                    payment, principle_payment, interest_payment,
                    bank_account_number, installment_date,
                    days_in_arrears, completion_status,
                    created_at, updated_at
                ) VALUES (
                    CAST(loan_rec.id AS VARCHAR), installment_amt, principal_amt, interest_amt,
                    CASE WHEN i <= 2 THEN installment_amt ELSE 0 END,
                    CASE WHEN i <= 2 THEN principal_amt ELSE 0 END,
                    CASE WHEN i <= 2 THEN interest_amt ELSE 0 END,
                    loan_rec.loan_account_number,
                    CURRENT_DATE - INTERVAL '12 months' + (i * INTERVAL '1 month'),
                    CASE WHEN i <= 2 THEN 0 ELSE 120 + ((i-3) * 30) END,
                    CASE WHEN i <= 2 THEN 'PAID' ELSE 'PENDING' END,
                    NOW(), NOW()
                );
                
            ELSIF loan_rec.loan_account_number IN ('LN2024TEST005', 'LN2024TEST006') THEN
                -- Loss: No/minimal payments (>180 days)
                INSERT INTO loans_schedules (
                    loan_id, installment, principle, interest,
                    payment, principle_payment, interest_payment,
                    bank_account_number, installment_date,
                    days_in_arrears, completion_status,
                    created_at, updated_at
                ) VALUES (
                    CAST(loan_rec.id AS VARCHAR), installment_amt, principal_amt, interest_amt,
                    CASE WHEN loan_rec.loan_account_number = 'LN2024TEST006' AND i = 1 
                         THEN installment_amt * 0.1 ELSE 0 END,
                    CASE WHEN loan_rec.loan_account_number = 'LN2024TEST006' AND i = 1 
                         THEN principal_amt * 0.08 ELSE 0 END,
                    CASE WHEN loan_rec.loan_account_number = 'LN2024TEST006' AND i = 1 
                         THEN interest_amt * 0.08 ELSE 0 END,
                    loan_rec.loan_account_number,
                    CURRENT_DATE - INTERVAL '12 months' + (i * INTERVAL '1 month'),
                    185 + (i * 30),
                    'PENDING',
                    NOW(), NOW()
                );
                
            ELSE
                -- Written off loans
                INSERT INTO loans_schedules (
                    loan_id, installment, principle, interest,
                    payment, principle_payment, interest_payment,
                    bank_account_number, installment_date,
                    days_in_arrears, completion_status,
                    created_at, updated_at
                ) VALUES (
                    CAST(loan_rec.id AS VARCHAR), installment_amt, principal_amt, interest_amt,
                    0, 0, 0,
                    loan_rec.loan_account_number,
                    CURRENT_DATE - INTERVAL '15 months' + (i * INTERVAL '1 month'),
                    400,
                    'WRITTEN_OFF',
                    NOW(), NOW()
                );
            END IF;
            
        END LOOP;
    END LOOP;
END $$;

-- Summary report
SELECT 'Summary of Test Data' as report_section, '' as category, 0 as count, 0 as amount
UNION ALL
SELECT '===================' , '', 0, 0
UNION ALL
SELECT 'Test Loans', 'Total Created', COUNT(*), SUM(principle + interest)
FROM loans WHERE loan_account_number LIKE 'LN2024TEST%'
UNION ALL
SELECT '', 'Active Loans', COUNT(*), SUM(principle + interest)
FROM loans WHERE loan_account_number LIKE 'LN2024TEST%' AND status = 'ACTIVE'
UNION ALL
SELECT '', 'Written Off', COUNT(*), SUM(principle + interest)
FROM loans WHERE loan_account_number LIKE 'LN2024TEST%' AND status = 'WRITTEN_OFF'
UNION ALL
SELECT '-------------------', '', 0, 0
UNION ALL
SELECT 'Aging Analysis', '', 0, 0
UNION ALL
SELECT '', 
    CASE 
        WHEN days_in_arrears = 0 THEN '1. Current (0 days)'
        WHEN days_in_arrears BETWEEN 1 AND 30 THEN '2. Current (1-30 days)'
        WHEN days_in_arrears BETWEEN 31 AND 60 THEN '3. Watch (31-60 days)'
        WHEN days_in_arrears BETWEEN 61 AND 90 THEN '4. Substandard (61-90 days)'
        WHEN days_in_arrears BETWEEN 91 AND 180 THEN '5. Doubtful (91-180 days)'
        WHEN days_in_arrears > 180 THEN '6. Loss (>180 days)'
    END,
    COUNT(*),
    SUM(installment - COALESCE(payment, 0))
FROM loans_schedules
WHERE bank_account_number LIKE 'LN2024TEST%'
GROUP BY 
    CASE 
        WHEN days_in_arrears = 0 THEN '1. Current (0 days)'
        WHEN days_in_arrears BETWEEN 1 AND 30 THEN '2. Current (1-30 days)'
        WHEN days_in_arrears BETWEEN 31 AND 60 THEN '3. Watch (31-60 days)'
        WHEN days_in_arrears BETWEEN 61 AND 90 THEN '4. Substandard (61-90 days)'
        WHEN days_in_arrears BETWEEN 91 AND 180 THEN '5. Doubtful (91-180 days)'
        WHEN days_in_arrears > 180 THEN '6. Loss (>180 days)'
    END
ORDER BY 1, 2;