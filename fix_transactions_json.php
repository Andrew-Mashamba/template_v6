<?php

// Read the TransactionsSeeder file
$filePath = 'database/seeders/TransactionsSeeder.php';
$content = file_get_contents($filePath);

// Fix JSON fields that should use json_encode
$jsonFields = [
    'lookup_request_payload',
    'lookup_response_payload'
];

foreach ($jsonFields as $field) {
    $content = preg_replace(
        "/'$field' => 'Sample {$field} \d+',/", 
        "'$field' => json_encode(['data' => 'Sample {$field}']),", 
        $content
    );
}

// Write the fixed content back
file_put_contents($filePath, $content);

echo "Fixed JSON fields in TransactionsSeeder.php\n";