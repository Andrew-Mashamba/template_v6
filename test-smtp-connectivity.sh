#!/bin/bash

echo "======================================"
echo "SMTP Connectivity Test for SACCOS"
echo "======================================"
echo ""
echo "Test Date: $(date)"
echo "Server: $(hostname -f)"
echo "IP: $(ip addr show | grep "inet " | grep -v "127.0.0.1" | awk '{print $2}' | cut -d/ -f1 | head -1)"
echo ""

echo "1. DNS Resolution Test"
echo "----------------------"
nslookup server354.web-hosting.com | head -10

echo ""
echo "2. Local Firewall Status"
echo "------------------------"
firewall-cmd --list-ports 2>/dev/null || echo "firewall-cmd not available"

echo ""
echo "3. SMTP Port Connectivity Tests"
echo "--------------------------------"

SMTP_SERVERS=(
    "server354.web-hosting.com:465"
    "server354.web-hosting.com:587"
    "server354.web-hosting.com:25"
    "smtp.gmail.com:587"
    "smtp.office365.com:587"
)

for server in "${SMTP_SERVERS[@]}"; do
    host=$(echo $server | cut -d: -f1)
    port=$(echo $server | cut -d: -f2)
    
    echo -n "Testing $host on port $port... "
    
    if timeout 3 bash -c "echo > /dev/tcp/$host/$port" 2>/dev/null; then
        echo "SUCCESS - Port is open"
    else
        echo "FAILED - Connection timeout/refused"
    fi
done

echo ""
echo "4. Current Email Configuration"
echo "-------------------------------"
grep -E "MAIL_HOST|MAIL_PORT|MAIL_MAILER" /var/www/html/template/.env | grep -v PASSWORD

echo ""
echo "5. Route to External Network"
echo "-----------------------------"
ip route | grep default

echo ""
echo "6. Network Interfaces"
echo "---------------------"
ip link show | grep "state UP"

echo ""
echo "======================================"
echo "Recommendation:"
echo "======================================"
echo "If all external SMTP connections fail but DNS resolves correctly,"
echo "this indicates a network-level firewall blocking outbound SMTP."
echo "The firewall exception request should be submitted to the network team."
echo ""
echo "Document location: /var/www/html/template/docs/FIREWALL_EXCEPTION_REQUEST.md"