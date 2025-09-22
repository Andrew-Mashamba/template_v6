# NBC Partners Values Added Services (PVAS)
## Customer Statement API Integration Guide

**Version**: v1.2  
**Classification**: Confidential  
**Owner**: NBC Development Team  
**Contact**: InhouseDevelopments@nbc.co.tz  

---

## Document Information

### Description
This document describes the integration process with NBC Customer Onboarding Platform Gateway for statement services. All APIs in this document are RESTful, and all requests and responses to/from this service are in JSON format.

### Purpose
This document acts as an integration guide for NBC and Partner Channels in meeting the intended objectives. It can be consumed by Developers and Software Analysts.

### Version History

| Version | Date | Description |
|---------|------|-------------|
| v1.0 | February 22, 2025 | Initial Draft |
| v1.1 | March 4, 2025 | Revision 1 |
| v1.2 | Current | Latest Version |

---

## Key Integration Points

### 1. Environment Configuration
- NBC will share Base Endpoints for UAT and PROD
- All requests will be through HTTPS over Secure VPN
- IP whitelisting might be put in place

### 2. Authentication
- **Method**: Bearer Authentication (JWT/OAuth2)
- Channel ID (Consumer API key) and Secret will be configured and securely shared
- PROD credentials will be shared after successfully completing SIT and UAT Sign-off

### 3. Security Requirements
- All requests must be signed with private keys and verified with public key
- Digital signature using SHA256withRSA algorithm
- Base64 encoded signature in header parameter

### 4. Reference Management
- Unique Customer reference number generated for each new request
- Unique Customer/Savings identifier maintained across all requests
- Should be used by technical teams for case escalation to Bank

---

## API Endpoints

### Authentication Endpoint

#### Login
Obtain JWT token for API access.

**HTTP Method**: POST  
**Endpoint**: `/api/auth/login`

**Request Body**:
```json
{
    "username": "<USER-ACCOUNT>",
    "password": "<USER-ACCOUNT-SECRET>"
}
```

**Success Response**:
```json
{
    "token": "eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJTVFJJTkciLCJpYXQiOjE3NDEwOTQzNzQsImV4cCI6MTc0MTA5NTI3NH0.NJG5wpjnnWxNXeuBy_250L_Eo0G92n1c1z42cdjAqx0",
    "expiry": 86400000
}
```

---

### Process 1: Account Position Balance

#### SC990001 - Get Account Balance

**HTTP Method**: POST  
**NBC Endpoint**: `/api/v1/casa/balance`

**Request Body**:
```json
{
    "timestamp": "2025-03-04T12:17:12.249Z",
    "serviceCode": "SC990001",
    "partnerRef": "2501201264890",
    "accountNumber": "012102000376",
    "statementDate": "2025-03-03"
}
```

**Response Body**:
```json
{
    "statusCode": 600,
    "message": "Successful",
    "serviceCode": "SC990001",
    "partnerRef": "CB2501201264890",
    "bankRef": "PVS25030514081302128",
    "timestamp": "2025-03-05T11:08:13.927+00:00",
    "data": {
        "currency": "TZS",
        "openingBalance": 252493350003.31,
        "closingBalance": 252491972089.07,
        "totalTransactionsCount": 116,
        "totalDebitAmount": 1451245.74,
        "totalDebitCount": 108,
        "totalCreditAmount": 73331.50,
        "totalCreditCount": 8
    }
}
```

---

### Process 2: Account Transactional Summary

#### SC990002 - Get Transaction Summary

**HTTP Method**: POST  
**NBC Endpoint**: `/api/v1/casa/summary`

**Request Body**:
```json
{
    "timestamp": "2025-03-04T12:17:12.249Z",
    "serviceCode": "SC990002",
    "partnerRef": "CB2501201264890",
    "accountNumber": "047103001630",
    "statementDate": "2023-01-02"
}
```

**Success Response**:
```json
{
    "statusCode": 600,
    "message": "Successful",
    "serviceCode": "SC990002",
    "partnerRef": "CB2501201264890",
    "bankRef": "PVS25030513585983026",
    "timestamp": "2025-03-05T10:59:02.034+00:00",
    "data": [
        {
            "accountNumber": "047103001630",
            "balancesInfo": {
                "item": {
                    "accountId": "047103001630",
                    "accountTitle": "Salum Suma Grayson",
                    "acyAmount": "0.00",
                    "balanceBook": "0.00",
                    "billAmount": "0.00",
                    "branchCode": "47",
                    "branchName": "SEA CLIFF",
                    "classification": "NORMAL",
                    "currencyCode": "100",
                    "currencyShortName": "TZS",
                    "currentStatus": "10",
                    "currentStatusDescription": "BLK DORMANT",
                    "customerRelationship": "SOW",
                    "depositNo": "0",
                    "emiAmount": "0.00",
                    "futureDatedAmount": "0.00",
                    "hasChequeBookFacility": "true",
                    "interestRate": "13.0",
                    "lcyAmount": "0.00",
                    "moduleCode": "C",
                    "netBanking": "true",
                    "originalBalance": "0.00",
                    "originalDepositNo": "0",
                    "overdraftLimit": "0.0",
                    "productCode": "103",
                    "productName": "BUSINESS CURRENT ACCOUNT",
                    "reason": "UNBLOCKED",
                    "safeDepositBoxId": "0",
                    "totalAcyAmount": "0.00",
                    "totalLcyAmount": "0.00",
                    "unclearFunds": "0.00"
                }
            },
            "transactionsHistory": {
                "accountCurrency": "TZS",
                "accountNo": "047103001630",
                "accountStatus": "BLK_DORMANT",
                "accountTitle": "Salum Suma Grayson",
                "amountLastCr": "0",
                "amountLastDr": "0",
                "amtCrMtd": "0",
                "amtCrTod": "0",
                "amtCrYtd": "0",
                "amtDrMtd": "0",
                "amtDrTod": "0",
                "amtDrYtd": "0",
                "amtTotCr": "0",
                "amtTotDr": "0",
                "amtYtdIntPaid": "0",
                "amtYtdIntRecvd": "0",
                "balAcctMinReqd": "15000",
                "balAvail": "0",
                "balLastStmt": "0",
                "codLang": "ENG",
                "ctNumWithdraw": "32767",
                "datLastCr": "2022-04-05 00:00:00",
                "datLastDr": "2022-04-05 00:00:00",
                "datLastMnt": "2023-12-04 20:34:12",
                "datLastStmt": "2022-09-09 00:00:00",
                "flgAcctClose": "N",
                "flgDormSc": "Y",
                "flgJointAcct": "N",
                "flgMemo": "N"
            }
        }
    ]
}
```

---

### Process 3: Account Statement

#### SC990003 - Get Account Statement

**HTTP Method**: POST  
**NBC Endpoint**: `/api/v1/casa/statement`

**Request Body**:
```json
{
    "timestamp": "2025-03-04T12:17:12.249Z",
    "serviceCode": "SC990003",
    "partnerRef": "CB2501201264890",
    "accountNumber": "012102000376",
    "statementDate": "2025-03-03"
}
```

**Success Response**:
```json
{
    "statusCode": 600,
    "message": "Successful",
    "serviceCode": "SC990003",
    "partnerRef": "CB2501201264890",
    "bankRef": "PVS25030415565672233",
    "timestamp": "2025-03-04T12:56:58.122+00:00",
    "data": {
        "transactions": [
            {
                "transactionDate": "2025-02-10T00:00:00",
                "postingDate": "2025-02-10T00:00:00",
                "valueDate": "2023-01-02T00:00:00",
                "currency": "TZS",
                "amount": 910,
                "balance": 4170415.84,
                "reference": "99520102000100948495",
                "description": "SC|250210141523713748|20250210|102|NULL|",
                "debitCredit": "D",
                "debitAmount": 910,
                "creditAmount": 0
            },
            {
                "transactionDate": "2025-02-10T00:00:00",
                "postingDate": "2025-02-10T00:00:00",
                "valueDate": "2023-01-02T00:00:00",
                "currency": "TZS",
                "amount": 1000,
                "balance": 4171325.84,
                "reference": "99520102000100948494",
                "description": "SC|250210141338866751|20250210|160|NULL|",
                "debitCredit": "D",
                "debitAmount": 1000,
                "creditAmount": 0
            }
        ]
    }
}
```

---

## Response Codes

| Code | Description |
|------|-------------|
| 600 | Success |
| 601 | Failed |
| 602 | Digital Signature Verification Failure |
| 613 | Unauthorized service access request |
| 615 | Authentication Failed |
| 699 | Exception caught |

---

## Request Headers

### Sample HTTP Request with Headers

```http
POST /api/v1/casa/summary
X-Signature: eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJTVFJJTkciLCJpYXQiOjE3MzEzMTA2MDQsImV4cCI6MTczMTMxMTUwNH0.l4wUW8Y3WW5jnMWg3K3tUONhebkrbfFGJCE58n4nK1U
Authorization: Bearer eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJTVFJJTkciLCJpYXQiOjE3NDExNzIxNjYsImV4cCI6MTc0MTE3MzA2Nn0.AywwVK3xgP3at54dzABEw_lFOhGHcBiN5DJyz8RKK7U
Content-Type: application/json
Accept: application/json
Connection: Keep-Alive
Accept-Encoding: br,deflate,gzip,x-gzip
```

### Required Headers

| Header | Description | Example |
|--------|-------------|---------|
| Authorization | Bearer token from authentication | Bearer eyJhbGci... |
| X-Signature | Digital signature of request body | eyJhbGciOiJ... |
| Content-Type | Request content type | application/json |
| Accept | Response content type | application/json |

---

## Digital Signature Process

### Signature Generation Steps

1. **Prepare the payload**: Convert entire request body to JSON string
2. **Generate signature**: Use SHA256withRSA algorithm with your private key
3. **Encode signature**: Convert signature to Base64 format
4. **Add to header**: Include as `X-Signature` header parameter

### Signature Verification
- NBC will use your public key to verify the request before processing
- Public key must be PEM base64 encoded format
- Verification failure will result in response code 602

---

## Integration Checklist

### NBC Tasks

| No | Task | Status |
|----|------|--------|
| 1 | Share API Documentation | Required |
| 2 | Share API Base Endpoints (IP and Port) for UAT | Required |
| 3 | Share Basic Auth credentials | Required |
| 4 | Share NBC Public signed certificate (PEM base64 encoded) | Required |
| 5 | Share API Base Endpoints for PROD (after UAT Sign-Off) | Required |

### Channel/Partner Tasks

| No | Task | Status |
|----|------|--------|
| 1 | Share your Public signed certificate (PEM base64 encoded) | Required |
| 2 | Share callback URLs | Required |

---

## Field Descriptions

### Common Request Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| timestamp | ISO 8601 DateTime | Yes | Request timestamp |
| serviceCode | String | Yes | Service identifier (SC990001, SC990002, SC990003) |
| partnerRef | String | Yes | Partner's unique reference |
| accountNumber | String | Yes | NBC account number |
| statementDate | Date (YYYY-MM-DD) | Yes | Statement date |

### Balance Response Fields

| Field | Type | Description |
|-------|------|-------------|
| currency | String | Account currency (e.g., TZS) |
| openingBalance | Decimal | Opening balance for the period |
| closingBalance | Decimal | Closing balance for the period |
| totalTransactionsCount | Integer | Total number of transactions |
| totalDebitAmount | Decimal | Sum of all debit transactions |
| totalDebitCount | Integer | Number of debit transactions |
| totalCreditAmount | Decimal | Sum of all credit transactions |
| totalCreditCount | Integer | Number of credit transactions |

### Transaction Fields

| Field | Type | Description |
|-------|------|-------------|
| transactionDate | DateTime | Date of transaction |
| postingDate | DateTime | Date posted to account |
| valueDate | DateTime | Value date of transaction |
| currency | String | Transaction currency |
| amount | Decimal | Transaction amount |
| balance | Decimal | Balance after transaction |
| reference | String | Transaction reference |
| description | String | Transaction description |
| debitCredit | String | D for Debit, C for Credit |
| debitAmount | Decimal | Debit amount (0 if credit) |
| creditAmount | Decimal | Credit amount (0 if debit) |

---

## Error Handling

### Common Error Scenarios

1. **Authentication Failure (615)**
   - Invalid credentials
   - Expired token
   - Missing Authorization header

2. **Signature Verification Failure (602)**
   - Invalid signature format
   - Signature doesn't match payload
   - Wrong public key

3. **Unauthorized Access (613)**
   - Service not enabled for channel
   - Invalid service code
   - IP not whitelisted

4. **General Failure (601)**
   - Invalid account number
   - Account not found
   - Date range errors

### Error Response Format

```json
{
    "statusCode": 601,
    "message": "Account not found",
    "serviceCode": "SC990001",
    "partnerRef": "CB2501201264890",
    "bankRef": null,
    "timestamp": "2025-03-04T12:56:58.122+00:00",
    "data": null
}
```

---

## Best Practices

### 1. Request Management
- Always include unique partnerRef for tracking
- Use ISO 8601 format for timestamps
- Validate account numbers before sending

### 2. Security
- Rotate JWT tokens before expiry
- Keep private keys secure
- Use secure VPN connections only

### 3. Error Handling
- Implement retry logic with exponential backoff
- Log all requests and responses
- Handle timeout scenarios (30 seconds recommended)

### 4. Performance
- Cache JWT tokens until expiry
- Implement connection pooling
- Use compression for large responses

---

## Testing Guidelines

### UAT Environment
1. Test all service codes sequentially
2. Verify signature generation and verification
3. Test error scenarios
4. Validate response data accuracy
5. Test with various date ranges

### Production Readiness
- Complete UAT sign-off
- Obtain production credentials
- Update endpoint configurations
- Implement monitoring and alerting
- Document escalation procedures

---

## Support and Escalation

### Technical Support
**Email**: InhouseDevelopments@nbc.co.tz  
**Include in escalation**:
- Partner reference number
- Bank reference number (if available)
- Service code
- Timestamp of request
- Error details

### Business Hours
Monday - Friday: 8:00 AM - 5:00 PM EAT  
Saturday: 9:00 AM - 1:00 PM EAT

---

## Appendix

### A. Sample Code - Signature Generation (PHP)

```php
function generateSignature($payload, $privateKeyPath) {
    $jsonPayload = json_encode($payload);
    $privateKey = openssl_pkey_get_private(file_get_contents($privateKeyPath));
    
    openssl_sign($jsonPayload, $signature, $privateKey, OPENSSL_ALGO_SHA256);
    
    return base64_encode($signature);
}
```

### B. Sample Code - JWT Token Management (PHP)

```php
class TokenManager {
    private $token;
    private $expiry;
    
    public function getToken() {
        if ($this->isTokenValid()) {
            return $this->token;
        }
        return $this->refreshToken();
    }
    
    private function isTokenValid() {
        return $this->token && time() < $this->expiry;
    }
    
    private function refreshToken() {
        // Call authentication endpoint
        // Store new token and expiry
        return $this->token;
    }
}
```

---

*End of Document*