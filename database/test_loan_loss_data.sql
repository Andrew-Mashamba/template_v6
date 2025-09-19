-- Test Data for Loan Loss Reserve Management
-- This script creates test loans and schedules with various delinquency levels

-- First, let's check if we have test clients, if not create some
INSERT INTO clients (client_number, first_name, middle_name, last_name, status, created_at, updated_at)
VALUES 
    ('TEST001', 'John', 'Robert', 'Smith', 'ACTIVE', NOW(), NOW()),
    ('TEST002', 'Mary', 'Jane', 'Johnson', 'ACTIVE', NOW(), NOW()),
    ('TEST003', 'Michael', 'David', 'Williams', 'ACTIVE', NOW(), NOW()),
    ('TEST004', 'Sarah', 'Elizabeth', 'Brown', 'ACTIVE', NOW(), NOW()),
    ('TEST005', 'James', 'Alexander', 'Davis', 'ACTIVE', NOW(), NOW()),
    ('TEST006', 'Patricia', 'Ann', 'Miller', 'ACTIVE', NOW(), NOW()),
    ('TEST007', 'Robert', 'Thomas', 'Wilson', 'ACTIVE', NOW(), NOW()),
    ('TEST008', 'Jennifer', 'Marie', 'Moore', 'ACTIVE', NOW(), NOW())
ON CONFLICT (client_number) DO NOTHING;

-- Create test loan products if they don't exist
INSERT INTO loan_products (product_name, product_code, interest_rate, min_amount, max_amount, min_term, max_term, status, created_at, updated_at)
VALUES 
    ('Business Loan', 'BL001', 18.00, 100000, 10000000, 3, 36, 'ACTIVE', NOW(), NOW()),
    ('Personal Loan', 'PL001', 24.00, 50000, 5000000, 3, 24, 'ACTIVE', NOW(), NOW()),
    ('Emergency Loan', 'EL001', 30.00, 10000, 500000, 1, 12, 'ACTIVE', NOW(), NOW())
ON CONFLICT (product_code) DO NOTHING;

-- Get product IDs
DO $$
DECLARE
    business_loan_id INTEGER;
    personal_loan_id INTEGER;
    emergency_loan_id INTEGER;
    current_date DATE := CURRENT_DATE;
BEGIN
    -- Get loan product IDs
    SELECT id INTO business_loan_id FROM loan_products WHERE product_code = 'BL001' LIMIT 1;
    SELECT id INTO personal_loan_id FROM loan_products WHERE product_code = 'PL001' LIMIT 1;
    SELECT id INTO emergency_loan_id FROM loan_products WHERE product_code = 'EL001' LIMIT 1;

    -- Insert test loans with various statuses and delinquency levels
    
    -- Loan 1: Current (0-30 days) - Performing well
    INSERT INTO loans (
        loan_account_number, client_number, loan_product_id, principle, interest, 
        loan_balance, total_principal_paid, total_interest_paid, status, 
        disbursement_date, maturity_date, number_of_installments, 
        created_at, updated_at
    ) VALUES (
        'LN2024TEST001', 'TEST001', business_loan_id, 5000000.00, 900000.00,
        4500000.00, 500000.00, 100000.00, 'ACTIVE',
        current_date - INTERVAL '3 months', current_date + INTERVAL '9 months', 12,
        NOW(), NOW()
    ) ON CONFLICT (loan_account_number) DO NOTHING;

    -- Loan 2: Watch (31-60 days) - Starting to fall behind
    INSERT INTO loans (
        loan_account_number, client_number, loan_product_id, principle, interest,
        loan_balance, total_principal_paid, total_interest_paid, status,
        disbursement_date, maturity_date, number_of_installments,
        created_at, updated_at
    ) VALUES (
        'LN2024TEST002', 'TEST002', personal_loan_id, 2000000.00, 480000.00,
        1800000.00, 200000.00, 50000.00, 'ACTIVE',
        current_date - INTERVAL '4 months', current_date + INTERVAL '8 months', 12,
        NOW(), NOW()
    ) ON CONFLICT (loan_account_number) DO NOTHING;

    -- Loan 3: Substandard (61-90 days) - Significant arrears
    INSERT INTO loans (
        loan_account_number, client_number, loan_product_id, principle, interest,
        loan_balance, total_principal_paid, total_interest_paid, status,
        disbursement_date, maturity_date, number_of_installments,
        created_at, updated_at
    ) VALUES (
        'LN2024TEST003', 'TEST003', business_loan_id, 3000000.00, 540000.00,
        2900000.00, 100000.00, 20000.00, 'ACTIVE',
        current_date - INTERVAL '5 months', current_date + INTERVAL '7 months', 12,
        NOW(), NOW()
    ) ON CONFLICT (loan_account_number) DO NOTHING;

    -- Loan 4: Doubtful (91-180 days) - High risk
    INSERT INTO loans (
        loan_account_number, client_number, loan_product_id, principle, interest,
        loan_balance, total_principal_paid, total_interest_paid, status,
        disbursement_date, maturity_date, number_of_installments,
        created_at, updated_at
    ) VALUES (
        'LN2024TEST004', 'TEST004', emergency_loan_id, 500000.00, 150000.00,
        490000.00, 10000.00, 5000.00, 'ACTIVE',
        current_date - INTERVAL '7 months', current_date + INTERVAL '5 months', 12,
        NOW(), NOW()
    ) ON CONFLICT (loan_account_number) DO NOTHING;

    -- Loan 5: Loss (>180 days) - Candidate for write-off
    INSERT INTO loans (
        loan_account_number, client_number, loan_product_id, principle, interest,
        loan_balance, total_principal_paid, total_interest_paid, status,
        disbursement_date, maturity_date, number_of_installments,
        created_at, updated_at
    ) VALUES (
        'LN2024TEST005', 'TEST005', personal_loan_id, 1000000.00, 240000.00,
        1000000.00, 0.00, 0.00, 'ACTIVE',
        current_date - INTERVAL '10 months', current_date + INTERVAL '2 months', 12,
        NOW(), NOW()
    ) ON CONFLICT (loan_account_number) DO NOTHING;

    -- Loan 6: Another Loss (>180 days) - Candidate for write-off
    INSERT INTO loans (
        loan_account_number, client_number, loan_product_id, principle, interest,
        loan_balance, total_principal_paid, total_interest_paid, status,
        disbursement_date, maturity_date, number_of_installments,
        created_at, updated_at
    ) VALUES (
        'LN2024TEST006', 'TEST006', business_loan_id, 8000000.00, 1440000.00,
        7950000.00, 50000.00, 10000.00, 'ACTIVE',
        current_date - INTERVAL '12 months', current_date, 12,
        NOW(), NOW()
    ) ON CONFLICT (loan_account_number) DO NOTHING;

    -- Loan 7: Written Off - For recovery testing
    INSERT INTO loans (
        loan_account_number, client_number, loan_product_id, principle, interest,
        loan_balance, total_principal_paid, total_interest_paid, status,
        disbursement_date, maturity_date, number_of_installments,
        total_recovered, created_at, updated_at
    ) VALUES (
        'LN2024TEST007', 'TEST007', personal_loan_id, 1500000.00, 360000.00,
        1450000.00, 50000.00, 10000.00, 'WRITTEN_OFF',
        current_date - INTERVAL '15 months', current_date - INTERVAL '3 months', 12,
        0.00, NOW(), NOW()
    ) ON CONFLICT (loan_account_number) DO NOTHING;

    -- Loan 8: Written Off with partial recovery - For recovery testing
    INSERT INTO loans (
        loan_account_number, client_number, loan_product_id, principle, interest,
        loan_balance, total_principal_paid, total_interest_paid, status,
        disbursement_date, maturity_date, number_of_installments,
        total_recovered, created_at, updated_at
    ) VALUES (
        'LN2024TEST008', 'TEST008', emergency_loan_id, 300000.00, 90000.00,
        280000.00, 20000.00, 5000.00, 'WRITTEN_OFF',
        current_date - INTERVAL '14 months', current_date - INTERVAL '2 months', 12,
        50000.00, NOW(), NOW()
    ) ON CONFLICT (loan_account_number) DO NOTHING;

END $$;

-- Now insert loan schedules for each loan
DO $$
DECLARE
    loan_record RECORD;
    schedule_date DATE;
    i INTEGER;
    installment_amount DECIMAL(20,2);
    principal_amount DECIMAL(20,2);
    interest_amount DECIMAL(20,2);
    days_overdue INTEGER;
    payment_amount DECIMAL(20,2);
    principal_paid DECIMAL(20,2);
    interest_paid DECIMAL(20,2);
BEGIN
    -- Loop through each test loan
    FOR loan_record IN 
        SELECT l.*, lp.interest_rate 
        FROM loans l
        JOIN loan_products lp ON l.loan_product_id = lp.id
        WHERE l.loan_account_number LIKE 'LN2024TEST%'
    LOOP
        -- Calculate installment amounts
        installment_amount := (loan_record.principle + loan_record.interest) / loan_record.number_of_installments;
        principal_amount := loan_record.principle / loan_record.number_of_installments;
        interest_amount := loan_record.interest / loan_record.number_of_installments;
        
        -- Create schedules for each installment
        FOR i IN 1..loan_record.number_of_installments LOOP
            schedule_date := loan_record.disbursement_date + (i * INTERVAL '1 month')::interval;
            
            -- Determine payment status based on loan type
            IF loan_record.loan_account_number = 'LN2024TEST001' THEN
                -- Current loan - all paid except last installment (15 days overdue)
                IF i < loan_record.number_of_installments THEN
                    payment_amount := installment_amount;
                    principal_paid := principal_amount;
                    interest_paid := interest_amount;
                    days_overdue := 0;
                ELSE
                    payment_amount := 0;
                    principal_paid := 0;
                    interest_paid := 0;
                    days_overdue := 15;
                END IF;
                
            ELSIF loan_record.loan_account_number = 'LN2024TEST002' THEN
                -- Watch loan - paid until 2 months ago (45 days overdue)
                IF i <= loan_record.number_of_installments - 2 THEN
                    payment_amount := installment_amount;
                    principal_paid := principal_amount;
                    interest_paid := interest_amount;
                    days_overdue := 0;
                ELSIF i = loan_record.number_of_installments - 1 THEN
                    payment_amount := 0;
                    principal_paid := 0;
                    interest_paid := 0;
                    days_overdue := 45;
                ELSE
                    payment_amount := 0;
                    principal_paid := 0;
                    interest_paid := 0;
                    days_overdue := 15;
                END IF;
                
            ELSIF loan_record.loan_account_number = 'LN2024TEST003' THEN
                -- Substandard loan - 75 days overdue
                IF i <= loan_record.number_of_installments - 3 THEN
                    payment_amount := installment_amount;
                    principal_paid := principal_amount;
                    interest_paid := interest_amount;
                    days_overdue := 0;
                ELSIF i = loan_record.number_of_installments - 2 THEN
                    payment_amount := 0;
                    principal_paid := 0;
                    interest_paid := 0;
                    days_overdue := 75;
                ELSIF i = loan_record.number_of_installments - 1 THEN
                    payment_amount := 0;
                    principal_paid := 0;
                    interest_paid := 0;
                    days_overdue := 45;
                ELSE
                    payment_amount := 0;
                    principal_paid := 0;
                    interest_paid := 0;
                    days_overdue := 15;
                END IF;
                
            ELSIF loan_record.loan_account_number = 'LN2024TEST004' THEN
                -- Doubtful loan - 120 days overdue
                IF i <= 2 THEN
                    payment_amount := installment_amount;
                    principal_paid := principal_amount;
                    interest_paid := interest_amount;
                    days_overdue := 0;
                ELSE
                    payment_amount := 0;
                    principal_paid := 0;
                    interest_paid := 0;
                    days_overdue := 150 - (i * 10); -- Graduated overdue days
                    IF days_overdue < 0 THEN
                        days_overdue := 120;
                    END IF;
                END IF;
                
            ELSIF loan_record.loan_account_number IN ('LN2024TEST005', 'LN2024TEST006') THEN
                -- Loss loans - >180 days overdue
                IF i <= 1 THEN
                    payment_amount := 0;
                    principal_paid := 0;
                    interest_paid := 0;
                    days_overdue := 240;
                ELSE
                    payment_amount := 0;
                    principal_paid := 0;
                    interest_paid := 0;
                    days_overdue := 210;
                END IF;
                
            ELSIF loan_record.loan_account_number IN ('LN2024TEST007', 'LN2024TEST008') THEN
                -- Written off loans
                payment_amount := 0;
                principal_paid := 0;
                interest_paid := 0;
                days_overdue := 300;
            ELSE
                -- Default case
                payment_amount := 0;
                principal_paid := 0;
                interest_paid := 0;
                days_overdue := 0;
            END IF;
            
            -- Insert the schedule
            INSERT INTO loans_schedules (
                loan_id, loan_account_number, installment, principle, interest,
                payment, principle_payment, interest_payment,
                installment_date, last_payment_date,
                days_in_arrears, completion_status,
                created_at, updated_at
            ) VALUES (
                loan_record.id, loan_record.loan_account_number, installment_amount,
                principal_amount, interest_amount,
                payment_amount, principal_paid, interest_paid,
                schedule_date, 
                CASE WHEN payment_amount > 0 THEN schedule_date ELSE NULL END,
                days_overdue,
                CASE WHEN payment_amount >= installment_amount THEN 'PAID' ELSE 'PENDING' END,
                NOW(), NOW()
            ) ON CONFLICT DO NOTHING;
        END LOOP;
    END LOOP;
END $$;

-- Summary of test data
SELECT 
    'Test Data Summary' as description,
    COUNT(DISTINCT CASE WHEN loan_account_number LIKE 'LN2024TEST%' THEN loan_account_number END) as test_loans_count,
    COUNT(DISTINCT CASE WHEN status = 'ACTIVE' AND loan_account_number LIKE 'LN2024TEST%' THEN loan_account_number END) as active_loans,
    COUNT(DISTINCT CASE WHEN status = 'WRITTEN_OFF' AND loan_account_number LIKE 'LN2024TEST%' THEN loan_account_number END) as written_off_loans
FROM loans;

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
WHERE loan_account_number LIKE 'LN2024TEST%'
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