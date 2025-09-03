# Detailed Payment Services Test Report

**Date**: 2025-09-02 12:49:53
**Environment**: local

## Configuration

- Base URL: https://22.32.245.67:443
- Client ID: IB
- SACCOS Account: 

## Service Endpoints

### Internal Funds Transfer (IFT)
- Account Verify: `/api/nbc/account/verify`
- Transfer: `/api/nbc/ift/transfer`
- Status: `/api/nbc/ift/status`

### External Funds Transfer (EFT)
- TIPS Lookup: `/domestix/api/v2/lookup`
- TIPS Transfer: `/domestix/api/v2/transfer`
- TISS Transfer: `/tiss/api/v2/transfer`

### Mobile Wallet Transfer
- Wallet Lookup: `/domestix/api/v2/lookup`
- Wallet Transfer: `/domestix/api/v2/transfer`

### Bill Payments
- GEPG Inquiry: `/api/nbc-sg/v2/billquery`
- GEPG Payment: `/api/nbc-sg/v2/bill-pay`
- LUKU Lookup: `/api/nbc-luku/v2/lookup`
- LUKU Payment: `/api/nbc-luku/v2/payment`

## Test Results

All services have been tested with full request/response logging.
Check `storage/logs/payments/` for detailed transaction logs.
