<?php

// Read the TemppermissionsSeeder file
$filePath = 'database/seeders/TemppermissionsSeeder.php';
$content = file_get_contents($filePath);

// Replace all string values with integer values for t1-t200 columns
for ($i = 1; $i <= 200; $i++) {
    // Replace 'Sample tX Y' with just an integer
    $content = preg_replace("/'t{$i}' => 'Sample t{$i} \\d+'/", "'t{$i}' => 1", $content);
}

// Write the fixed content back
file_put_contents($filePath, $content);

echo "Fixed TemppermissionsSeeder.php\n";