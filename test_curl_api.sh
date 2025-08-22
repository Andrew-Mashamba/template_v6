#!/bin/bash

# Test script for the simplified loan disbursement API

echo "========================================="
echo "  TESTING SIMPLIFIED LOAN API"
echo "========================================="
echo ""

# API endpoint
API_URL="http://localhost:8000/api/v1/loans/auto-disburse"

# Test data
CLIENT_NUMBER="000001"
AMOUNT="2000000"

echo "Testing API Endpoint: $API_URL"
echo "Client Number: $CLIENT_NUMBER"
echo "Loan Amount: TZS $(printf "%'.0f" $AMOUNT)"
echo ""
echo "Sending request..."
echo ""

# Make the API call
# Note: In production, you'll need proper API key and token
curl -X POST $API_URL \
  -H "Content-Type: application/json" \
  -H "X-API-Key: test-api-key" \
  -H "Authorization: Bearer test-token" \
  -d "{
    \"client_number\": \"$CLIENT_NUMBER\",
    \"amount\": $AMOUNT
  }" \
  -w "\n\nHTTP Status: %{http_code}\nTime: %{time_total}s\n" \
  2>/dev/null | python3 -m json.tool 2>/dev/null || echo "Response received (may need authentication setup)"

echo ""
echo "========================================="
echo "Note: If you see authentication errors, the API endpoints"
echo "are working but need proper API key setup."
echo ""
echo "To bypass authentication for testing, you can:"
echo "1. Temporarily disable middleware in routes/api.php"
echo "2. Or create proper API keys in the system"
echo "========================================="