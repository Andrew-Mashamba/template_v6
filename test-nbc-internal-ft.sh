#!/bin/bash

# Configuration
API_URL="http://cbpuat.intra.nbc.co.tz:6666/api/nbc-sg/internal_ft"
API_KEY="b1f6c3a92e4d9a7c34f981cf22b54e716e5e8d2aab57ff449c6a1347088c3f55"
NBC_AUTH="Basic c2FjY29zaXNhbGU6QE5CQ3NhY2Nvc2lzYWxlTHRk"

# Generate unique channel reference with timestamp
CHANNEL_REF="CH$(date +%Y%m%d%H%M%S)"

# Request body
REQUEST_BODY=$(cat <<EOF
{
  "header": {
    "service": "internal_ft",
    "extra": {"pyrName": "Test User"}
  },
  "channelId": "SACCOSNBC",
  "channelRef": "${CHANNEL_REF}",
  "creditAccount": "011191000036",
  "debitAccount": "011191000035",
  "amount": "1000",
  "narration": "Test NBC Internal Transfer..."
}
EOF
)

# Display the curl command
echo "=== CURL Request ==="
echo ""
echo "curl -X POST '${API_URL}' \\"
echo "  -H 'Content-Type: application/json' \\"
echo "  -H 'X-Api-Key: ${API_KEY}' \\"
echo "  -H 'NBC-Authorization: ${NBC_AUTH}' \\"
echo "  -H 'Signature: [Generated using private key]' \\"
echo "  -d '${REQUEST_BODY}'"
echo ""
echo "=== Request Details ==="
echo "URL: ${API_URL}"
echo "Channel Reference: ${CHANNEL_REF}"
echo ""
echo "Headers:"
echo "  X-Api-Key: ${API_KEY}"
echo "  NBC-Authorization: ${NBC_AUTH}"
echo "  Signature: [Needs to be generated with private key]"
echo ""
echo "Body (formatted):"
echo "${REQUEST_BODY}" | jq '.' 2>/dev/null || echo "${REQUEST_BODY}"
echo ""
echo "=== Note ==="
echo "The Signature header needs to be generated using the private key."
echo "This typically involves signing the request body with RSA or similar algorithm."