#!/bin/bash

# NBC Internal Funds Transfer Direct CURL Test
# Tests authentication and basic connectivity

echo "============================================"
echo "NBC Internal Funds Transfer - Direct API Test"
echo "============================================"
echo ""

# Configuration from environment
API_URL="http://cbpuat.intra.nbc.co.tz:6666/api/nbc-sg/internal_ft"
API_KEY="b1f6c3a92e4d9a7c34f981cf22b54e716e5e8d2aab57ff449c6a1347088c3f55"
USERNAME="saccosnbc"
PASSWORD="@NBCsaccosisaleLtd"
CHANNEL_ID="SACCOSNBC"

# Generate unique reference
CHANNEL_REF="CH$(date +%Y%m%d%H%M%S)$(openssl rand -hex 3 | tr '[:lower:]' '[:upper:]')"

# Basic Auth token (username:password in base64)
AUTH_TOKEN=$(echo -n "${USERNAME}:${PASSWORD}" | base64)

# Request body
REQUEST_BODY=$(cat <<EOF
{
  "header": {
    "service": "internal_ft",
    "extra": {
      "pyrName": "Test User"
    }
  },
  "channelId": "${CHANNEL_ID}",
  "channelRef": "${CHANNEL_REF}",
  "creditAccount": "011191000036",
  "creditCurrency": "TZS",
  "debitAccount": "011191000035",
  "debitCurrency": "TZS",
  "amount": "1000",
  "narration": "Test NBC Internal Transfer - $(date +%Y-%m-%d' '%H:%M:%S)"
}
EOF
)

echo "URL: ${API_URL}"
echo "Channel Reference: ${CHANNEL_REF}"
echo ""
echo "Headers:"
echo "  x-api-key: ${API_KEY:0:10}...${API_KEY: -4}"
echo "  NBC-Authorization: Basic ${AUTH_TOKEN:0:10}...${AUTH_TOKEN: -4}"
echo ""
echo "Request Body:"
echo "${REQUEST_BODY}" | jq '.' 2>/dev/null || echo "${REQUEST_BODY}"
echo ""
echo "Sending request..."
echo "============================================"
echo ""

# Make the actual request
RESPONSE=$(curl -s -w "\nHTTP_STATUS:%{http_code}" -X POST "${API_URL}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "x-api-key: ${API_KEY}" \
  -H "NBC-Authorization: Basic ${AUTH_TOKEN}" \
  -d "${REQUEST_BODY}")

# Extract HTTP status and response body
HTTP_STATUS=$(echo "${RESPONSE}" | tail -n 1 | sed 's/HTTP_STATUS://g')
RESPONSE_BODY=$(echo "${RESPONSE}" | sed '$d')

echo "HTTP Status: ${HTTP_STATUS}"
echo ""
echo "Response:"
if [ -n "${RESPONSE_BODY}" ]; then
    echo "${RESPONSE_BODY}" | jq '.' 2>/dev/null || echo "${RESPONSE_BODY}"
else
    echo "(Empty response)"
fi
echo ""

# Interpret the result
if [ "${HTTP_STATUS}" = "200" ] || [ "${HTTP_STATUS}" = "201" ]; then
    echo "✓ Request successful (HTTP ${HTTP_STATUS})"
    
    # Check NBC status code if available
    NBC_STATUS=$(echo "${RESPONSE_BODY}" | jq -r '.statusCode' 2>/dev/null)
    if [ "${NBC_STATUS}" = "600" ]; then
        echo "✓ NBC Transfer successful (Status 600)"
    elif [ -n "${NBC_STATUS}" ]; then
        echo "⚠ NBC Status: ${NBC_STATUS}"
    fi
elif [ "${HTTP_STATUS}" = "401" ]; then
    echo "✗ Authentication failed (HTTP 401)"
    echo ""
    echo "Possible issues:"
    echo "  1. Invalid API key"
    echo "  2. Invalid username/password"
    echo "  3. Missing or incorrect NBC-Authorization header"
    echo ""
    echo "Current auth configuration:"
    echo "  Username: ${USERNAME}"
    echo "  Password: ${PASSWORD:0:3}***${PASSWORD: -3}"
    echo "  Auth Token: ${AUTH_TOKEN}"
elif [ "${HTTP_STATUS}" = "400" ]; then
    echo "✗ Bad request (HTTP 400)"
    echo "Check the request body format and required fields"
elif [ "${HTTP_STATUS}" = "404" ]; then
    echo "✗ Endpoint not found (HTTP 404)"
    echo "Check the API URL: ${API_URL}"
elif [ "${HTTP_STATUS}" = "500" ] || [ "${HTTP_STATUS}" = "502" ] || [ "${HTTP_STATUS}" = "503" ]; then
    echo "✗ Server error (HTTP ${HTTP_STATUS})"
    echo "The NBC server encountered an error"
else
    echo "✗ Unexpected status (HTTP ${HTTP_STATUS})"
fi

echo ""
echo "============================================"
echo "Test completed at $(date)"
echo "============================================"