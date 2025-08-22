<?php

// Read the TransactionsSeeder file
$filePath = 'database/seeders/TransactionsSeeder.php';
$content = file_get_contents($filePath);

// Fix boolean fields that have integer values
$booleanFields = [
    'lookup_validated',
    'lookup_validation_status',
    'lookup_validation_notes'
];

// For fields that should be boolean
$content = str_replace("'lookup_validated' => 1,", "'lookup_validated' => true,", $content);
$content = str_replace("'lookup_validated' => 2,", "'lookup_validated' => true,", $content);

// For fields that should be strings or other types
$content = str_replace("'lookup_validation_status' => 1,", "'lookup_validation_status' => 'validated',", $content);
$content = str_replace("'lookup_validation_status' => 2,", "'lookup_validation_status' => 'validated',", $content);

$content = str_replace("'lookup_validation_notes' => 1,", "'lookup_validation_notes' => 'Sample validation notes',", $content);
$content = str_replace("'lookup_validation_notes' => 2,", "'lookup_validation_notes' => 'Sample validation notes',", $content);

// Write the fixed content back
file_put_contents($filePath, $content);

echo "Fixed boolean fields in TransactionsSeeder.php\n";