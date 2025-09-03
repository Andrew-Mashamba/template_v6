# Payment API Analysis Report

**Date**: September 2, 2025
**Environment**: UAT
**Base URL**: https://22.32.245.67:443

## API Endpoints Status

### 1. Internal Funds Transfer (IFT)
- **Endpoint**: `/api/nbc/account/verify`
- **Method**: POST
- **Status**: ❌ 404 Not Found
- **Issue**: Endpoint does not exist at this path

### 2. External Funds Transfer (EFT) - TIPS
- **Endpoint**: `/domestix/api/v2/lookup`
- **Method**: POST
- **Status**: ⚠️ 400 Bad Request
- **Issue**: Validation error - `clientRef should only contain alphanumeric characters`
- **Fix Required**: Remove underscores from clientRef (use only alphanumeric)

### 3. Mobile Wallet Transfer
- **Endpoint**: `/domestix/api/v2/lookup`
- **Method**: POST
- **Status**: ⚠️ 400 Bad Request
- **Issue**: Same as EFT - clientRef validation
- **Fix Required**: Use alphanumeric clientRef only

### 4. GEPG Bill Inquiry
- **Endpoint**: `/api/nbc-sg/v2/billquery`
- **Method**: POST
- **Content-Type**: application/xml
- **Status**: ❌ 404 Not Found
- **Issue**: Endpoint does not exist at this path

### 5. LUKU Meter Lookup
- **Endpoint**: `/api/nbc-luku/v2/lookup`
- **Method**: POST
- **Status**: ❌ 404 Not Found
- **Issue**: Endpoint does not exist at this path

## Key Findings

### Working APIs
- **TIPS/DOMESTIX API** (`/domestix/api/v2/lookup`) is reachable but requires:
  - Alphanumeric clientRef only (no underscores or special characters)
  - Proper signature generation
  - Valid API key and client ID

### API Response Headers
```
X-RateLimit-Remaining: -1
X-RateLimit-Burst-Capacity: 250
X-RateLimit-Replenish-Rate: 200
Strict-Transport-Security: max-age=31536000
```

### Validation Rules Discovered
1. **clientRef**: Must be alphanumeric only (no underscores, dashes, or special characters)
2. **Service Names**: Must match exactly (e.g., "TIPS_LOOKUP")
3. **Headers Required**:
   - `X-Api-Key`
   - `Client-Id`
   - `Service-Name`
   - `Signature`
   - `Timestamp`

## Required Code Changes

### 1. Fix Reference Generation
```php
// Current (incorrect):
$reference = 'IFT_20250902124952_123C84';

// Fixed (correct):
$reference = 'IFT20250902124952123C84';
```

### 2. Update Service Methods
All `generateReference()` methods need to remove underscores:
```php
protected function generateReference(string $prefix = 'IFT'): string
{
    // Remove underscores, use only alphanumeric
    return $prefix . date('YmdHis') . strtoupper(substr(md5(uniqid()), 0, 6));
}
```

## Correct Request Formats

### TIPS Lookup (Working with fix)
```json
{
    "serviceName": "TIPS_LOOKUP",
    "clientId": "IB",
    "clientRef": "EFTLOOKUP20250902125126",  // No underscores
    "identifierType": "BANK",
    "identifier": "12345678901",
    "destinationFsp": "NMIBTZTZ",
    "debitAccount": "06012040022",
    "debitAccountCurrency": "TZS",
    "debitAccountBranchCode": "060",
    "amount": "1",
    "debitAccountCategory": "BUSINESS"
}
```

## Recommendations

1. **Immediate Actions**:
   - Fix reference generation to use alphanumeric only
   - Verify correct API endpoints with NBC documentation
   - Test with fixed clientRef format

2. **Configuration Updates Needed**:
   - Confirm IFT endpoint path
   - Confirm GEPG endpoint path
   - Confirm LUKU endpoint path

3. **Service Improvements**:
   - Add validation for clientRef format before sending
   - Implement better error messages for validation failures
   - Add retry logic for transient failures

## Next Steps

1. Update all payment services to generate alphanumeric references
2. Get correct endpoint URLs from NBC API documentation
3. Re-test with corrected payloads
4. Implement comprehensive error handling for validation errors