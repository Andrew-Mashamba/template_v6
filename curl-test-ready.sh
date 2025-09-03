#!/bin/bash

# NBC API - Ready-to-Run CURL Commands
# Generated: 2025-09-03
# These commands are ready to copy and paste

echo "================================================"
echo "NBC API - Copy & Paste CURL Commands"
echo "================================================"
echo ""
echo "Copy any of the commands below to test the API:"
echo ""

# Generate a simple UUID (simplified for easy copy-paste)
UUID=$(uuidgen 2>/dev/null || echo "$(date +%s)-$(shuf -i 1000-9999 -n 1)-$(shuf -i 1000-9999 -n 1)-$(shuf -i 1000-9999 -n 1)")

# ============================================
# TEST 1: CRDB Bank Account Lookup
# ============================================
cat << 'EOF1'
# TEST 1: CRDB Bank Account Lookup
curl -X POST 'https://22.32.245.67:443/domestix/api/v2/lookup' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -H 'X-Trace-Uuid: domestix-test-001' \
  -H 'x-api-key: MDcyNjY2NWVkZDlkYTJmYWZiZTFiODFhNDQ5MWNkNTY3ODZhZjA2NTNiOTMwNzNiODVkMzVlOTNmN2UxZDE5NTUwZjc3M2I5MzQwYmRlZGRiYzdlMjUxMmU5NGUxMmQ4NmQxOGQ1NTIyYmM3YzlkNjYyY2U2ZjE2YjZhNjFkZjU=' \
  -H 'Client-Id: APP_IOS' \
  -H 'Service-Name: TIPS_LOOKUP' \
  -H 'Signature: dummy_signature' \
  -k \
  -d '{
    "serviceName": "TIPS_LOOKUP",
    "clientId": "APP_IOS",
    "clientRef": "TEST001",
    "identifierType": "BANK",
    "identifier": "12334567789",
    "destinationFsp": "CORUTZTZ",
    "debitAccount": "015103001490",
    "debitAccountCurrency": "TZS",
    "debitAccountBranchCode": "015",
    "amount": "5000",
    "debitAccountCategory": "BUSINESS"
  }'
EOF1

echo ""
echo ""

# ============================================
# TEST 2: NMB Bank Account Lookup
# ============================================
cat << 'EOF2'
# TEST 2: NMB Bank Account Lookup
curl -X POST 'https://22.32.245.67:443/domestix/api/v2/lookup' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -H 'X-Trace-Uuid: domestix-test-002' \
  -H 'x-api-key: MDcyNjY2NWVkZDlkYTJmYWZiZTFiODFhNDQ5MWNkNTY3ODZhZjA2NTNiOTMwNzNiODVkMzVlOTNmN2UxZDE5NTUwZjc3M2I5MzQwYmRlZGRiYzdlMjUxMmU5NGUxMmQ4NmQxOGQ1NTIyYmM3YzlkNjYyY2U2ZjE2YjZhNjFkZjU=' \
  -H 'Client-Id: APP_IOS' \
  -H 'Service-Name: TIPS_LOOKUP' \
  -H 'Signature: dummy_signature' \
  -k \
  -d '{
    "serviceName": "TIPS_LOOKUP",
    "clientId": "APP_IOS",
    "clientRef": "TEST002",
    "identifierType": "BANK",
    "identifier": "1234567890123",
    "destinationFsp": "NMIBTZT0",
    "debitAccount": "015103001490",
    "debitAccountCurrency": "TZS",
    "debitAccountBranchCode": "015",
    "amount": "10000",
    "debitAccountCategory": "BUSINESS"
  }'
EOF2

echo ""
echo ""

# ============================================
# TEST 3: Vodacom M-Pesa Wallet Lookup
# ============================================
cat << 'EOF3'
# TEST 3: Vodacom M-Pesa Wallet Lookup (Working Example from NBC)
curl -X POST 'https://22.32.245.67:443/domestix/api/v2/lookup' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -H 'X-Trace-Uuid: domestix-test-003' \
  -H 'x-api-key: MDcyNjY2NWVkZDlkYTJmYWZiZTFiODFhNDQ5MWNkNTY3ODZhZjA2NTNiOTMwNzNiODVkMzVlOTNmN2UxZDE5NTUwZjc3M2I5MzQwYmRlZGRiYzdlMjUxMmU5NGUxMmQ4NmQxOGQ1NTIyYmM3YzlkNjYyY2U2ZjE2YjZhNjFkZjU=' \
  -H 'Client-Id: APP_IOS' \
  -H 'Service-Name: TIPS_LOOKUP' \
  -H 'Signature: dummy_signature' \
  -k \
  -d '{
    "serviceName": "TIPS_LOOKUP",
    "clientId": "APP_IOS",
    "clientRef": "TEST003",
    "identifierType": "MSISDN",
    "identifier": "0748045601",
    "destinationFsp": "VMCASHIN",
    "debitAccount": "011103033734",
    "debitAccountCurrency": "TZS",
    "debitAccountBranchCode": "012",
    "amount": "900000",
    "debitAccountCategory": "BUSINESS"
  }'
EOF3

echo ""
echo ""

# ============================================
# TEST 4: Bank Transfer (POST)
# ============================================
cat << 'EOF4'
# TEST 4: Bank to Bank Transfer (B2B)
curl -X POST 'https://22.32.245.67:443/domestix/api/v2/outgoing-transfers' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -H 'X-Trace-Uuid: domestix-test-004' \
  -H 'x-api-key: MDcyNjY2NWVkZDlkYTJmYWZiZTFiODFhNDQ5MWNkNTY3ODZhZjA2NTNiOTMwNzNiODVkMzVlOTNmN2UxZDE5NTUwZjc3M2I5MzQwYmRlZGRiYzdlMjUxMmU5NGUxMmQ4NmQxOGQ1NTIyYmM3YzlkNjYyY2U2ZjE2YjZhNjFkZjU=' \
  -H 'Signature: dummy_signature' \
  -k \
  -d '{
    "serviceName": "TIPS_B2B_TRANSFER",
    "clientId": "APP_IOS",
    "clientRef": "TRANSFER001",
    "customerRef": "CUSTOMER001",
    "lookupRef": "LOOKUP001",
    "timestamp": "2025-09-03T12:00:00+00:00",
    "callbackUrl": "https://your-domain.com/callback",
    "payerDetails": {
      "identifierType": "BANK",
      "identifier": "015103001490",
      "phoneNumber": "255715000001",
      "initiatorId": "1234567890",
      "branchCode": "015",
      "fspId": "015",
      "fullName": "Test Payer",
      "accountCategory": "BUSINESS",
      "accountType": "BANK",
      "identity": {
        "type": "",
        "value": ""
      }
    },
    "payeeDetails": {
      "identifierType": "BANK",
      "identifier": "12334567789",
      "fspId": "003",
      "destinationFsp": "CORUTZTZ",
      "fullName": "Test Payee",
      "accountCategory": "PERSON",
      "accountType": "BANK",
      "identity": {
        "type": "",
        "value": ""
      }
    },
    "transactionDetails": {
      "debitAmount": "5000",
      "debitCurrency": "TZS",
      "creditAmount": "5000",
      "creditCurrency": "TZS",
      "productCode": "",
      "isServiceChargeApplicable": true,
      "serviceChargeBearer": "OUR"
    },
    "remarks": "Test B2B Transfer"
  }'
EOF4

echo ""
echo ""

# ============================================
# TEST 5: Wallet Transfer (B2W)
# ============================================
cat << 'EOF5'
# TEST 5: Bank to Wallet Transfer (B2W) - Working Example from NBC
curl -X POST 'https://22.32.245.67:443/domestix/api/v2/outgoing-transfers' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -H 'X-Trace-Uuid: domestix-test-005' \
  -H 'x-api-key: MDcyNjY2NWVkZDlkYTJmYWZiZTFiODFhNDQ5MWNkNTY3ODZhZjA2NTNiOTMwNzNiODVkMzVlOTNmN2UxZDE5NTUwZjc3M2I5MzQwYmRlZGRiYzdlMjUxMmU5NGUxMmQ4NmQxOGQ1NTIyYmM3YzlkNjYyY2U2ZjE2YjZhNjFkZjU=' \
  -H 'Signature: dummy_signature' \
  -k \
  -d '{
    "serviceName": "TIPS_B2W_TRANSFER",
    "clientId": "APP_IOS",
    "clientRef": "TRANSFER002",
    "customerRef": "CUSTOMER002",
    "lookupRef": "LOOKUP002",
    "timestamp": "2025-09-03T12:00:00+00:00",
    "callbackUrl": "https://your-domain.com/callback",
    "payerDetails": {
      "identifierType": "BANK",
      "identifier": "015103001490",
      "phoneNumber": "255653666201",
      "initiatorId": "1234567890",
      "branchCode": "015",
      "fspId": "015",
      "fullName": "LAZARO NGAIKA",
      "accountCategory": "PERSON",
      "accountType": "BANK",
      "identity": {
        "type": "",
        "value": ""
      }
    },
    "payeeDetails": {
      "identifierType": "MSISDN",
      "identifier": "0786670024",
      "fspId": "504",
      "destinationFsp": "AMCASHIN",
      "fullName": "Paul Alex",
      "accountCategory": "PERSON",
      "accountType": "WALLET",
      "identity": {
        "type": "",
        "value": ""
      }
    },
    "transactionDetails": {
      "debitAmount": "5000",
      "debitCurrency": "TZS",
      "creditAmount": "5000",
      "creditCurrency": "TZS",
      "productCode": "",
      "isServiceChargeApplicable": true,
      "serviceChargeBearer": "OUR"
    },
    "remarks": "Test B2W Transfer to Airtel Money"
  }'
EOF5

echo ""
echo ""

echo "================================================"
echo "TIPS FOR TESTING:"
echo "================================================"
echo ""
echo "1. Save response to file:"
echo "   Add: -o response.json"
echo ""
echo "2. See detailed output:"
echo "   Add: -v (verbose mode)"
echo ""
echo "3. Format JSON response:"
echo "   Add: | jq '.'"
echo ""
echo "4. Test with timeout:"
echo "   Add: --max-time 30"
echo ""
echo "5. Show response headers:"
echo "   Add: -i"
echo ""
echo "Example with all options:"
echo "curl [command] -v -o response.json --max-time 30 | jq '.'"
echo ""
echo "================================================"
echo ""