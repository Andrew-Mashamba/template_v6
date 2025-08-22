<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\OTP;

class TestEmail extends Command
{
    protected $signature = 'email:test {email}';
    protected $description = 'Test email functionality by sending a test OTP email';

    public function handle()
    {
        $email = $this->argument('email');
        $testOtp = '123456';

        try {
            $this->info('Attempting to send test email to: ' . $email);

            Mail::to($email)->send(new OTP(url('/'), 'Test User', $testOtp));

            $this->info('Test email sent successfully!');
            $this->info('Please check your inbox for the test OTP email.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to send test email:');
            $this->error($e->getMessage());
            $this->error('Stack trace:');
            $this->error($e->getTraceAsString());

            return Command::FAILURE;
        }
    }
}
