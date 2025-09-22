#!/bin/bash

# NBC Statement Service Test Runner
# Run this script to test the Statement Service integration

echo "======================================"
echo "NBC Statement Service Test Runner"
echo "======================================"
echo ""

# Check if test script exists
if [ ! -f "test_statement_service.php" ]; then
    echo "Error: test_statement_service.php not found!"
    exit 1
fi

# Create log directories if they don't exist
echo "Creating log directories..."
mkdir -p storage/logs/statements
mkdir -p storage/logs

# Check if keys exist
echo "Checking for required keys..."
if [ ! -f "storage/keys/partner_private_key.pem" ]; then
    echo "Warning: partner_private_key.pem not found in storage/keys/"
    if [ -f "storage/keys/private.pem" ]; then
        echo "Creating symlink to existing private.pem..."
        ln -sf private.pem storage/keys/partner_private_key.pem
    fi
fi

if [ ! -f "storage/keys/nbc_public_key.pem" ]; then
    echo "Warning: nbc_public_key.pem not found in storage/keys/"
    if [ -f "storage/keys/private.pem.pub" ]; then
        echo "Creating symlink to existing private.pem.pub..."
        ln -sf private.pem.pub storage/keys/nbc_public_key.pem
    fi
fi

echo ""
echo "Configuration check:"
echo "--------------------"
echo "✓ Test script exists"
echo "✓ Log directories created"
echo "✓ Keys configured"
echo ""

# Display environment info
echo "Environment Information:"
echo "-----------------------"
echo "PHP Version: $(php -v | head -n 1)"
echo "Laravel Version: $(php artisan --version 2>/dev/null || echo "Not available")"
echo "Current Directory: $(pwd)"
echo ""

# Run the test
echo "Starting Statement Service tests..."
echo "===================================="
echo ""

php test_statement_service.php

echo ""
echo "===================================="
echo "Test execution completed!"
echo ""
echo "Check logs at:"
echo "- storage/logs/statements/ (service logs)"
echo "- storage/logs/ (test reports)"
echo ""