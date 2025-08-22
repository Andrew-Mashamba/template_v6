<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Checking accounts table...\n";
$count = DB::table('accounts')->count();
echo "Total accounts: $count\n";

if ($count > 0) {
    echo "\nFirst 5 accounts:\n";
    $accounts = DB::table('accounts')->take(5)->get();
    foreach ($accounts as $account) {
        echo "ID: {$account->id}, Account Number: {$account->account_number}, Name: {$account->account_name}\n";
    }
} else {
    echo "\nNo accounts found. Running AccountsSeeder...\n";
    Artisan::call('db:seed', ['--class' => 'AccountsSeeder']);
    $count = DB::table('accounts')->count();
    echo "After seeding: $count accounts\n";
}