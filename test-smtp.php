<?php

// Test SMTP connection directly
$smtp_server = 'smtp.absa.co.za';
$smtp_port = 25;
$from = 'nbc_saccos@nbc.co.tz';
$to = 'andrew.s.mashamba@gmail.com';
$subject = 'NBC SACCOS - SMTP Test';
$message = 'This is a test email from NBC SACCOS system.';

echo "Testing SMTP connection to $smtp_server:$smtp_port\n";

try {
    // Open connection
    $connection = fsockopen($smtp_server, $smtp_port, $errno, $errstr, 30);
    
    if (!$connection) {
        echo "Failed to connect: $errstr ($errno)\n";
        exit;
    }
    
    echo "Connected successfully!\n";
    
    // Read server response
    $response = fgets($connection, 515);
    echo "Server: $response";
    
    // Send HELO command
    fputs($connection, "HELO nbc.co.tz\r\n");
    $response = fgets($connection, 515);
    echo "Server: $response";
    
    // Send MAIL FROM
    fputs($connection, "MAIL FROM: <$from>\r\n");
    $response = fgets($connection, 515);
    echo "Server: $response";
    
    // Send RCPT TO
    fputs($connection, "RCPT TO: <$to>\r\n");
    $response = fgets($connection, 515);
    echo "Server: $response";
    
    // Send DATA command
    fputs($connection, "DATA\r\n");
    $response = fgets($connection, 515);
    echo "Server: $response";
    
    // Send the message
    $headers = "From: $from\r\n";
    $headers .= "To: $to\r\n";
    $headers .= "Subject: $subject\r\n";
    $headers .= "Date: " . date("r") . "\r\n";
    $headers .= "\r\n";
    
    fputs($connection, $headers . $message . "\r\n.\r\n");
    $response = fgets($connection, 515);
    echo "Server: $response";
    
    // Quit
    fputs($connection, "QUIT\r\n");
    $response = fgets($connection, 515);
    echo "Server: $response";
    
    fclose($connection);
    
    echo "\nEmail sent successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}