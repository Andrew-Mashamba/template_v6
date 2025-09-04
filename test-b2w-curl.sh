#!/bin/bash

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[0;33m'
CYAN='\033[0;36m'
NC='\033[0m'

echo -e "${CYAN}================================================${NC}"
echo -e "${CYAN}     B2W CURL TEST - DIRECT API CALL${NC}"
echo -e "${CYAN}================================================${NC}"
echo -e "Date: $(date '+%Y-%m-%d %H:%M:%S')\n"

# Generate alphanumeric reference
CLIENT_REF="REF$(date +%s | tail -c 10)"

echo -e "${YELLOW}Test Parameters:${NC}"
echo "• Phone: 0748045601"
echo "• Provider: VMCASHIN (M-Pesa)"
echo "• Amount: TZS 900,000"
echo "• Client Ref: $CLIENT_REF"
echo ""

echo -e "${YELLOW}Sending B2W lookup request...${NC}\n"

# Make the request
RESPONSE=$(curl -s -w "\nHTTP_CODE:%{http_code}\nTIME_TOTAL:%{time_total}" \
  -X POST https://22.32.245.67:443/domestix/api/v2/lookup \
  -H "Content-Type: application/json" \
  -H "x-api-key: f3a1248b47e3e965f1c5ac1f3adb9b94" \
  --insecure \
  --connect-timeout 10 \
  --max-time 30 \
  -d "{
    \"serviceName\": \"TIPS_LOOKUP\",
    \"clientId\": \"APP_IOS\",
    \"clientRef\": \"$CLIENT_REF\",
    \"identifierType\": \"MSISDN\",
    \"identifier\": \"0748045601\",
    \"destinationFsp\": \"VMCASHIN\",
    \"debitAccount\": \"015103001490\",
    \"debitAccountCurrency\": \"TZS\",
    \"debitAccountBranchCode\": \"015\",
    \"amount\": \"900000\",
    \"debitAccountCategory\": \"BUSINESS\"
  }")

# Extract HTTP code and time
HTTP_CODE=$(echo "$RESPONSE" | grep "HTTP_CODE:" | cut -d: -f2)
TIME_TOTAL=$(echo "$RESPONSE" | grep "TIME_TOTAL:" | cut -d: -f2)
BODY=$(echo "$RESPONSE" | sed '/HTTP_CODE:/d' | sed '/TIME_TOTAL:/d')

echo -e "${YELLOW}Response Time:${NC} ${TIME_TOTAL}s"
echo -e "${YELLOW}HTTP Status:${NC} $HTTP_CODE\n"

if [ "$HTTP_CODE" == "200" ]; then
    echo -e "${GREEN}✅ Request Successful${NC}\n"
    echo -e "${YELLOW}Response Body:${NC}"
    echo "$BODY" | python3 -m json.tool 2>/dev/null || echo "$BODY"
else
    echo -e "${RED}❌ Request Failed${NC}\n"
    if [ -n "$BODY" ]; then
        echo -e "${YELLOW}Response Body:${NC}"
        echo "$BODY" | python3 -m json.tool 2>/dev/null || echo "$BODY"
    fi
fi

echo -e "\n${CYAN}=== Test Complete ===${NC}"