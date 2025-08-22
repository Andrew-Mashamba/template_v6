<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Exception;

class SendTransactionNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;
    public $maxExceptions = 3;

    protected $transactionId;
    protected $notificationType; // success, failure, suspect
    protected $notificationData;

    /**
     * Create a new job instance.
     */
    public function __construct($transactionId, $notificationType, $notificationData = [])
    {
        $this->transactionId = $transactionId;
        $this->notificationType = $notificationType;
        $this->notificationData = $notificationData;
        
        // Set queue name
        $this->onQueue('notifications');
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        Log::info('Processing transaction notification', [
            'transactionId' => $this->transactionId,
            'notificationType' => $this->notificationType,
            'attempt' => $this->attempts()
        ]);

        try {
            $transaction = Transaction::findOrFail($this->transactionId);

            switch ($this->notificationType) {
                case 'success':
                    $this->sendSuccessNotification($transaction);
                    break;
                case 'failure':
                    $this->sendFailureNotification($transaction);
                    break;
                case 'suspect':
                    $this->sendSuspectNotification($transaction);
                    break;
                default:
                    throw new Exception("Unknown notification type: {$this->notificationType}");
            }

            // Update notification tracking
            $this->updateNotificationTracking($transaction);

        } catch (Exception $e) {
            Log::error('Transaction notification failed', [
                'transactionId' => $this->transactionId,
                'notificationType' => $this->notificationType,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts()
            ]);

            // If this is the last attempt, send admin alert
            if ($this->attempts() >= $this->tries) {
                $this->sendAdminAlert('Notification delivery failed', [
                    'transactionId' => $this->transactionId,
                    'notificationType' => $this->notificationType,
                    'error' => $e->getMessage()
                ]);
            }

            throw $e;
        }
    }

    /**
     * Send success notification
     */
    protected function sendSuccessNotification($transaction)
    {
        Log::info('Sending success notification', [
            'transactionId' => $transaction->id,
            'referenceNumber' => $transaction->reference
        ]);

        // Send SMS to member
        $this->sendSmsNotification($transaction, 'success');

        // Send email to member
        $this->sendEmailNotification($transaction, 'success');

        // Update transaction metadata
        $transaction->update([
            'metadata' => array_merge($transaction->metadata ?? [], [
                'success_notification_sent_at' => now()->toIso8601String(),
                'notification_attempts' => ($transaction->metadata['notification_attempts'] ?? 0) + 1
            ])
        ]);
    }

    /**
     * Send failure notification
     */
    protected function sendFailureNotification($transaction)
    {
        Log::info('Sending failure notification', [
            'transactionId' => $transaction->id,
            'errorCode' => $transaction->error_code
        ]);

        // Send SMS to member
        $this->sendSmsNotification($transaction, 'failure');

        // Send email to member
        $this->sendEmailNotification($transaction, 'failure');

        // Send admin alert for critical failures
        if ($this->isCriticalFailure($transaction)) {
            $this->sendAdminAlert('Critical transaction failure', [
                'transactionId' => $transaction->id,
                'errorCode' => $transaction->error_code,
                'errorMessage' => $transaction->error_message,
                'amount' => $transaction->amount,
                'serviceType' => $transaction->external_system
            ]);
        }

        // Update transaction metadata
        $transaction->update([
            'metadata' => array_merge($transaction->metadata ?? [], [
                'failure_notification_sent_at' => now()->toIso8601String(),
                'notification_attempts' => ($transaction->metadata['notification_attempts'] ?? 0) + 1
            ])
        ]);
    }

    /**
     * Send suspect notification
     */
    protected function sendSuspectNotification($transaction)
    {
        Log::info('Sending suspect notification', [
            'transactionId' => $transaction->id,
            'externalReference' => $transaction->external_reference
        ]);

        // Send SMS to member
        $this->sendSmsNotification($transaction, 'suspect');

        // Send email to member
        $this->sendEmailNotification($transaction, 'suspect');

        // Send admin alert for manual review
        $this->sendAdminAlert('Suspect transaction requires review', [
            'transactionId' => $transaction->id,
            'externalReference' => $transaction->external_reference,
            'amount' => $transaction->amount,
            'serviceType' => $transaction->external_system,
            'suspectReason' => $this->notificationData['suspect_reason'] ?? 'Unknown'
        ]);

        // Update transaction metadata
        $transaction->update([
            'metadata' => array_merge($transaction->metadata ?? [], [
                'suspect_notification_sent_at' => now()->toIso8601String(),
                'notification_attempts' => ($transaction->metadata['notification_attempts'] ?? 0) + 1
            ])
        ]);
    }

    /**
     * Send SMS notification
     */
    protected function sendSmsNotification($transaction, $type)
    {
        try {
            // Rate limiting for SMS
            $rateLimitKey = "sms_rate_limit:{$transaction->metadata['member_id']}";
            if (!$this->checkRateLimit($rateLimitKey, 5, 3600)) { // 5 SMS per hour
                Log::warning('SMS rate limit exceeded', [
                    'memberId' => $transaction->metadata['member_id'],
                    'transactionId' => $transaction->id
                ]);
                return;
            }

            $message = $this->buildSmsMessage($transaction, $type);
            
            // Use existing SMS service
            $smsService = new \App\Services\SmsService();
            $smsService->sendSms(
                $transaction->metadata['member_phone'] ?? '',
                $message
            );

            Log::info('SMS notification sent', [
                'transactionId' => $transaction->id,
                'type' => $type,
                'phone' => $this->maskPhoneNumber($transaction->metadata['member_phone'] ?? '')
            ]);

        } catch (Exception $e) {
            Log::error('SMS notification failed', [
                'transactionId' => $transaction->id,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send email notification
     */
    protected function sendEmailNotification($transaction, $type)
    {
        try {
            $emailData = $this->buildEmailData($transaction, $type);
            
            // Use existing email service
            Mail::to($transaction->metadata['member_email'] ?? '')
                ->send(new \App\Mail\TransactionNotification($emailData));

            Log::info('Email notification sent', [
                'transactionId' => $transaction->id,
                'type' => $type,
                'email' => $this->maskEmail($transaction->metadata['member_email'] ?? '')
            ]);

        } catch (Exception $e) {
            Log::error('Email notification failed', [
                'transactionId' => $transaction->id,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send admin alert
     */
    protected function sendAdminAlert($subject, $data)
    {
        try {
            // Get admin users
            $adminUsers = User::where('role', 'admin')->orWhere('role', 'super_admin')->get();

            foreach ($adminUsers as $admin) {
                Mail::to($admin->email)->send(new \App\Mail\AdminAlert($subject, $data));
            }

            Log::info('Admin alert sent', [
                'subject' => $subject,
                'adminCount' => $adminUsers->count(),
                'data' => $data
            ]);

        } catch (Exception $e) {
            Log::error('Admin alert failed', [
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check if failure is critical
     */
    protected function isCriticalFailure($transaction)
    {
        $criticalErrorCodes = [
            'EXTERNAL_SERVICE_DOWN',
            'NETWORK_ERROR',
            'TIMEOUT_ERROR',
            'SERVER_ERROR_500',
            'SERVER_ERROR_502',
            'SERVER_ERROR_503',
            'SERVER_ERROR_504'
        ];

        return in_array($transaction->error_code, $criticalErrorCodes);
    }

    /**
     * Check rate limit
     */
    protected function checkRateLimit($key, $maxAttempts, $windowSeconds)
    {
        $attempts = Cache::get($key, 0);
        
        if ($attempts >= $maxAttempts) {
            return false;
        }

        Cache::put($key, $attempts + 1, $windowSeconds);
        return true;
    }

    /**
     * Build SMS message
     */
    protected function buildSmsMessage($transaction, $type)
    {
        $amount = number_format($transaction->amount, 2);
        $reference = $transaction->reference;

        switch ($type) {
            case 'success':
                return "Your transaction of TZS {$amount} has been processed successfully. Ref: {$reference}";
            case 'failure':
                return "Your transaction of TZS {$amount} failed. Please contact support. Ref: {$reference}";
            case 'suspect':
                return "Your transaction of TZS {$amount} is being processed. We'll notify you shortly. Ref: {$reference}";
            default:
                return "Transaction update: TZS {$amount}. Ref: {$reference}";
        }
    }

    /**
     * Build email data
     */
    protected function buildEmailData($transaction, $type)
    {
        return [
            'transaction' => $transaction,
            'type' => $type,
            'amount' => number_format($transaction->amount, 2),
            'reference' => $transaction->reference,
            'externalReference' => $transaction->external_reference,
            'serviceType' => $transaction->external_system,
            'timestamp' => $transaction->created_at,
            'errorMessage' => $transaction->error_message ?? null
        ];
    }

    /**
     * Update notification tracking
     */
    protected function updateNotificationTracking($transaction)
    {
        // Track notification metrics
        $metricsKey = "notification_metrics:{$this->notificationType}:" . date('Y-m-d');
        $metrics = Cache::get($metricsKey, 0);
        Cache::put($metricsKey, $metrics + 1, 86400); // 24 hours

        // Check for high failure rates
        if ($this->notificationType === 'failure') {
            $this->checkFailureRate();
        }
    }

    /**
     * Check failure rate and alert if high
     */
    protected function checkFailureRate()
    {
        $today = date('Y-m-d');
        $failureKey = "notification_metrics:failure:{$today}";
        $successKey = "notification_metrics:success:{$today}";
        
        $failures = Cache::get($failureKey, 0);
        $successes = Cache::get($successKey, 0);
        
        $total = $failures + $successes;
        
        if ($total > 10 && $failures / $total > 0.3) { // 30% failure rate
            $this->sendAdminAlert('High transaction failure rate detected', [
                'failureRate' => round(($failures / $total) * 100, 2) . '%',
                'totalTransactions' => $total,
                'failedTransactions' => $failures,
                'date' => $today
            ]);
        }
    }

    /**
     * Mask phone number for logging
     */
    protected function maskPhoneNumber($phone)
    {
        if (strlen($phone) <= 4) {
            return str_repeat('*', strlen($phone));
        }
        return substr($phone, 0, 2) . str_repeat('*', strlen($phone) - 4) . substr($phone, -2);
    }

    /**
     * Mask email for logging
     */
    protected function maskEmail($email)
    {
        if (empty($email)) {
            return '';
        }
        
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return $email;
        }
        
        $username = $parts[0];
        $domain = $parts[1];
        
        if (strlen($username) <= 2) {
            $maskedUsername = $username;
        } else {
            $maskedUsername = substr($username, 0, 1) . str_repeat('*', strlen($username) - 2) . substr($username, -1);
        }
        
        return $maskedUsername . '@' . $domain;
    }

    /**
     * Handle job failure
     */
    public function failed(Exception $exception)
    {
        Log::error('Transaction notification job failed permanently', [
            'transactionId' => $this->transactionId,
            'notificationType' => $this->notificationType,
            'error' => $exception->getMessage()
        ]);

        // Send admin alert for permanent notification failure
        $this->sendAdminAlert('Notification delivery permanently failed', [
            'transactionId' => $this->transactionId,
            'notificationType' => $this->notificationType,
            'error' => $exception->getMessage()
        ]);
    }
} 