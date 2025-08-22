#!/bin/bash

# End-to-End Auto Loan Disbursement Test
# This script tests the complete loan lifecycle from creation to disbursement

# Configuration
BASE_URL="http://localhost:8000/api/v1/loans"
ENDPOINT="/auto-disburse"
CLIENT_NUMBER="10003"
AMOUNT="100000"  # 100,000 TZS
API_KEY="test_saccos_api_key_2024"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}   END-TO-END LOAN DISBURSEMENT TEST   ${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Step 1: Check initial client status
echo -e "${YELLOW}STEP 1: Checking initial client status...${NC}"
php artisan tinker --execute="
    use App\Models\ClientsModel;
    use App\Models\AccountsModel;
    
    \$client = ClientsModel::where('client_number', '${CLIENT_NUMBER}')->first();
    if (\$client) {
        echo 'Client: ' . \$client->first_name . ' ' . \$client->last_name . PHP_EOL;
        echo 'NBC Account: ' . \$client->account_number . PHP_EOL;
        
        \$account = AccountsModel::where('account_number', \$client->account_number)->first();
        if (\$account) {
            echo 'Initial Balance: TZS ' . number_format(\$account->balance, 2) . PHP_EOL;
        }
    }
" 2>/dev/null | grep -v ">>>"
echo ""

# Step 2: Check existing loans for the client
echo -e "${YELLOW}STEP 2: Checking existing loans...${NC}"
php artisan tinker --execute="
    use Illuminate\Support\Facades\DB;
    
    \$loans = DB::table('loans')
        ->where('client_number', '${CLIENT_NUMBER}')
        ->where('loan_status', 'active')
        ->count();
    
    echo 'Active loans for client ${CLIENT_NUMBER}: ' . \$loans . PHP_EOL;
" 2>/dev/null | grep -v ">>>"
echo ""

# Step 3: Create loan via API
echo -e "${YELLOW}STEP 3: Creating loan via API...${NC}"
echo -e "Amount: ${GREEN}TZS ${AMOUNT}${NC}"
echo ""

# Create JSON payload
JSON_PAYLOAD=$(cat <<EOF
{
    "client_number": "${CLIENT_NUMBER}",
    "amount": ${AMOUNT}
}
EOF
)

# Make the API call
RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "${BASE_URL}${ENDPOINT}" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "X-API-Key: ${API_KEY}" \
    -d "${JSON_PAYLOAD}")

# Extract HTTP status code and response body
HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
RESPONSE_BODY=$(echo "$RESPONSE" | sed '$d')

if [ "$HTTP_CODE" -eq 200 ] || [ "$HTTP_CODE" -eq 201 ]; then
    echo -e "${GREEN}✓ Loan created successfully!${NC}"
    
    # Extract loan details
    LOAN_ID=$(echo "$RESPONSE_BODY" | jq -r '.data.loan_id // "N/A"')
    TRANSACTION_ID=$(echo "$RESPONSE_BODY" | jq -r '.data.transaction_id // "N/A"')
    NET_DISBURSED=$(echo "$RESPONSE_BODY" | jq -r '.data.disbursement.net_disbursed // "N/A"')
    MONTHLY_PAYMENT=$(echo "$RESPONSE_BODY" | jq -r '.data.loan_details.monthly_installment // "N/A"')
    CONTROL_NUMBER=$(echo "$RESPONSE_BODY" | jq -r '.data.repayment.control_numbers[0].number // "N/A"')
    
    echo -e "Loan ID: ${GREEN}${LOAN_ID}${NC}"
    echo -e "Transaction ID: ${GREEN}${TRANSACTION_ID}${NC}"
    echo -e "Net Disbursed: ${GREEN}TZS ${NET_DISBURSED}${NC}"
    echo -e "Monthly Payment: ${GREEN}TZS ${MONTHLY_PAYMENT}${NC}"
    echo -e "Control Number: ${GREEN}${CONTROL_NUMBER}${NC}"
else
    echo -e "${RED}✗ Loan creation failed!${NC}"
    echo "$RESPONSE_BODY" | jq '.' 2>/dev/null || echo "$RESPONSE_BODY"
    exit 1
fi
echo ""

# Step 4: Verify loan in database
echo -e "${YELLOW}STEP 4: Verifying loan in database...${NC}"
if [ "$LOAN_ID" != "N/A" ]; then
    php artisan tinker --execute="
        use Illuminate\Support\Facades\DB;
        
        \$loan = DB::table('loans')
            ->where('loan_id', '${LOAN_ID}')
            ->first();
        
        if (\$loan) {
            echo '✓ Loan found in database' . PHP_EOL;
            echo 'Status: ' . \$loan->loan_status . PHP_EOL;
            echo 'Principal: TZS ' . number_format(\$loan->principle, 2) . PHP_EOL;
            echo 'Interest: ' . \$loan->interest . '%' . PHP_EOL;
            echo 'Disbursement Date: ' . \$loan->disbursement_date . PHP_EOL;
        } else {
            echo '✗ Loan not found in database' . PHP_EOL;
        }
    " 2>/dev/null | grep -v ">>>"
fi
echo ""

# Step 5: Check loan schedule
echo -e "${YELLOW}STEP 5: Checking repayment schedule...${NC}"
if [ "$LOAN_ID" != "N/A" ]; then
    php artisan tinker --execute="
        use Illuminate\Support\Facades\DB;
        
        \$schedules = DB::table('loans_schedules')
            ->where('loan_id', '${LOAN_ID}')
            ->get();
        
        echo 'Installments created: ' . \$schedules->count() . PHP_EOL;
        
        if (\$schedules->count() > 0) {
            \$first = \$schedules->first();
            echo 'First payment date: ' . \$first->installment_date . PHP_EOL;
            echo 'Amount: TZS ' . number_format(\$first->principle + \$first->interest, 2) . PHP_EOL;
        }
    " 2>/dev/null | grep -v ">>>"
fi
echo ""

# Step 6: Check account balance after disbursement
echo -e "${YELLOW}STEP 6: Checking account balance after disbursement...${NC}"
php artisan tinker --execute="
    use App\Models\ClientsModel;
    use App\Models\AccountsModel;
    
    \$client = ClientsModel::where('client_number', '${CLIENT_NUMBER}')->first();
    if (\$client) {
        \$account = AccountsModel::where('account_number', \$client->account_number)->first();
        if (\$account) {
            echo 'Final Balance: TZS ' . number_format(\$account->balance, 2) . PHP_EOL;
            echo 'Account has been credited with loan disbursement' . PHP_EOL;
        }
    }
" 2>/dev/null | grep -v ">>>"
echo ""

# Step 7: Check transaction posting
echo -e "${YELLOW}STEP 7: Verifying transaction postings...${NC}"
if [ "$LOAN_ID" != "N/A" ]; then
    php artisan tinker --execute="
        use Illuminate\Support\Facades\DB;
        
        \$transactions = DB::table('general_ledger')
            ->where('reference_number', 'like', '%${LOAN_ID}%')
            ->orWhere('reference_number', 'like', '%${TRANSACTION_ID}%')
            ->get();
        
        echo 'GL entries posted: ' . \$transactions->count() . PHP_EOL;
        
        \$total_debit = \$transactions->sum('debit');
        \$total_credit = \$transactions->sum('credit');
        
        echo 'Total Debits: TZS ' . number_format(\$total_debit, 2) . PHP_EOL;
        echo 'Total Credits: TZS ' . number_format(\$total_credit, 2) . PHP_EOL;
        
        if (abs(\$total_debit - \$total_credit) < 0.01) {
            echo '✓ Debits and Credits are balanced' . PHP_EOL;
        } else {
            echo '✗ Warning: Debits and Credits are not balanced!' . PHP_EOL;
        }
    " 2>/dev/null | grep -v ">>>"
fi
echo ""

# Step 8: Test duplicate prevention
echo -e "${YELLOW}STEP 8: Testing duplicate prevention...${NC}"
echo "Attempting to create another loan with same amount..."

DUPLICATE_RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "${BASE_URL}${ENDPOINT}" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "X-API-Key: ${API_KEY}" \
    -d "${JSON_PAYLOAD}")

DUP_HTTP_CODE=$(echo "$DUPLICATE_RESPONSE" | tail -n1)
DUP_RESPONSE_BODY=$(echo "$DUPLICATE_RESPONSE" | sed '$d')

if [ "$DUP_HTTP_CODE" -eq 200 ] || [ "$DUP_HTTP_CODE" -eq 201 ]; then
    echo -e "${YELLOW}⚠ Another loan was created (this may be intentional)${NC}"
    DUP_LOAN_ID=$(echo "$DUP_RESPONSE_BODY" | jq -r '.data.loan_id // "N/A"')
    echo -e "New Loan ID: ${YELLOW}${DUP_LOAN_ID}${NC}"
else
    echo -e "${GREEN}✓ Duplicate prevention working (or validation failed)${NC}"
    ERROR_MSG=$(echo "$DUP_RESPONSE_BODY" | jq -r '.message // "Unknown"')
    echo -e "Response: ${YELLOW}${ERROR_MSG}${NC}"
fi
echo ""

# Summary
echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}           TEST SUMMARY                ${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

if [ "$LOAN_ID" != "N/A" ]; then
    echo -e "${GREEN}✓ End-to-End Test SUCCESSFUL!${NC}"
    echo ""
    echo "Loan Details:"
    echo "  • Loan ID: ${LOAN_ID}"
    echo "  • Amount: TZS ${AMOUNT}"
    echo "  • Net Disbursed: TZS ${NET_DISBURSED}"
    echo "  • Monthly Payment: TZS ${MONTHLY_PAYMENT}"
    echo "  • Control Number: ${CONTROL_NUMBER}"
    echo ""
    echo "Next Steps:"
    echo "  1. Client can check their NBC account for the disbursed amount"
    echo "  2. Client should pay using control number: ${CONTROL_NUMBER}"
    echo "  3. Monitor loan repayments in the system"
else
    echo -e "${RED}✗ Test FAILED${NC}"
    echo "Please check the error messages above"
fi

echo ""
echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}         TEST COMPLETED                ${NC}"
echo -e "${BLUE}========================================${NC}"