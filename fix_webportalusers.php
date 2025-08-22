<?php

// Read the WebportalusersSeeder file
$filePath = 'database/seeders/WebportalusersSeeder.php';
$content = file_get_contents($filePath);

// Fix timestamp fields
$timestampFields = [
    'last_failed_attempt', 'password_reset_expires_at', 'password_changed_at',
    'session_expires_at', 'two_factor_confirmed_at', 'portal_registered_at',
    'last_activity_at'
];

foreach ($timestampFields as $field) {
    $content = preg_replace("/'$field' => 'Sample {$field} \d+',/", "'$field' => null,", $content);
}

// Fix IP address field
$content = preg_replace("/'last_login_ip' => 'Sample last_login_ip \d+',/", "'last_login_ip' => '192.168.1.1',", $content);

// Fix phone numbers
$content = str_replace("'phone' => +255700000001,", "'phone' => '255700000001',", $content);
$content = str_replace("'phone' => +255700000002,", "'phone' => '255700000002',", $content);

// Fix integer for updated_by field
$content = preg_replace("/'updated_by' => '\d{4}-\d{2}-\d{2}',/", "'updated_by' => 1,", $content);

// Fix timezone
$content = preg_replace("/'timezone' => '\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}',/", "'timezone' => 'Africa/Dar_es_Salaam',", $content);

// Fix boolean fields that have integer values
$content = str_replace("'two_factor_enabled' => 1,", "'two_factor_enabled' => true,", $content);
$content = str_replace("'two_factor_enabled' => 2,", "'two_factor_enabled' => true,", $content);
$content = str_replace("'force_password_change' => 1,", "'force_password_change' => true,", $content);
$content = str_replace("'force_password_change' => 2,", "'force_password_change' => true,", $content);

// Fix session ID fields
$content = str_replace("'current_session_id' => 1,", "'current_session_id' => 'session1',", $content);
$content = str_replace("'current_session_id' => 2,", "'current_session_id' => 'session2',", $content);

// Fix closing braces
if (substr_count($content, '{') > substr_count($content, '}')) {
    $content = preg_replace(
        '/(\s*foreach \(\$data as \$row\) \{\s*DB::table\(\'web_portal_users\'\)->insert\(\$row\);\s*)\}\s*\}\s*\}$/s',
        '$1}
    }
}',
        $content
    );
}

// Write the fixed content back
file_put_contents($filePath, $content);

echo "Fixed WebportalusersSeeder.php\n";