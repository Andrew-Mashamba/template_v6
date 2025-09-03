#!/bin/bash

# Script to show emails in Laravel log (when using log mail driver)

echo "==========================================="
echo "        Email Log Viewer for SACCOS        "
echo "==========================================="
echo ""

LOG_FILE="/var/www/html/template/storage/logs/laravel-$(date +%Y-%m-%d).log"

if [ ! -f "$LOG_FILE" ]; then
    echo "Log file not found: $LOG_FILE"
    exit 1
fi

echo "Recent emails from log:"
echo "-----------------------"
grep -A20 "local.DEBUG.*From:" "$LOG_FILE" | tail -100

echo ""
echo "==========================================="
echo "        OTP Codes (if any failed)          "
echo "==========================================="
echo ""

OTP_LOG="/var/www/html/template/storage/logs/otp-$(date +%Y-%m-%d).log"

if [ -f "$OTP_LOG" ]; then
    echo "Recent OTP failures with codes:"
    echo "-------------------------------"
    grep "OTP delivery failed" "$OTP_LOG" | tail -5 | while read line; do
        echo "$line" | grep -oP '"user_email":"[^"]+"|"otp_code":"[^"]+"' | tr '\n' ' '
        echo ""
    done
else
    echo "No OTP log found"
fi

echo ""
echo "To view active OTP codes, run: php artisan otp:show"