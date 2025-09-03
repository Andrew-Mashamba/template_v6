# Payment Services Update Documentation

## Update Date: 2025-09-02

## Critical Changes Applied

### 1. ExternalFundsTransferService.php
**Location:** `/var/www/html/template/app/Services/Payments/ExternalFundsTransferService.php`

#### Key Updates:
- **Endpoint Changed:** Now uses `/domestix/api/v2/outgoing-transfers` for all transfers
- **Service Name:** Uses `TIPS_B2B_TRANSFER` for bank-to-bank transfers
- **Payload Structure:** Completely restructured to match NBC API requirements

#### New Payload Structure:
```php
[
    'serviceName' => 'TIPS_B2B_TRANSFER',
    'clientId' => $clientId,
    'clientRef' => $reference,  // Alphanumeric only
    'customerRef' => $customerRef,
    'lookupRef' => $lookupRef,  // MUST be alphanumeric only (no hyphens)
    'timestamp' => Carbon::now()->toIso8601String(),
    'callbackUrl' => 'http://localhost:90/post',
    
    'payerDetails' => [
        'identifierType' => 'BANK',
        'identifier' => $accountNumber,
        'phoneNumber' => $phoneNumber,
        'initiatorId' => $initiatorId,
        'branchCode' => $branchCode,
        'fspId' => $fspId,
        'fullName' => $accountName,
        'accountCategory' => 'BUSINESS',  // or 'PERSON'
        'accountType' => 'BANK',
        'identity' => ['type' => '', 'value' => '']
    ],
    
    'payeeDetails' => [
        'identifierType' => 'BANK',
        'identifier' => $accountNumber,
        'fspId' => $fspId,
        'destinationFsp' => $bankCode,
        'fullName' => $accountName,
        'accountCategory' => 'PERSON',
        'accountType' => 'BANK',
        'identity' => ['type' => '', 'value' => '']
    ],
    
    'transactionDetails' => [
        'debitAmount' => (string)$amount,
        'debitCurrency' => 'TZS',
        'creditAmount' => (string)$amount,
        'creditCurrency' => 'TZS',
        'productCode' => '',
        'isServiceChargeApplicable' => true,
        'serviceChargeBearer' => 'OUR'  // or 'BEN' or 'SHA'
    ],
    
    'remarks' => 'Transfer description'
]
```

#### New Helper Methods Added:
```php
// Convert string to alphanumeric only (CRITICAL for lookupRef)
protected function toAlphanumeric(string $str): string
{
    return preg_replace('/[^A-Za-z0-9]/', '', $str);
}

// Generate UUID for X-Trace-Uuid header
protected function generateUUID(): string
{
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', ...);
}
```

### 2. MobileWalletTransferService.php
**Location:** `/var/www/html/template/app/Services/Payments/MobileWalletTransferService.php`

#### Key Updates:
- **Endpoint Changed:** Now uses `/domestix/api/v2/outgoing-transfers`
- **Service Name:** Uses `TIPS_B2W_TRANSFER` for bank-to-wallet transfers
- **Phone Number Handling:** Wallet identifier should NOT include country code

#### B2W Specific Structure:
```php
'payerDetails' => [
    'identifierType' => 'BANK',
    'accountType' => 'BANK',
    // ... rest of bank details
],

'payeeDetails' => [
    'identifierType' => 'MSISDN',
    'identifier' => '0715000000',  // WITHOUT country code for wallets
    'accountType' => 'WALLET',     // CRITICAL: Must be 'WALLET' for B2W
    'destinationFsp' => 'VMCASHIN', // Provider-specific code
    // ... rest of wallet details
]
```

#### Provider FSP IDs:
```php
$fspIds = [
    'MPESA' => '504',
    'TIGOPESA' => '505',
    'AIRTELMONEY' => '506',
    'HALOPESA' => '507',
    'EZYPESA' => '508'
];
```

#### Provider Destination FSP Codes:
```php
const PROVIDERS = [
    'MPESA' => 'VMCASHIN',
    'TIGOPESA' => 'TPCASHIN',
    'AIRTELMONEY' => 'AIRTELMONEYCASHIN',
    'HALOPESA' => 'HALOPESACASHIN',
    'EZYPESA' => 'EZYPESACASHIN'
];
```

## Critical Requirements

### 1. Alphanumeric lookupRef
The `lookupRef` field MUST contain only alphanumeric characters. Any hyphens or special characters from the lookup response must be stripped:
```php
$lookupRef = $this->toAlphanumeric($engineRef);
```

### 2. Two-Step Process
All transfers require:
1. **Lookup:** Verify account/wallet exists and get engineRef
2. **Transfer:** Execute the actual transfer using the lookupRef

### 3. Account Type Determines Transfer Type
- **B2B:** Both payer and payee have `accountType: 'BANK'`
- **B2W:** Payer has `accountType: 'BANK'`, payee has `accountType: 'WALLET'`

### 4. Headers Required
```php
[
    'Accept' => 'application/json',
    'Content-Type' => 'application/json',
    'X-Trace-Uuid' => 'domestix-' . $uuid,
    'Signature' => $signature,
    'X-Api-Key' => $apiKey
]
```

## Testing Commands

### Test B2B Transfer:
```bash
php /var/www/html/template/test-final-working.php
```

### Test B2W Transfer:
```bash
php /var/www/html/template/test-correct-fields.php
```

## Error Handling

### Common Errors and Solutions:

1. **"lookupRef must be alphanumeric"**
   - Solution: Strip all non-alphanumeric characters from engineRef
   
2. **"Missing required fields"**
   - Solution: Ensure nested structure with payerDetails, payeeDetails, transactionDetails
   
3. **404 on endpoint**
   - Solution: Use `/domestix/api/v2/outgoing-transfers` for all transfers

## API Endpoints

- **Lookup:** `POST /domestix/api/v2/lookup`
- **Transfer:** `POST /domestix/api/v2/outgoing-transfers`
- **Both work for B2B and B2W transfers**

## Configuration

Ensure these are set in `.env`:
```env
NBC_PAYMENTS_BASE_URL=https://22.32.245.67:443
NBC_PAYMENTS_API_KEY=your_api_key
NBC_PAYMENTS_CLIENT_ID=IB
NBC_PAYMENTS_SACCOS_ACCOUNT=06012040022
NBC_PAYMENTS_CALLBACK_URL=http://your-domain/api/callback
```

## Next Steps

1. Test with valid account numbers
2. Implement callback handler for async responses
3. Add transaction status checking
4. Implement retry logic for failed transfers
5. Add comprehensive error logging