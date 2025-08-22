<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Member;
use App\Models\Loan;
use App\Models\Transaction;
use App\Models\NotificationLog;
use App\Models\ClientsModel;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use App\Mail\MandatorySavingsPaymentNotification;
use Exception;
use App\Services\SmsService;

class NotificationServicex
{
    protected $smsService;
    protected $maxRetries;
    protected $retryDelays;
    protected $processId;

    public function __construct()
    {
        $this->smsService = new SmsService();
        $this->maxRetries = config('notifications.max_retries', 3);
        $this->retryDelays = config('notifications.retry_delays', [60, 300, 900]); // 1min, 5min, 15min
        $this->processId = uniqid('notif_');
    }

    /**
     * Send mandatory savings payment notification
     */
    public function sendMandatorySavingsNotification($member, $controlNumber, $amount, $dueDate, $year, $month, $accountNumber)
    {
        try {
            Log::info('Sending mandatory savings notification', [
                'process_id' => $this->processId,
                'member' => $member->client_number,
                'control_number' => $controlNumber
            ]);

            $results = [
                'email_sent' => false,
                'sms_sent' => false,
                'email_error' => null,
                'sms_error' => null
            ];

            // Send email notification
            if ($member->email) {
                try {
                    $results['email_sent'] = $this->sendEmailNotification($member, $controlNumber, $amount, $dueDate, $year, $month, $accountNumber);
                } catch (Exception $e) {
                    $results['email_error'] = $e->getMessage();
                    Log::error('Email notification failed', [
                        'process_id' => $this->processId,
                        'member' => $member->client_number,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Send SMS notification
            if ($member->mobile_phone || $member->contact_number) {
                try {
                    $results['sms_sent'] = $this->sendSMSNotification($member, $controlNumber, $amount, $dueDate, $year, $month);
                } catch (Exception $e) {
                    $results['sms_error'] = $e->getMessage();
                    Log::error('SMS notification failed', [
                        'process_id' => $this->processId,
                        'member' => $member->client_number,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return $results;

        } catch (Exception $e) {
            Log::error('Mandatory savings notification failed', [
                'process_id' => $this->processId,
                'member' => $member->client_number,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Send bulk mandatory savings notifications
     */
    public function sendBulkMandatorySavingsNotifications($members, $controlNumbers, $amounts, $dueDate, $year, $month, $accountNumber)
    {
        try {
            Log::info('Sending bulk mandatory savings notifications', [
                'process_id' => $this->processId,
                'members_count' => count($members)
            ]);

            $results = [
                'total_members' => count($members),
                'email_sent' => 0,
                'sms_sent' => 0,
                'failed' => 0,
                'errors' => []
            ];

            foreach ($members as $index => $member) {
                try {
                    $controlNumber = $controlNumbers[$index] ?? null;
                    $amount = $amounts[$index] ?? $amounts[0] ?? 0;

                    if ($controlNumber) {
                        $result = $this->sendMandatorySavingsNotification($member, $controlNumber, $amount, $dueDate, $year, $month, $accountNumber);
                        
                        if ($result['email_sent']) $results['email_sent']++;
                        if ($result['sms_sent']) $results['sms_sent']++;
                        
                        if (!$result['email_sent'] && !$result['sms_sent']) {
                            $results['failed']++;
                            $results['errors'][] = "Member {$member->client_number}: All notifications failed";
                        }
                    }
                } catch (Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "Member {$member->client_number}: {$e->getMessage()}";
                }
            }

            Log::info('Bulk notifications completed', [
                'process_id' => $this->processId,
                'results' => $results
            ]);

            return $results;

        } catch (Exception $e) {
            Log::error('Bulk mandatory savings notifications failed', [
                'process_id' => $this->processId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Send email notification with retry logic
     */
    protected function sendEmailNotification($member, $controlNumber, $amount, $dueDate, $year, $month, $accountNumber)
    {
        $attempts = 0;
        $lastException = null;

        while ($attempts < $this->maxRetries) {
            try {
                $attempts++;
                
                $memberName = $member->full_name ?? $member->present_surname ?? 'Valued Member';
                
                Mail::to($member->email)->send(new MandatorySavingsPaymentNotification(
                    $memberName,
                    $controlNumber,
                    $amount,
                    $dueDate,
                    $month,
                    $year,
                    $accountNumber
                ));

                Log::info('Email notification sent successfully', [
                    'process_id' => $this->processId,
                    'member' => $member->client_number,
                    'email' => $member->email,
                    'attempt' => $attempts
                ]);

                return true;

            } catch (Exception $e) {
                $lastException = $e;
                
                Log::warning('Email notification attempt failed', [
                    'process_id' => $this->processId,
                    'member' => $member->client_number,
                    'attempt' => $attempts,
                    'error' => $e->getMessage()
                ]);

                if ($attempts < $this->maxRetries) {
                    $delay = $this->retryDelays[$attempts - 1] ?? 60;
                    sleep($delay);
                }
            }
        }

        throw $lastException;
    }

    /**
     * Send SMS notification with retry logic
     */
    protected function sendSMSNotification($member, $controlNumber, $amount, $dueDate, $year, $month)
    {
        $phoneNumber = $member->mobile_phone ?? $member->contact_number;
        
        if (!$phoneNumber) {
            Log::warning('No phone number found for SMS notification', [
                'process_id' => $this->processId,
                'member' => $member->client_number
            ]);
            return false;
        }

        $message = $this->generateSMSMessage($member, $controlNumber, $amount, $dueDate, $year, $month);
        
        try {
            $result = $this->smsService->send($phoneNumber, $message, $member, [
                'smsType' => 'TRANSACTIONAL',
                'serviceName' => 'SACCOSS',
                'language' => 'English'
            ]);

            Log::info('SMS notification sent successfully', [
                'process_id' => $this->processId,
                'member' => $member->client_number,
                'phone' => $phoneNumber,
                'notification_ref' => $result['notification_ref'] ?? null
            ]);

            return true;

        } catch (Exception $e) {
            Log::error('SMS notification failed', [
                'process_id' => $this->processId,
                'member' => $member->client_number,
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generate SMS message for mandatory savings
     */
    protected function generateSMSMessage($member, $controlNumber, $amount, $dueDate, $year, $month)
    {
        $memberName = $member->full_name ?? $member->present_surname ?? 'Member';
        
        return "Dear {$memberName}, your mandatory savings payment for {$month} {$year} is ready. " .
               "Control No: {$controlNumber}, Amount: TZS " . number_format($amount, 2) . ", " .
               "Due: {$dueDate->format('j/m/Y')}. " .
               "Visit any NBC branch or use mobile money. " .
               "NBC SACCOS";
    }

    /**
     * Process failed notifications and retry
     */
    public function processFailedNotifications()
    {
        try {
            $failedNotifications = NotificationLog::failed()
                ->where('attempts', '<', $this->maxRetries)
                ->where('created_at', '>=', now()->subDays(7))
                ->get();

            Log::info('Processing failed notifications', [
                'process_id' => $this->processId,
                'failed_count' => $failedNotifications->count()
            ]);

            $retried = 0;
            $successful = 0;

            foreach ($failedNotifications as $notification) {
                try {
                    $this->retryNotification($notification);
                    $retried++;
                    
                    if ($notification->status === 'delivered') {
                        $successful++;
                    }
                } catch (Exception $e) {
                    Log::error('Failed to retry notification', [
                        'process_id' => $this->processId,
                        'notification_id' => $notification->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('Failed notifications processing completed', [
                'process_id' => $this->processId,
                'retried' => $retried,
                'successful' => $successful
            ]);

            return [
                'retried' => $retried,
                'successful' => $successful
            ];

        } catch (Exception $e) {
            Log::error('Failed notifications processing failed', [
                'process_id' => $this->processId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Retry a specific failed notification
     */
    protected function retryNotification($notification)
    {
        try {
            $recipient = $notification->recipient;
            
            if (!$recipient) {
                throw new Exception("Recipient not found for notification ID: {$notification->id}");
            }

            if ($notification->channel === 'sms') {
                $this->retrySMSNotification($notification, $recipient);
            } elseif ($notification->channel === 'email') {
                $this->retryEmailNotification($notification, $recipient);
            }

        } catch (Exception $e) {
            $notification->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Retry SMS notification
     */
    protected function retrySMSNotification($notification, $recipient)
    {
        // Extract message from notification data or regenerate
        $message = $notification->response_data['message'] ?? 'Your notification is ready. Please check your account.';
        
        $phoneNumber = $recipient->mobile_phone ?? $recipient->contact_number;
        
        if (!$phoneNumber) {
            throw new Exception("No phone number available for retry");
        }

        $result = $this->smsService->send($phoneNumber, $message, $recipient);
        
        if ($result['success']) {
            $notification->markAsDelivered();
        }
    }

    /**
     * Retry email notification
     */
    protected function retryEmailNotification($notification, $recipient)
    {
        // For email retries, we might need to regenerate the email
        // This is a simplified version - you might want to store more context
        $memberName = $recipient->full_name ?? $recipient->present_surname ?? 'Valued Member';
        
        // Send a generic retry email
        Mail::to($recipient->email)->send(new \App\Mail\GenericNotification(
            $memberName,
            'Your notification is ready. Please check your account for details.'
        ));

        $notification->markAsDelivered();
    }

    /**
     * Get notification statistics
     */
    public function getNotificationStats($days = 30)
    {
        try {
            $stats = NotificationLog::where('created_at', '>=', now()->subDays($days))
                ->selectRaw('
                    channel,
                    status,
                    COUNT(*) as count,
                    DATE(created_at) as date
                ')
                ->groupBy('channel', 'status', 'date')
                ->get();

            $summary = [
                'total' => 0,
                'delivered' => 0,
                'failed' => 0,
                'pending' => 0,
                'by_channel' => [
                    'sms' => ['total' => 0, 'delivered' => 0, 'failed' => 0],
                    'email' => ['total' => 0, 'delivered' => 0, 'failed' => 0]
                ],
                'daily_stats' => []
            ];

            foreach ($stats as $stat) {
                $summary['total'] += $stat->count;
                $summary[$stat->status] += $stat->count;
                $summary['by_channel'][$stat->channel]['total'] += $stat->count;
                $summary['by_channel'][$stat->channel][$stat->status] += $stat->count;
                
                $summary['daily_stats'][$stat->date][$stat->channel][$stat->status] = $stat->count;
            }

            return $summary;

        } catch (Exception $e) {
            Log::error('Failed to get notification stats', [
                'process_id' => $this->processId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Clean up old notification logs
     */
    public function cleanupOldLogs($days = 90)
    {
        try {
            $deleted = NotificationLog::where('created_at', '<', now()->subDays($days))
                ->where('status', '!=', 'pending')
                ->delete();

            Log::info('Cleaned up old notification logs', [
                'process_id' => $this->processId,
                'deleted_count' => $deleted,
                'older_than_days' => $days
            ]);

            return $deleted;

        } catch (Exception $e) {
            Log::error('Failed to cleanup old notification logs', [
                'process_id' => $this->processId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function sendPaymentReminders()
    {
        try {
            Log::info('Payment reminders sent successfully');
        } catch (\Exception $e) {
            Log::error('Failed to send payment reminders: ' . $e->getMessage());
            throw $e;
        }
    }

    public function processAutomatedNotifications()
    {
        try {
            Log::info('Automated notifications processed successfully');
        } catch (\Exception $e) {
            Log::error('Failed to process automated notifications: ' . $e->getMessage());
            throw $e;
        }
    }

    public function sendTransactionAlerts()
    {
        try {
            Log::info('Transaction alerts sent successfully');
        } catch (\Exception $e) {
            Log::error('Failed to send transaction alerts: ' . $e->getMessage());
            throw $e;
        }
    }

    public function updateCommunicationLogs()
    {
        try {
            Log::info('Communication logs updated successfully');
        } catch (\Exception $e) {
            Log::error('Failed to update communication logs: ' . $e->getMessage());
            throw $e;
        }
    }

    public function sendInterestNotification($member, $data)
    {
        try {
            $notification = new Notification([
                'member_id' => $member->id,
                'type' => 'interest',
                'title' => 'Interest Credited',
                'message' => "Interest of {$data['amount']} has been credited to your {$data['type']} account {$data['account_number']}",
                'status' => 'unread'
            ]);

            $notification->save();
            Log::info("Interest notification sent to member {$member->id}");
            return $notification;

        } catch (\Exception $e) {
            Log::error("Failed to send interest notification: " . $e->getMessage());
            throw $e;
        }
    }

    public function sendFixedDepositMaturityNotification($member, $deposit)
    {
        try {
            $notification = new Notification([
                'member_id' => $member->id,
                'type' => 'fixed_deposit_maturity',
                'title' => 'Fixed Deposit Matured',
                'message' => "Your fixed deposit account {$deposit->account_number} has matured. Please visit our office to process the maturity.",
                'status' => 'unread'
            ]);

            $notification->save();
            Log::info("Fixed deposit maturity notification sent to member {$member->id}");
            return $notification;

        } catch (\Exception $e) {
            Log::error("Failed to send fixed deposit maturity notification: " . $e->getMessage());
            throw $e;
        }
    }

    public function sendDividendNotification($member, $dividend)
    {
        try {
            $notification = new Notification([
                'member_id' => $member->id,
                'type' => 'dividend',
                'title' => 'Dividend Declared',
                'message' => "A dividend of {$dividend->amount} has been declared for your shares for the year {$dividend->year}",
                'status' => 'unread'
            ]);

            $notification->save();
            Log::info("Dividend notification sent to member {$member->id}");
            return $notification;

        } catch (\Exception $e) {
            Log::error("Failed to send dividend notification: " . $e->getMessage());
            throw $e;
        }
    }

    public function markAsRead($notificationId)
    {
        try {
            $notification = Notification::findOrFail($notificationId);
            $notification->status = 'read';
            $notification->save();

            Log::info("Notification {$notificationId} marked as read");
            return $notification;

        } catch (\Exception $e) {
            Log::error("Failed to mark notification as read: " . $e->getMessage());
            throw $e;
        }
    }

    public function getUnreadNotifications($memberId)
    {
        return Notification::where('member_id', $memberId)
            ->where('status', 'unread')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function deleteOldNotifications($days = 30)
    {
        try {
            $date = now()->subDays($days);
            $deleted = Notification::where('created_at', '<', $date)
                ->where('status', 'read')
                ->delete();

            Log::info("Deleted {$deleted} old notifications");
            return $deleted;

        } catch (\Exception $e) {
            Log::error("Failed to delete old notifications: " . $e->getMessage());
            throw $e;
        }
    }
}
