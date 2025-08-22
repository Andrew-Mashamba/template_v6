#!/bin/bash

# Auto Loan Disbursement API Test Script
# This script tests the simplified loan creation and disbursement endpoint
# Only requires client_number and amount

# Configuration
BASE_URL="http://localhost:8000/api/v1/loans"
ENDPOINT="/auto-disburse"
CLIENT_NUMBER="10003"
AMOUNT="1000000"  # 1 million TZS

# API Key (you'll need to get this from your API key management system)
# This is a test key - replace with your actual API key
API_KEY="test_saccos_api_key_2024"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}========================================${NC}"
echo -e "${YELLOW}Auto Loan Disbursement API Test${NC}"
echo -e "${YELLOW}========================================${NC}"
echo ""
echo -e "Client Number: ${GREEN}${CLIENT_NUMBER}${NC}"
echo -e "Loan Amount: ${GREEN}TZS ${AMOUNT}${NC}"
echo -e "Endpoint: ${GREEN}${BASE_URL}${ENDPOINT}${NC}"
echo ""

# Create JSON payload
JSON_PAYLOAD=$(cat <<EOF
{
    "client_number": "${CLIENT_NUMBER}",
    "amount": ${AMOUNT}
}
EOF
)

echo -e "${YELLOW}Request Payload:${NC}"
echo "$JSON_PAYLOAD" | jq '.' 2>/dev/null || echo "$JSON_PAYLOAD"
echo ""

echo -e "${YELLOW}Sending request...${NC}"
echo ""

# Make the API call
RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "${BASE_URL}${ENDPOINT}" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "X-API-Key: ${API_KEY}" \
    -d "${JSON_PAYLOAD}")

# Extract HTTP status code (last line)
HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
# Extract response body (all except last line)
RESPONSE_BODY=$(echo "$RESPONSE" | sed '$d')

echo -e "${YELLOW}Response Status Code:${NC} ${HTTP_CODE}"
echo ""

# Check if response is successful
if [ "$HTTP_CODE" -eq 200 ] || [ "$HTTP_CODE" -eq 201 ]; then
    echo -e "${GREEN}✓ Request Successful!${NC}"
    echo ""
    echo -e "${YELLOW}Response Body:${NC}"
    echo "$RESPONSE_BODY" | jq '.' 2>/dev/null || echo "$RESPONSE_BODY"
    
    # Extract key information if jq is available
    if command -v jq &> /dev/null; then
        echo ""
        echo -e "${YELLOW}Key Information:${NC}"
        
        LOAN_ID=$(echo "$RESPONSE_BODY" | jq -r '.data.loan_id // "N/A"')
        TRANSACTION_ID=$(echo "$RESPONSE_BODY" | jq -r '.data.transaction_id // "N/A"')
        NET_DISBURSED=$(echo "$RESPONSE_BODY" | jq -r '.data.disbursement.net_disbursed // "N/A"')
        MONTHLY_PAYMENT=$(echo "$RESPONSE_BODY" | jq -r '.data.loan_details.monthly_installment // "N/A"')
        
        echo -e "Loan ID: ${GREEN}${LOAN_ID}${NC}"
        echo -e "Transaction ID: ${GREEN}${TRANSACTION_ID}${NC}"
        echo -e "Net Disbursed: ${GREEN}TZS ${NET_DISBURSED}${NC}"
        echo -e "Monthly Payment: ${GREEN}TZS ${MONTHLY_PAYMENT}${NC}"
    fi
else
    echo -e "${RED}✗ Request Failed!${NC}"
    echo ""
    echo -e "${YELLOW}Response Body:${NC}"
    echo "$RESPONSE_BODY" | jq '.' 2>/dev/null || echo "$RESPONSE_BODY"
    
    # Show error details if available
    if command -v jq &> /dev/null; then
        ERROR_MSG=$(echo "$RESPONSE_BODY" | jq -r '.message // .error // "Unknown error"')
        echo ""
        echo -e "${RED}Error: ${ERROR_MSG}${NC}"
        
        # Show validation errors if present
        ERRORS=$(echo "$RESPONSE_BODY" | jq -r '.errors // empty')
        if [ ! -z "$ERRORS" ]; then
            echo ""
            echo -e "${RED}Validation Errors:${NC}"
            echo "$ERRORS" | jq '.'
        fi
    fi
fi

echo ""
echo -e "${YELLOW}========================================${NC}"

# Alternative curl command examples
echo ""
echo -e "${YELLOW}Alternative curl commands:${NC}"
echo ""
echo "# Basic curl command (without formatting):"
echo "curl -X POST '${BASE_URL}${ENDPOINT}' \\"
echo "  -H 'Content-Type: application/json' \\"
echo "  -H 'X-API-Key: ${API_KEY}' \\"
echo "  -d '{\"client_number\":\"${CLIENT_NUMBER}\",\"amount\":${AMOUNT}}'"
echo ""
echo "# With pretty printing (requires jq):"
echo "curl -X POST '${BASE_URL}${ENDPOINT}' \\"
echo "  -H 'Content-Type: application/json' \\"
echo "  -H 'X-API-Key: ${API_KEY}' \\"
echo "  -d '{\"client_number\":\"${CLIENT_NUMBER}\",\"amount\":${AMOUNT}}' | jq '.'"
echo ""
echo "# With verbose output for debugging:"
echo "curl -v -X POST '${BASE_URL}${ENDPOINT}' \\"
echo "  -H 'Content-Type: application/json' \\"
echo "  -H 'X-API-Key: ${API_KEY}' \\"
echo "  -d '{\"client_number\":\"${CLIENT_NUMBER}\",\"amount\":${AMOUNT}}'"
echo ""
echo "# Test with different amounts:"
echo "# Minimum loan (100,000 TZS):"
echo "curl -X POST '${BASE_URL}${ENDPOINT}' \\"
echo "  -H 'Content-Type: application/json' \\"
echo "  -H 'X-API-Key: ${API_KEY}' \\"
echo "  -d '{\"client_number\":\"${CLIENT_NUMBER}\",\"amount\":100000}'"
echo ""
echo "# Maximum loan (100,000,000 TZS):"
echo "curl -X POST '${BASE_URL}${ENDPOINT}' \\"
echo "  -H 'Content-Type: application/json' \\"
echo "  -H 'X-API-Key: ${API_KEY}' \\"
echo "  -d '{\"client_number\":\"${CLIENT_NUMBER}\",\"amount\":100000000}'"