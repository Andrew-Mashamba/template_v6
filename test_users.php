<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

echo "Total users: " . User::count() . "\n";
echo "Users with details:\n";

foreach(User::all() as $user) {
    echo "- {$user->name} (ID: {$user->id}, Email: {$user->email}, Dept: " . ($user->department_code ?? 'NULL') . ", Status: " . ($user->status ?? 'NULL') . ")\n";
}

echo "\nAll user IDs: ";
foreach(User::pluck('id') as $id) {
    echo $id . " ";
}
echo "\n"; 