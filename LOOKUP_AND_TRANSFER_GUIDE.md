# TIPS Lookup and Transfer Implementation Guide

## Last Updated: 2025-09-02

## 1. LOOKUP STRUCTURE (Critical First Step)

### Correct Lookup Payload Structure
```json
{
  "serviceName": "TIPS_LOOKUP",
  "clientId": "IB",
  "clientRef": "{{timestamp}}",
  "identifierType": "MSISDN",  // or "BANK"
  "identifier": "0715000000",   // Phone WITHOUT country code for MSISDN
  "destinationFsp": "VMCASHIN", // Provider code
  "debitAccount": "06012040022",
  "debitAccountCurrency": "TZS",
  "debitAccountBranchCode": "060",
  "amount": "5000",
  "debitAccountCategory": "BUSINESS"
}
```

### Key Requirements for Lookup:
1. **serviceName**: Must be exactly `"TIPS_LOOKUP"`
2. **clientId**: Your client ID (e.g., "IB")
3. **clientRef**: Unique reference (timestamp works well)
4. **identifierType**: 
   - `"MSISDN"` for mobile wallets
   - `"BANK"` for bank accounts
5. **identifier**:
   - For MSISDN: Phone number WITHOUT country code (e.g., "0715000000")
   - For BANK: Full account number
6. **destinationFsp**: Provider code (see list below)
7. **debitAccount**: Source account number
8. **debitAccountBranchCode**: First 3 digits of debit account
9. **amount**: Amount as string
10. **debitAccountCategory**: "BUSINESS" or "PERSON"

### Wallet Provider Codes (destinationFsp):
```
VMCASHIN           - M-Pesa (Vodacom)
TPCASHIN           - TigoPesa  
AIRTELMONEYCASHIN  - Airtel Money
HALOPESACASHIN     - HaloPesa
EZYPESACASHIN      - EzyPesa
```

### Bank Codes (destinationFsp):
```
CRDBTZTZ  - CRDB Bank
NMIBTZTZ  - NMB Bank
CORUTZTZ  - NBC Bank
(etc.)
```

## 2. TRANSFER STRUCTURE (After Successful Lookup)

### B2W (Bank to Wallet) Transfer
```json
{
  "serviceName": "TIPS_B2W_TRANSFER",
  "clientId": "IB",
  "clientRef": "REF123456",
  "customerRef": "CUSTOMERREF123456",
  "lookupRef": "ALPHANUMERICONLY",  // CRITICAL: Strip hyphens!
  "timestamp": "2025-09-02T10:00:00Z",
  "callbackUrl": "http://localhost:90/post",
  
  "payerDetails": {
    "identifierType": "BANK",
    "identifier": "06012040022",
    "phoneNumber": "255715000001",
    "initiatorId": "123456",
    "branchCode": "060",
    "fspId": "060",
    "fullName": "SACCOS Account",
    "accountCategory": "BUSINESS",
    "accountType": "BANK",
    "identity": {"type": "", "value": ""}
  },
  
  "payeeDetails": {
    "identifierType": "MSISDN",
    "identifier": "0715000000",  // WITHOUT country code
    "fspId": "504",
    "destinationFsp": "VMCASHIN",
    "fullName": "Wallet User",
    "accountCategory": "PERSON",
    "accountType": "WALLET",  // CRITICAL for B2W
    "identity": {"type": "", "value": ""}
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
  
  "remarks": "Transfer to M-Pesa"
}
```

### B2B (Bank to Bank) Transfer
```json
{
  "serviceName": "TIPS_B2B_TRANSFER",
  // ... same structure but:
  
  "payeeDetails": {
    "identifierType": "BANK",  // BANK for B2B
    "identifier": "12345678901",  // Account number
    "fspId": "030",
    "destinationFsp": "CRDBTZTZ",
    "fullName": "Bank Account",
    "accountCategory": "PERSON",
    "accountType": "BANK",  // BANK for B2B
    "identity": {"type": "", "value": ""}
  }
}
```

## 3. CRITICAL IMPLEMENTATION NOTES

### Phone Number Formatting:
```php
// For lookup and transfer identifier (MSISDN):
function getPhoneWithoutCountryCode($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    if (substr($phone, 0, 3) === '255') {
        return '0' . substr($phone, 3);  // 255715000000 → 0715000000
    }
    
    if (substr($phone, 0, 1) === '0') {
        return $phone;  // Already in correct format
    }
    
    if (strlen($phone) === 9) {
        return '0' . $phone;  // 715000000 → 0715000000
    }
    
    return $phone;
}
```

### LookupRef Processing:
```php
// CRITICAL: lookupRef MUST be alphanumeric only
function toAlphanumeric($str) {
    return preg_replace('/[^A-Za-z0-9]/', '', $str);
}

// Usage:
$engineRef = "DTcf230271-dd58-4c40-a065-d6135ed43073";  // From lookup response
$lookupRef = toAlphanumeric($engineRef);  // "DTcf230271dd584c40a065d6135ed43073"
```

### Headers Required:
```php
// For Lookup:
[
    'Content-Type' => 'application/json',
    'Accept' => 'application/json',
    'X-Api-Key' => $apiKey,
    'Client-Id' => 'IB',
    'Service-Name' => 'TIPS_LOOKUP',
    'Signature' => $signature,
    'Timestamp' => '2025-09-02T10:00:00Z'
]

// For Transfer:
[
    'Accept' => 'application/json',
    'Content-Type' => 'application/json',
    'X-Trace-Uuid' => 'domestix-' . $uuid,
    'Signature' => $signature,
    'X-Api-Key' => $apiKey
]
```

## 4. COMPLETE FLOW

1. **Perform Lookup**
   - Use correct structure with all required fields
   - Phone numbers WITHOUT country code for MSISDN
   - Get `engineRef` from response

2. **Process engineRef**
   - Strip all non-alphanumeric characters
   - Store as `lookupRef`

3. **Execute Transfer**
   - Use processed `lookupRef`
   - Set correct `accountType` (BANK or WALLET)
   - Use phone WITHOUT country code for wallet identifier

## 5. ENDPOINTS

- **Lookup**: `POST /domestix/api/v2/lookup`
- **Transfer**: `POST /domestix/api/v2/outgoing-transfers`

## 6. TESTING

### Test Files:
- `/var/www/html/template/test-lookup-only.php` - Test lookup structure
- `/var/www/html/template/test-final-working.php` - Test complete flow
- `/var/www/html/template/test-correct-fields.php` - Test field structure

### Common Errors:
1. **"lookupRef must be alphanumeric"** → Strip hyphens from engineRef
2. **"Invalid identifier"** → Use phone WITHOUT country code for MSISDN
3. **"FSP not onboarded"** → Provider not available for TIPS
4. **Timeout** → Network issue, retry

## 7. SERVICE FILES UPDATED

1. **ExternalFundsTransferService.php**
   - Updated `lookupAccount()` method with correct structure
   - Added `toAlphanumeric()` helper
   - Updated transfer payload structure

2. **MobileWalletTransferService.php**
   - Updated `lookupWallet()` method with correct structure
   - Added `getPhoneWithoutCountryCode()` helper
   - Fixed identifier format for wallets

## 8. CONFIGURATION

Ensure `.env` has:
```
NBC_PAYMENTS_BASE_URL=https://22.32.245.67:443
NBC_PAYMENTS_API_KEY=your_api_key
NBC_PAYMENTS_CLIENT_ID=IB
NBC_PAYMENTS_SACCOS_ACCOUNT=06012040022
```