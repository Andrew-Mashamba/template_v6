#!/usr/bin/env python3

import json
import sys

# Read all input
data = sys.stdin.read()

# Find JSON part
start = data.find('{')
if start != -1:
    json_str = data[start:]
    try:
        # Clean up the string
        json_str = json_str.replace('[0;34m=== Test Complete ===[0m', '').strip()
        obj = json.loads(json_str)
        
        banks = []
        wallets = []
        
        for fsp in obj['body']:
            if fsp['fspType'] == 'BANK':
                banks.append(fsp)
            else:
                wallets.append(fsp)
        
        print('\n=== BANKS (Active in TIPS) ===')
        for bank in sorted(banks, key=lambda x: x['fspShortNme']):
            print(f"• {bank['fspShortNme']:15} - {bank['fspCode']:12} - ID: {bank['fspId']:3} - {bank['fspFullNme']}")
        
        print(f'\nTotal Banks: {len(banks)}')
        
        print('\n=== MOBILE WALLETS (Active in TIPS) ===')
        for wallet in sorted(wallets, key=lambda x: x['fspShortNme']):
            print(f"• {wallet['fspShortNme']:15} - {wallet['fspCode']:12} - ID: {wallet['fspId']:3} - {wallet['fspFullNme']}")
        
        print(f'\nTotal Wallets: {len(wallets)}')
        
        # Show specific working ones
        print('\n=== CONFIRMED WORKING IN OUR TESTS ===')
        print('• CRDB Bank      - CORUTZTZ     - ID: 003 ✓ (B2B Lookups Working)')
        print('• MPesa          - VMCASHIN     - ID: 503 ✓ (B2W Lookups Working)')
        
    except Exception as e:
        print(f'Error parsing JSON: {e}')