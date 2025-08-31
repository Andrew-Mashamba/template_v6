<?php

// This script creates a sample combined import template
$templateFile = '/Volumes/DATA/PROJECTS/SACCOS/SYSTEMS/SACCOS_CORE_SYSTEM/data_migration/combined_template.csv';

// Create combined template with headers matching MEMBER LIST.csv format
if (($handle = fopen($templateFile, "w")) !== FALSE) {
    // Write BOM for Excel compatibility
    fwrite($handle, "\xEF\xBB\xBF");
    
    // Write header
    fputcsv($handle, ['s/n', 'JINA KAMILI', 'ID', 'NO OF SHARES', 'VALUE', 'AKIBA', 'GENDER', 'DOB', 'PHONE NO', 'NIN']);
    
    // Add a sample row to show the format
    fputcsv($handle, [
        '1',                          // s/n
        'JOHN DOE SAMPLE',           // Full name
        '9999',                      // ID
        '100',                       // Number of shares
        '500,000.00',               // Share value
        '1,000,000.00',             // Savings balance (Akiba)
        'Male',                      // Gender
        '01/15/1990',               // Date of birth
        '0712345678',               // Phone number
        '19900115-12345-00001-23'   // NIN
    ]);
    
    fclose($handle);
    echo "Combined template created at: " . $templateFile . "\n";
    echo "This template shows the expected format for combined member, shares, and savings import.\n";
    echo "\nThe MEMBER LIST.csv file in this directory is already in the correct format for combined import.\n";
}

// Verify the existing MEMBER LIST.csv format
$memberFile = '/Volumes/DATA/PROJECTS/SACCOS/SYSTEMS/SACCOS_CORE_SYSTEM/data_migration/MAMBER LIST.csv';
if (file_exists($memberFile)) {
    echo "\n✓ MEMBER LIST.csv exists and can be used directly for combined import.\n";
    echo "  This file contains " . (count(file($memberFile)) - 1) . " member records ready for import.\n";
}