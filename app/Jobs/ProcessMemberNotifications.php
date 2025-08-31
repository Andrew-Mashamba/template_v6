<?php

namespace App\Jobs;

use App\Mail\MemberNotificationMail;
use App\Mail\GuarantorNotificationMail;
use App\Models\User;
use App\Services\SmsService;
use App\Services\SmsTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

use App\Mail\WelcomeEmail;
use App\Mail\GuarantorEmail;

use App\Models\NotificationLog;
use Throwable;
use Illuminate\Support\Str;


class ProcessMemberNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 60;

    protected $member;
    protected $controlNumbers;
    protected $paymentLink;
    protected $processId;
    protected $smsService;
    protected $smsTemplateService;


        /**
     * Create a new job instance.
     */
    public function __construct($member, $controlNumbers = [], $paymentLink = null)
    {
        $this->member = $member;
        $this->controlNumbers = $controlNumbers;
        $this->paymentLink = $paymentLink;
        $this->processId = Str::uuid()->toString();
        $this->smsService = new SmsService();
        $this->smsTemplateService = new SmsTemplateService();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Starting member notification process', [
                'process_id' => $this->processId,
                'member_id' => $this->member->id,
                'control_numbers_count' => is_array($this->controlNumbers) ? count($this->controlNumbers) : 0,
                'control_numbers' => $this->controlNumbers
            ]);

            // Notify member only (removed guarantor notifications)
            $this->notifyMember();

            Log::info('Member notification process completed successfully', [
                'process_id' => $this->processId,
                'member_id' => $this->member->id
            ]);

        } catch (Throwable $e) {
            $this->handleError($e, [
                'process_id' => $this->processId,
                'member_id' => $this->member->id,
                'step' => 'main_process'
            ]);
        }
    }

    protected function notifyMember(): void
    {
        Log::info('Processing member notification', [
            'process_id' => $this->processId,
            'member_id' => $this->member->id ?? null,
            'email' => $this->member->email ?? null
        ]);

        $memberName = $this->member->first_name . ' ' . $this->member->last_name;
        $memberPhone = $this->formatPhoneNumber($this->member->phone_number);
        $email = $this->member->email ?? null;

        // Prepare control numbers with service details
        $controlNumbersWithDetails = [];

        if (!empty($this->controlNumbers)) {
            try {
                Log::info('Processing control numbers', [
                    'process_id' => $this->processId,
                    'count' => is_array($this->controlNumbers) ? count($this->controlNumbers) : 0,
                ]);

                $controlNumbersWithDetails = collect($this->controlNumbers)->map(function ($control, $index) {
                    // Convert stdClass to array if needed
                    if (is_object($control)) {
                        $control = (array) $control;
                    }
                    
                    if (!is_array($control)) {
                        $this->logControlError('Item is not an array or object', $control, $index);
                    }

                    $requiredKeys = ['service_code', 'control_number', 'amount'];
                    foreach ($requiredKeys as $key) {
                        if (!array_key_exists($key, $control)) {
                            $this->logControlError("Missing required key: {$key}", $control, $index);
                        }
                    }

                    return [
                        'service_code'     => $control['service_code'],
                        'service_name'     => $control['service_name'] ?? $control['service_code'],
                        'control_number'   => $control['control_number'],
                        'amount'           => $control['amount'],
                    ];
                })->toArray();

                Log::info('Finished processing control numbers', [
                    'process_id' => $this->processId,
                    'processed_count' => is_array($controlNumbersWithDetails) ? count($controlNumbersWithDetails) : 0,
                ]);

            } catch (\Exception $e) {
                Log::error('Failed to process control numbers', [
                    'process_id' => $this->processId,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }

        // Check if this is a loan disbursement notification
        $isLoanDisbursement = !empty($controlNumbersWithDetails) && 
                             collect($controlNumbersWithDetails)->contains('service_code', 'REP');

        if ($isLoanDisbursement) {
            // Send loan disbursement email
            try {
                // Get loan details from the member's latest loan
                $loanDetails = $this->getLoanDetails($this->member->client_number);
                
                // Get repayment schedule
                $repaymentSchedule = $this->getRepaymentSchedule($this->member->client_number);

                Mail::to($this->member->email)
                    ->send(new \App\Mail\LoanDisbursementEmail(
                        $memberName,
                        $loanDetails,
                        $controlNumbersWithDetails,
                        $this->paymentLink,
                        $repaymentSchedule
                    ));

                Log::info('Loan disbursement email sent successfully', [
                    'process_id' => $this->processId,
                    'member_id' => $this->member->id,
                    'loan_details' => $loanDetails
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send loan disbursement email', [
                    'process_id' => $this->processId,
                    'member_id' => $this->member->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Don't throw exception if email fails - continue with SMS attempt
                // throw $e;
            }
        } else {
            // Send welcome email to member
            try {
                Mail::to($this->member->email)
                    ->send(new WelcomeEmail(
                        $memberName,
                        $controlNumbersWithDetails,
                        $this->paymentLink
                    ));

                Log::info('Welcome email sent successfully', [
                    'process_id' => $this->processId,
                    'member_id' => $this->member->id
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send welcome email', [
                    'process_id' => $this->processId,
                    'member_id' => $this->member->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Don't throw exception if email fails - continue with SMS attempt
                // throw $e;
            }
        }

        // Send SMS Notification for Member
        if (!empty($memberPhone)) {
            // Check if SMS service is configured
            $smsConfigured = config('services.sms.enabled', false);
            
            if ($smsConfigured) {
                try {
                    $smsMessage = $this->generateMemberSMSMessage($memberName, $controlNumbersWithDetails, $isLoanDisbursement);
                    
                    $result = $this->smsService->send($memberPhone, $smsMessage, $this->member, [
                        'smsType' => $isLoanDisbursement ? 'TRANSACTIONAL' : 'TRANSACTIONAL',
                        'serviceName' => $isLoanDisbursement ? 'SACCOSS' : 'SACCOSS',
                        'language' => 'English'
                    ]);

                    Log::info('Member SMS sent successfully', [
                        'process_id' => $this->processId,
                        'member_id' => $this->member->id,
                        'phone' => $memberPhone,
                        'notification_ref' => $result['notification_ref'] ?? null,
                        'is_loan_disbursement' => $isLoanDisbursement
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Failed to send member SMS, falling back to email only', [
                        'process_id' => $this->processId,
                        'member_id' => $this->member->id,
                        'phone' => $memberPhone,
                        'error' => $e->getMessage()
                    ]);
                    // Don't throw exception for SMS failure - email was already sent
                }
            } else {
                // Log SMS message that would have been sent
                $smsMessage = $this->generateMemberSMSMessage($memberName, $controlNumbersWithDetails, $isLoanDisbursement);
                
                Log::info('SMS service not configured - message would have been:', [
                    'process_id' => $this->processId,
                    'member_id' => $this->member->id,
                    'phone' => $memberPhone,
                    'message' => $smsMessage,
                    'payment_link' => $this->paymentLink,
                    'is_loan_disbursement' => $isLoanDisbursement
                ]);
                
                Log::info('Notification sent via email only (SMS not configured)', [
                    'process_id' => $this->processId,
                    'member_id' => $this->member->id,
                    'email' => $this->member->email
                ]);
            }
        } else {
            Log::warning('No phone number available for member SMS', [
                'process_id' => $this->processId,
                'member_id' => $this->member->id
            ]);
        }
    }

    /**
     * Generate SMS message for member using template service
     */
    protected function generateMemberSMSMessage($memberName, $controlNumbers, $isLoanDisbursement)
    {
        if ($isLoanDisbursement) {
            // Loan disbursement SMS
            $loanDetails = $this->getLoanDetails($this->member->client_number);
            $loanAmount = $loanDetails['approved_amount'] ?? 0;
            $monthlyInstallment = $loanDetails['monthly_installment'] ?? 0;
            $controlNumber = !empty($controlNumbers) ? $controlNumbers[0]['control_number'] : null;
            
            return $this->smsTemplateService->generateLoanDisbursementMemberSMS(
                $memberName,
                $loanAmount,
                $monthlyInstallment,
                $controlNumber,
                $this->paymentLink
            );
        } else {
            // Regular member registration SMS
            $controlNumber = !empty($controlNumbers) ? $controlNumbers[0]['control_number'] : null;
            $amount = !empty($controlNumbers) ? $controlNumbers[0]['amount'] : null;
            
            return $this->smsTemplateService->generateMemberRegistrationSMS(
                $memberName,
                $controlNumber,
                $amount,
                $this->paymentLink
            );
        }
    }

    protected function logControlError(string $message, mixed $control, int $index): void
    {
        Log::error($message, [
            'process_id' => $this->processId,
            'index' => $index,
            'control_data' => $control,
            'type' => gettype($control),
        ]);

        throw new \InvalidArgumentException("Invalid control number data at index {$index}: {$message}");
    }

    protected function handleError(Throwable $e, array $logContext)
    {
        Log::error('Error in member notification process', array_merge($logContext, [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]));

        // Log the error in notification_logs
        NotificationLog::logNotification([
            'process_id' => $this->processId,
            'recipient_type' => get_class($this->member),
            'recipient_id' => $this->member->id,
            'notification_type' => 'member_registration',
            'channel' => 'system',
            'status' => 'failed',
            'error_message' => $e->getMessage(),
            'error_details' => [
                'exception_class' => get_class($e),
                'trace' => $e->getTraceAsString()
            ],
            'created_by' => auth()->id()
        ]);

        $this->fail($e);
    }

    public function failed(Throwable $exception)
    {
        Log::error('Member notification process failed permanently', [
            'process_id' => $this->processId,
            'member_id' => $this->member->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Log permanent failure
        NotificationLog::logNotification([
            'process_id' => $this->processId,
            'recipient_type' => get_class($this->member),
            'recipient_id' => $this->member->id,
            'notification_type' => 'member_registration',
            'channel' => 'system',
            'status' => 'failed_permanently',
            'error_message' => $exception->getMessage(),
            'error_details' => [
                'exception_class' => get_class($exception),
                'trace' => $exception->getTraceAsString(),
                'final_attempt' => $this->attempts()
            ],
            'created_by' => auth()->id()
        ]);
    }

    protected function formatPhoneNumber($phone)
    {
        // Remove any non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Ensure it starts with country code
        if (strlen($phone) === 9 && substr($phone, 0, 1) !== '0') {
            $phone = '255' . $phone;
        } elseif (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
            $phone = '255' . substr($phone, 1);
        }
        
        return $phone;
    }

    protected function getLoanDetails($clientNumber)
    {
        try {
            $loan = DB::table('loans')
                ->where('client_number', $clientNumber)
                ->where('status', 'ACTIVE')
                ->orderBy('disbursement_date', 'desc')
                ->first();

            if (!$loan) {
                return [];
            }

            return [
                'approved_amount' => $loan->approved_loan_value ?? $loan->principle,
                'tenure' => $loan->tenure ?? 12,
                'interest_rate' => $loan->interest_rate ?? 0,
                'monthly_installment' => $loan->monthly_installment ?? 0,
                'disbursement_date' => $loan->disbursement_date ? date('d/m/Y', strtotime($loan->disbursement_date)) : date('d/m/Y'),
                'first_payment_date' => $loan->disbursement_date ? date('d/m/Y', strtotime($loan->disbursement_date . ' +1 month')) : date('d/m/Y', strtotime('+1 month')),
                'loan_id' => $loan->id,
                'loan_type' => $loan->loan_type ?? 'PERSONAL_LOAN'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get loan details', [
                'client_number' => $clientNumber,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    protected function getRepaymentSchedule($clientNumber)
    {
        try {
            $loan = DB::table('loans')
                ->where('client_number', $clientNumber)
                ->where('status', 'ACTIVE')
                ->orderBy('disbursement_date', 'desc')
                ->first();

            if (!$loan) {
                return [];
            }

            $schedule = DB::table('loans_schedules')
                ->where('loan_id', $loan->id)
                ->orderBy('installment_date', 'asc')
                ->get();

            return $schedule->map(function ($installment) {
                return [
                    'due_date' => $installment->installment_date ? date('d/m/Y', strtotime($installment->installment_date)) : '',
                    'principal' => $installment->principle ?? 0,
                    'interest' => $installment->interest ?? 0,
                    'total' => $installment->installment ?? 0,
                    'balance' => $installment->closing_balance ?? 0
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::error('Failed to get repayment schedule', [
                'client_number' => $clientNumber,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

}
