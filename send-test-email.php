<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

try {
    // Create transport without encryption
    $transport = new EsmtpTransport('smtp.absa.co.za', 25);
    // Don't set username/password for unauthenticated SMTP
    
    // Disable STARTTLS by setting the transport to not use it
    $stream = $transport->getStream();
    if (method_exists($stream, 'setStreamOptions')) {
        $stream->setStreamOptions([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ]
        ]);
    }
    
    $mailer = new Mailer($transport);
    
    $email = (new Email())
        ->from('nbc_saccos@nbc.co.tz')
        ->to('andrew.s.mashamba@gmail.com')
        ->subject('NBC SACCOS - Test Email')
        ->text('This is a test email from NBC SACCOS system.')
        ->html('<p>This is a test email from NBC SACCOS system.</p>');
    
    $mailer->send($email);
    
    echo "Email sent successfully using Symfony Mailer!\n";
    
} catch (Exception $e) {
    echo "Failed to send email: " . $e->getMessage() . "\n";
}