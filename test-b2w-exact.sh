#!/bin/bash

echo "Testing B2W lookup with exact parameters..."
echo "Account: 011103033734"
echo "Phone: 0748045601"
echo "Provider: VMCASHIN"
echo "Amount: 900,000 TZS"
echo ""

START=$(date +%s)

RESPONSE=$(curl -s -w "\nHTTP_STATUS:%{http_code}" \
  -X POST "https://22.32.245.67:443/domestix/api/v2/lookup" \
  -H "Content-Type: application/json" \
  -H "x-api-key: f3a1248b47e3e965f1c5ac1f3adb9b94" \
  --insecure \
  --connect-timeout 15 \
  --max-time 45 \
  -d '{
    "serviceName": "TIPS_LOOKUP",
    "clientId": "APP_IOS",
    "clientRef": "REF'$(date +%s%N | head -c 16)'",
    "identifierType": "MSISDN",
    "identifier": "0748045601",
    "destinationFsp": "VMCASHIN",
    "debitAccount": "011103033734",
    "debitAccountCurrency": "TZS",
    "debitAccountBranchCode": "012",
    "amount": "900000",
    "debitAccountCategory": "BUSINESS"
  }')

END=$(date +%s)
DURATION=$((END - START))

HTTP_STATUS=$(echo "$RESPONSE" | grep -o "HTTP_STATUS:[0-9]*" | cut -d: -f2)
BODY=$(echo "$RESPONSE" | sed '/HTTP_STATUS:/d')

echo "Response time: ${DURATION}s"
echo "HTTP Status: ${HTTP_STATUS}"
echo ""

if [ -n "$BODY" ]; then
    echo "Response:"
    echo "$BODY" | python3 -m json.tool 2>/dev/null || echo "$BODY"
else
    echo "No response body received (timeout)"
fi