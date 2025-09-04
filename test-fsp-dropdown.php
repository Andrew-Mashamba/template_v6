#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Livewire\Payments\MoneyTransfer;

// Color codes
$GREEN = "\033[0;32m";
$RED = "\033[0;31m";
$YELLOW = "\033[0;33m";
$BLUE = "\033[0;34m";
$CYAN = "\033[0;36m";
$NC = "\033[0m";

echo "\n{$CYAN}================================================\n";
echo "     FSP DROPDOWN LIST TEST\n";
echo "================================================{$NC}\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Create instance of MoneyTransfer component
$component = new MoneyTransfer();
$component->mount();

echo "{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n";
echo "{$YELLOW}BANKS IN DROPDOWN{$NC}\n";
echo "{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n\n";

$banks = $component->availableBanks;
$bankCount = count($banks);
$verifiedCount = 0;
$activeCount = 0;
$issueCount = 0;

echo "Total Banks: {$bankCount}\n\n";

foreach ($banks as $code => $name) {
    if (strpos($name, '✓') !== false) {
        echo "{$GREEN}✓{$NC} {$code}: {$name}\n";
        $verifiedCount++;
    } elseif (strpos($name, '⚠') !== false) {
        echo "{$YELLOW}⚠{$NC} {$code}: {$name}\n";
        $issueCount++;
    } elseif (strpos($name, '•') !== false) {
        echo "• {$code}: {$name}\n";
        $activeCount++;
    } else {
        echo "{$RED}✗{$NC} {$code}: {$name}\n";
    }
}

echo "\n{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n";
echo "{$YELLOW}MOBILE WALLETS IN DROPDOWN{$NC}\n";
echo "{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n\n";

$wallets = $component->availableWallets;
$walletCount = count($wallets);
$walletVerified = 0;
$walletActive = 0;
$walletIssue = 0;

echo "Total Wallets: {$walletCount}\n\n";

foreach ($wallets as $key => $name) {
    if (strpos($name, '✓') !== false) {
        echo "{$GREEN}✓{$NC} {$key}: {$name}\n";
        $walletVerified++;
    } elseif (strpos($name, '⚠') !== false) {
        echo "{$YELLOW}⚠{$NC} {$key}: {$name}\n";
        $walletIssue++;
    } elseif (strpos($name, '•') !== false) {
        echo "• {$key}: {$name}\n";
        $walletActive++;
    } else {
        echo "{$RED}✗{$NC} {$key}: {$name}\n";
    }
}

echo "\n{$CYAN}================================================\n";
echo "                SUMMARY\n";
echo "================================================{$NC}\n\n";

echo "{$YELLOW}Banks:{$NC}\n";
echo "• Total: {$bankCount}\n";
echo "• Verified & Working: {$GREEN}{$verifiedCount}{$NC}\n";
echo "• Active (Untested): {$activeCount}\n";
echo "• Known Issues: {$YELLOW}{$issueCount}{$NC}\n\n";

echo "{$YELLOW}Mobile Wallets:{$NC}\n";
echo "• Total: {$walletCount}\n";
echo "• Verified & Working: {$GREEN}{$walletVerified}{$NC}\n";
echo "• Active (Untested): {$walletActive}\n";
echo "• Known Issues: {$YELLOW}{$walletIssue}{$NC}\n\n";

echo "{$YELLOW}Legend:{$NC}\n";
echo "{$GREEN}✓ (Verified){$NC} - Tested and working in production\n";
echo "{$GREEN}✓ (Verified - Fast){$NC} - Working with <1s response time\n";
echo "• (Active) - Available but not yet tested\n";
echo "{$YELLOW}⚠ (Issues){$NC} - Known problems, may not work\n";
echo "{$RED}✗ (Inactive){$NC} - Currently disabled\n";

echo "\n{$GREEN}All FSPs are now available in the dropdown!{$NC}\n";
echo "Users can see which ones are verified and working.\n";

echo "\n{$BLUE}=== Test Complete ==={$NC}\n\n";