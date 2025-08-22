# Auto Loan Disbursement API - Complete Request Examples

## 📋 API Endpoint Information

**Base URL**: `http://localhost:8000` (or your server URL)  
**Endpoint**: `/api/v1/loans/auto-disburse`  
**Method**: `POST`  
**Authentication**: API Key required  
**Content-Type**: `application/json`

## 🔐 Authentication Requirements

The API requires the following headers:
- `X-API-Key`: Your API key for authentication
- `Content-Type`: `application/json`
- `Accept`: `application/json`

## 📝 Request Parameters

### **Required Parameters**
| Parameter | Type | Description | Validation |
|-----------|------|-------------|------------|
| `client_number` | string | Client number from the system | Must exist in clients table |
| `amount` | numeric | Loan amount in TZS | Min: 100,000, Max: 100,000,000 |

## 🚀 Complete HTTP POST Request Examples

### **1. cURL Example**

```bash
curl -X POST http://localhost:8000/api/v1/loans/auto-disburse \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "X-API-Key: YOUR_API_KEY_HERE" \
  -d '{
    "client_number": "1001",
    "amount": 1000000
  }'
```

### **2. JavaScript (Fetch) Example**

```javascript
const response = await fetch('http://localhost:8000/api/v1/loans/auto-disburse', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-API-Key': 'YOUR_API_KEY_HERE'
  },
  body: JSON.stringify({
    client_number: '1001',
    amount: 1000000
  })
});

const result = await response.json();
console.log(result);
```

### **3. Python (Requests) Example**

```python
import requests
import json

url = 'http://localhost:8000/api/v1/loans/auto-disburse'
headers = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-API-Key': 'YOUR_API_KEY_HERE'
}
data = {
    'client_number': '1001',
    'amount': 1000000
}

response = requests.post(url, headers=headers, json=data)
result = response.json()
print(json.dumps(result, indent=2))
```

### **4. PHP (cURL) Example**

```php
<?php
$url = 'http://localhost:8000/api/v1/loans/auto-disburse';
$data = [
    'client_number' => '1001',
    'amount' => 1000000
];

$headers = [
    'Content-Type: application/json',
    'Accept: application/json',
    'X-API-Key: YOUR_API_KEY_HERE'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);
print_r($result);
?>
```

### **5. Postman Collection**

```json
{
  "info": {
    "name": "Auto Loan Disbursement API",
    "description": "API for automatic loan creation and disbursement"
  },
  "item": [
    {
      "name": "Auto Disburse Loan",
      "request": {
        "method": "POST",
        "header": [
          {
            "key": "Content-Type",
            "value": "application/json"
          },
          {
            "key": "Accept",
            "value": "application/json"
          },
          {
            "key": "X-API-Key",
            "value": "YOUR_API_KEY_HERE"
          }
        ],
        "body": {
          "mode": "raw",
          "raw": "{\n  \"client_number\": \"1001\",\n  \"amount\": 1000000\n}"
        },
        "url": {
          "raw": "http://localhost:8000/api/v1/loans/auto-disburse",
          "protocol": "http",
          "host": ["localhost"],
          "port": "8000",
          "path": ["api", "v1", "loans", "auto-disburse"]
        }
      }
    }
  ]
}
```

## 📊 Expected Response Format

### **Success Response (200 OK)**

```json
{
  "success": true,
  "message": "Loan created and disbursed successfully",
  "data": {
    "transaction_id": "AUTO_DISB_64f8a1b2c3d4e",
    "loan_id": "AUTO2025000001",
    "loan_account": "LN1001",
    "client": {
      "number": "1001",
      "name": "John Doe",
      "nbc_account": "NBC1001"
    },
    "loan_details": {
      "amount": 1000000,
      "tenure_months": 12,
      "interest_rate": "24.00%",
      "monthly_installment": 94583.33,
      "total_payable": 1135000
    },
    "disbursement": {
      "gross_amount": 1000000,
      "deductions": {
        "total": 50000,
        "charges": 30000,
        "insurance": 15000,
        "first_interest": 5000,
        "breakdown": [
          {
            "type": "charge",
            "name": "Management Fee",
            "amount": 30000
          },
          {
            "type": "insurance",
            "name": "Loan Insurance",
            "amount": 15000
          },
          {
            "type": "first_interest",
            "name": "First Month Interest",
            "amount": 5000
          }
        ]
      },
      "net_disbursed": 950000,
      "payment_method": "NBC_ACCOUNT",
      "payment_reference": "NBC_AUTO_64f8a1b2c3d4e",
      "disbursement_date": "2025-08-20T14:30:00.000Z"
    },
    "repayment": {
      "first_payment_date": "2025-09-01",
      "control_numbers": [
        {
          "type": "REPAYMENT",
          "number": "AUTO17347206001234",
          "description": "Monthly Loan Repayment",
          "valid_until": "2025-09-20"
        }
      ],
      "frequency": "MONTHLY"
    }
  },
  "meta": {
    "execution_time_ms": 1250.45,
    "timestamp": "2025-08-20T14:30:00.000Z"
  }
}
```

### **Error Response (422 Validation Error)**

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "client_number": [
      "Client not found in the system"
    ],
    "amount": [
      "Minimum loan amount is TZS 100,000"
    ]
  },
  "meta": {
    "timestamp": "2025-08-20T14:30:00.000Z"
  }
}
```

### **Error Response (500 Server Error)**

```json
{
  "success": false,
  "message": "Client 1001 does not have an NBC account number",
  "error": {
    "code": "AUTO_DISBURSEMENT_ERROR"
  },
  "meta": {
    "timestamp": "2025-08-20T14:30:00.000Z"
  }
}
```

## 🔍 Request Validation Rules

### **client_number**
- **Required**: Yes
- **Type**: String
- **Validation**: Must exist in the `clients` table
- **Error Message**: "Client not found in the system"

### **amount**
- **Required**: Yes
- **Type**: Numeric
- **Minimum**: 100,000 TZS
- **Maximum**: 100,000,000 TZS
- **Error Messages**:
  - "Minimum loan amount is TZS 100,000"
  - "Maximum loan amount is TZS 100,000,000"

## 📋 Complete Request Headers

```http
POST /api/v1/loans/auto-disburse HTTP/1.1
Host: localhost:8000
Content-Type: application/json
Accept: application/json
X-API-Key: YOUR_API_KEY_HERE
User-Agent: Your-Application/1.0
Content-Length: 45

{
  "client_number": "1001",
  "amount": 1000000
}
```

## 🔧 Testing Examples

### **Test Case 1: Valid Request**
```json
{
  "client_number": "1001",
  "amount": 1000000
}
```

### **Test Case 2: Minimum Amount**
```json
{
  "client_number": "1001",
  "amount": 100000
}
```

### **Test Case 3: Maximum Amount**
```json
{
  "client_number": "1001",
  "amount": 100000000
}
```

### **Test Case 4: Invalid Client (Error)**
```json
{
  "client_number": "9999",
  "amount": 1000000
}
```

### **Test Case 5: Invalid Amount (Error)**
```json
{
  "client_number": "1001",
  "amount": 50000
}
```

## 📈 Enhanced Logging

When you make a request to this API, the enhanced `AutoLoanDisbursementService` will generate comprehensive logs in:
```
storage/logs/laravel-{YYYY-MM-DD}.log
```

The logs will include:
- 🚀 Process start and transaction ID
- ✅ Step-by-step progress tracking
- 💰 Detailed financial calculations
- 💳 Account balance changes
- 🎉 Complete financial breakdown
- 💥 Any errors with full context

## 🔐 Security Notes

1. **API Key**: Always use a valid API key in the `X-API-Key` header
2. **IP Whitelisting**: Your IP must be whitelisted in the system
3. **HTTPS**: Use HTTPS in production environments
4. **Rate Limiting**: Be aware of any rate limiting policies

## 📞 Support

For API support or to obtain an API key, contact your system administrator.

---

**Last Updated**: August 20, 2025  
**API Version**: 1.0  
**Service**: AutoLoanDisbursementService
