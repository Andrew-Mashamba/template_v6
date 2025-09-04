#!/bin/bash

# External Bank Account Lookup - CURL Test Commands
# Generated: 2025-09-03
# 
# IMPORTANT: These commands use the NBC UAT environment
# Replace placeholders with actual values where needed

echo "================================================"
echo "NBC API - External Bank Account Lookup Tests"
echo "================================================"
echo ""

# Configuration
BASE_URL="https://22.32.245.67:443"
API_KEY="MDcyNjY2NWVkZDlkYTJmYWZiZTFiODFhNDQ5MWNkNTY3ODZhZjA2NTNiOTMwNzNiODVkMzVlOTNmN2UxZDE5NTUwZjc3M2I5MzQwYmRlZGRiYzdlMjUxMmU5NGUxMmQ4NmQxOGQ1NTIyYmM3YzlkNjYyY2U2ZjE2YjZhNjFkZjU="
CLIENT_ID="APP_IOS"
DEBIT_ACCOUNT="015103001490"

# Generate UUID function
generate_uuid() {
    cat /proc/sys/kernel/random/uuid
}

# Generate timestamp
TIMESTAMP=$(date +%s)
ISO_TIMESTAMP=$(date -u +"%Y-%m-%dT%H:%M:%S+00:00")

echo "Configuration:"
echo "- Base URL: $BASE_URL"
echo "- Client ID: $CLIENT_ID"
echo "- Debit Account: $DEBIT_ACCOUNT"
echo "- Timestamp: $ISO_TIMESTAMP"
echo ""
echo "================================================"
echo ""

# Test 1: CRDB Bank Account Lookup
echo "TEST 1: CRDB Bank Account Lookup"
echo "---------------------------------"
echo ""
echo "curl -X POST '$BASE_URL/domestix/api/v2/lookup' \\"
echo "  -H 'Accept: application/json' \\"
echo "  -H 'Content-Type: application/json' \\"
echo "  -H 'X-Trace-Uuid: domestix-$(generate_uuid)' \\"
echo "  -H 'x-api-key: $API_KEY' \\"
echo "  -H 'Client-Id: $CLIENT_ID' \\"
echo "  -H 'Service-Name: TIPS_LOOKUP' \\"
echo "  -H 'Timestamp: $ISO_TIMESTAMP' \\"
echo "  -H 'Signature: dummy_signature_replace_with_actual' \\"
echo "  -k \\"
echo "  -d '{"
echo '    "serviceName": "TIPS_LOOKUP",'
echo '    "clientId": "'$CLIENT_ID'",'
echo '    "clientRef": "LOOKUP'$TIMESTAMP'",'
echo '    "identifierType": "BANK",'
echo '    "identifier": "12334567789",'
echo '    "destinationFsp": "CORUTZTZ",'
echo '    "debitAccount": "'$DEBIT_ACCOUNT'",'
echo '    "debitAccountCurrency": "TZS",'
echo '    "debitAccountBranchCode": "015",'
echo '    "amount": "5000",'
echo '    "debitAccountCategory": "BUSINESS"'
echo "  }'"
echo ""
echo ""

# Test 2: NMB Bank Account Lookup
echo "TEST 2: NMB Bank Account Lookup"
echo "--------------------------------"
echo ""
echo "curl -X POST '$BASE_URL/domestix/api/v2/lookup' \\"
echo "  -H 'Accept: application/json' \\"
echo "  -H 'Content-Type: application/json' \\"
echo "  -H 'X-Trace-Uuid: domestix-$(generate_uuid)' \\"
echo "  -H 'x-api-key: $API_KEY' \\"
echo "  -H 'Client-Id: $CLIENT_ID' \\"
echo "  -H 'Service-Name: TIPS_LOOKUP' \\"
echo "  -H 'Timestamp: $ISO_TIMESTAMP' \\"
echo "  -H 'Signature: dummy_signature_replace_with_actual' \\"
echo "  -k \\"
echo "  -d '{"
echo '    "serviceName": "TIPS_LOOKUP",'
echo '    "clientId": "'$CLIENT_ID'",'
echo '    "clientRef": "LOOKUP'$TIMESTAMP'2",'
echo '    "identifierType": "BANK",'
echo '    "identifier": "1234567890123",'
echo '    "destinationFsp": "NMIBTZT0",'
echo '    "debitAccount": "'$DEBIT_ACCOUNT'",'
echo '    "debitAccountCurrency": "TZS",'
echo '    "debitAccountBranchCode": "015",'
echo '    "amount": "10000",'
echo '    "debitAccountCategory": "BUSINESS"'
echo "  }'"
echo ""
echo ""

# Test 3: Mobile Wallet (Vodacom M-Pesa) Lookup
echo "TEST 3: Vodacom M-Pesa Wallet Lookup"
echo "-------------------------------------"
echo ""
echo "curl -X POST '$BASE_URL/domestix/api/v2/lookup' \\"
echo "  -H 'Accept: application/json' \\"
echo "  -H 'Content-Type: application/json' \\"
echo "  -H 'X-Trace-Uuid: domestix-$(generate_uuid)' \\"
echo "  -H 'x-api-key: $API_KEY' \\"
echo "  -H 'Client-Id: $CLIENT_ID' \\"
echo "  -H 'Service-Name: TIPS_LOOKUP' \\"
echo "  -H 'Timestamp: $ISO_TIMESTAMP' \\"
echo "  -H 'Signature: dummy_signature_replace_with_actual' \\"
echo "  -k \\"
echo "  -d '{"
echo '    "serviceName": "TIPS_LOOKUP",'
echo '    "clientId": "'$CLIENT_ID'",'
echo '    "clientRef": "LOOKUP'$TIMESTAMP'3",'
echo '    "identifierType": "MSISDN",'
echo '    "identifier": "0748045601",'
echo '    "destinationFsp": "VMCASHIN",'
echo '    "debitAccount": "'$DEBIT_ACCOUNT'",'
echo '    "debitAccountCurrency": "TZS",'
echo '    "debitAccountBranchCode": "015",'
echo '    "amount": "900000",'
echo '    "debitAccountCategory": "BUSINESS"'
echo "  }'"
echo ""
echo ""

# Test 4: Airtel Money Wallet Lookup
echo "TEST 4: Airtel Money Wallet Lookup"
echo "-----------------------------------"
echo ""
echo "curl -X POST '$BASE_URL/domestix/api/v2/lookup' \\"
echo "  -H 'Accept: application/json' \\"
echo "  -H 'Content-Type: application/json' \\"
echo "  -H 'X-Trace-Uuid: domestix-$(generate_uuid)' \\"
echo "  -H 'x-api-key: $API_KEY' \\"
echo "  -H 'Client-Id: $CLIENT_ID' \\"
echo "  -H 'Service-Name: TIPS_LOOKUP' \\"
echo "  -H 'Timestamp: $ISO_TIMESTAMP' \\"
echo "  -H 'Signature: dummy_signature_replace_with_actual' \\"
echo "  -k \\"
echo "  -d '{"
echo '    "serviceName": "TIPS_LOOKUP",'
echo '    "clientId": "'$CLIENT_ID'",'
echo '    "clientRef": "LOOKUP'$TIMESTAMP'4",'
echo '    "identifierType": "MSISDN",'
echo '    "identifier": "0786670024",'
echo '    "destinationFsp": "AMCASHIN",'
echo '    "debitAccount": "'$DEBIT_ACCOUNT'",'
echo '    "debitAccountCurrency": "TZS",'
echo '    "debitAccountBranchCode": "015",'
echo '    "amount": "5000",'
echo '    "debitAccountCategory": "BUSINESS"'
echo "  }'"
echo ""
echo ""

echo "================================================"
echo "NOTES:"
echo "================================================"
echo ""
echo "1. The -k flag disables SSL certificate verification (for testing only)"
echo "2. Replace 'dummy_signature_replace_with_actual' with a valid signature"
echo "3. The signature should be generated using your private key"
echo "4. UUID and timestamps are generated automatically"
echo "5. To run a specific test, copy the curl command and execute it"
echo ""
echo "To save response to a file:"
echo "Add: -o response.json"
echo ""
echo "To see response headers:"
echo "Add: -v (verbose) or -i (include headers)"
echo ""
echo "To format JSON response:"
echo "Pipe to: | python -m json.tool"
echo ""
echo "Example with formatting:"
echo "curl [command] | python -m json.tool"
echo ""