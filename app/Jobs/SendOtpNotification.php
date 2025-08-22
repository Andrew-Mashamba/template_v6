<?php

namespace App\Jobs;

use App\Models\User;
use App\Mail\OTP;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOtpNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $otp;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, string $otp)
    {
        $this->user = $user;
        $this->otp = $otp;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $emailSent = false;
            $smsSent = false;
            
            // Send OTP via email if email is configured
            if (config('mail.mailers.smtp.username') && config('mail.mailers.smtp.password')) {
                try {
                    Mail::to($this->user->email)->send(new OTP(
                        config('app.url'), 
                        $this->user->name ?? $this->user->email, 
                        $this->otp
                    ));
                    $emailSent = true;
                } catch (\Exception $emailError) {
                    Log::channel('otp')->warning('Email sending failed, will try SMS only', [
                        'user_id' => $this->user->id,
                        'email' => $this->user->email,
                        'email_error' => $emailError->getMessage()
                    ]);
                }
            } else {
                Log::channel('otp')->warning('Email configuration missing, skipping email', [
                    'user_id' => $this->user->id,
                    'email' => $this->user->email
                ]);
            }

            // Send OTP via SMS
            if ($this->user->phone_number) {
                try {
                    app(SmsService::class)->send($this->user->phone_number, "Your OTP code is: {$this->otp}. Valid for 5 minutes.", $this->user);
                    $smsSent = true;
                } catch (\Exception $smsError) {
                    Log::channel('otp')->warning('SMS sending failed', [
                        'user_id' => $this->user->id,
                        'phone_number' => $this->user->phone_number,
                        'sms_error' => $smsError->getMessage()
                    ]);
                }
            }

            // If neither email nor SMS was sent successfully, log the OTP for manual retrieval
            if (!$emailSent && !$smsSent) {
                Log::channel('otp')->critical('OTP delivery failed - manual intervention required', [
                    'user_id' => $this->user->id,
                    'user_email' => $this->user->email,
                    'user_phone' => $this->user->phone_number,
                    'otp_code' => $this->otp,
                    'timestamp' => now()->toDateTimeString()
                ]);
                
                // Don't throw exception to prevent infinite retries
                // Instead, mark as completed but log the failure
                return;
            }

            Log::channel('otp')->info('OTP sent successfully', [
                'user_id' => $this->user->id,
                'email' => $this->user->email,
                'phone_number' => $this->user->phone_number,
                'email_sent' => $emailSent,
                'sms_sent' => $smsSent,
                'timestamp' => now()->toDateTimeString()
            ]);
        } catch (\Exception $e) {
            Log::channel('otp')->error('Failed to send OTP notification', [
                'user_id' => $this->user->id,
                'email' => $this->user->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'timestamp' => now()->toDateTimeString()
            ]);

            // If the job fails, throw the exception to trigger retry
            throw $e;
        }
    }

    /**
     * The number of times the job may be attempted.
     */
    public function tries(): int
    {
        return 3;
    }

    /**
     * The number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [30, 60, 120]; // Retry after 30 seconds, then 1 minute, then 2 minutes
    }
} 