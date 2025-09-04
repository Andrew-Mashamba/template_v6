# External Bank Account Lookup Test Report

**Date:** 2025-09-03 09:26:43
**Environment:** local
**Base URL:** https://22.32.245.67:443
**Client ID:** APP_IOS
**SACCOS Account:** 015103001490

---

## Service Configuration

- **Service:** ExternalFundsTransferService
- **Endpoint:** `/domestix/api/v2/lookup`
- **Method:** POST

## Test #1: CRDB Bank Account

### Request Details

| Field | Value |
|-------|-------|
| Account Number | 12334567789 |
| Bank Code | CORUTZTZ |
| Amount | 5,000 TZS |
| Request Time | 2025-09-03 09:26:43 |

### Request Headers

```http
Content-Type: application/json
Accept: application/json
X-Api-Key: [REDACTED]
X-Trace-Uuid: domestix-f4603b48-0e7b-49b8-b489-7edbaee02d7a
Client-Id: APP_IOS
Service-Name: TIPS_LOOKUP
Signature: [REDACTED]
Timestamp: 2025-09-03T09:26:43+00:00
```

### Request Body

```json
{
    "serviceName": "TIPS_LOOKUP",
    "clientId": "APP_IOS",
    "clientRef": "LOOKUP2025090309264326BC07",
    "identifierType": "BANK",
    "identifier": "12334567789",
    "destinationFsp": "CORUTZTZ",
    "debitAccount": "015103001490",
    "debitAccountCurrency": "TZS",
    "debitAccountBranchCode": "015",
    "amount": "5000",
    "debitAccountCategory": "BUSINESS"
}
```

### Response

| Field | Value |
|-------|-------|
| Response Time | 2711.86ms |
| Status | SUCCESS ✓ |
| Account Name |  |
| Bank Name |  |
| Can Receive | Yes |
| Engine Ref | DTdd2ef64e-d4dd-4559-98df-ba858f13df48 |

### Full Response Data

```json
{
    "success": true,
    "account_number": "12334567789",
    "account_name": "",
    "bank_code": "CORUTZTZ",
    "bank_name": "",
    "can_receive": true,
    "engine_ref": "DTdd2ef64e-d4dd-4559-98df-ba858f13df48",
    "response_time": 2711.68
}
```

## Test #2: NMB Bank Account

### Request Details

| Field | Value |
|-------|-------|
| Account Number | 1234567890123 |
| Bank Code | NMIBTZT0 |
| Amount | 10,000 TZS |
| Request Time | 2025-09-03 09:26:47 |

### Request Headers

```http
Content-Type: application/json
Accept: application/json
X-Api-Key: [REDACTED]
X-Trace-Uuid: domestix-3a2890bd-82db-411e-9400-34bc7ae71e72
Client-Id: APP_IOS
Service-Name: TIPS_LOOKUP
Signature: [REDACTED]
Timestamp: 2025-09-03T09:26:47+00:00
```

### Request Body

```json
{
    "serviceName": "TIPS_LOOKUP",
    "clientId": "APP_IOS",
    "clientRef": "LOOKUP2025090309264726547B",
    "identifierType": "BANK",
    "identifier": "1234567890123",
    "destinationFsp": "NMIBTZT0",
    "debitAccount": "015103001490",
    "debitAccountCurrency": "TZS",
    "debitAccountBranchCode": "015",
    "amount": "10000",
    "debitAccountCategory": "BUSINESS"
}
```

### Response

| Field | Value |
|-------|-------|
| Response Time | 25482.32ms |
| Status | FAILED ✗ |
| Error | Unexpected response returned from BOT Service, Status Code: 400 |

### Full Response Data

```json
{
    "success": false,
    "error": "Unexpected response returned from BOT Service, Status Code: 400",
    "account_number": "1234567890123",
    "bank_code": "NMIBTZT0"
}
```

---

## Test Summary

- **Test Completed:** 2025-09-03 09:27:13
- **Tests Run:** 2
- **Environment:** local

## Known Issues

1. **NBC UAT Environment Instability**: The BOT API Gateway intermittently returns authentication errors
2. **Account Balance Retrieval**: Some test accounts have CBS-level issues
3. **Timeout Issues**: API responses sometimes exceed 30-second timeout

## Recommendations

1. Use account `015103001490` for testing (confirmed working)
2. Implement retry logic for transient failures
3. Consider moving to production environment for stable testing
