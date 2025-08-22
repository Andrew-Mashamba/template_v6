<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SmsTemplateService
{
    /**
     * Generate loan disbursement SMS for member
     */
    public function generateLoanDisbursementMemberSMS($memberName, $loanAmount, $monthlyInstallment, $controlNumber = null, $paymentLink = null)
    {
        $amount = number_format($loanAmount, 0);
        $installment = number_format($monthlyInstallment, 0);
        
        $message = "Dear {$memberName}, your loan of TZS {$amount} has been disbursed successfully. ";
        $message .= "Monthly payment: TZS {$installment}. ";
        
        if ($controlNumber) {
            $message .= "Control No: {$controlNumber}. ";
        }
        
        $message .= "Pay via NBC Kiganjani, Wakala or branches. ";
        
        if ($paymentLink) {
            $message .= "Or pay online: {$paymentLink}. ";
        }
        
        $message .= "Contact: +255 22 219 7000. NBC SACCOS";
        
        return $this->truncateMessage($message);
    }

    /**
     * Generate loan disbursement SMS for guarantor
     */
    public function generateLoanDisbursementGuarantorSMS($guarantorName, $memberName, $loanAmount)
    {
        $amount = number_format($loanAmount, 0);
        
        $message = "Dear {$guarantorName}, you are guarantor for {$memberName}'s loan of TZS {$amount}. ";
        $message .= "Loan has been disbursed. Please monitor payments. ";
        $message .= "Contact: +255 22 219 7000. NBC SACCOS";
        
        return $this->truncateMessage($message);
    }

    /**
     * Generate member registration SMS
     */
    public function generateMemberRegistrationSMS($memberName, $controlNumber = null, $amount = null)
    {
        $message = "Dear {$memberName}, welcome to NBC SACCOS! ";
        $message .= "Your account has been created successfully. ";
        
        if ($controlNumber && $amount) {
            $formattedAmount = number_format($amount, 0);
            $message .= "Control No: {$controlNumber}, Amount: TZS {$formattedAmount}. ";
        }
        
        $message .= "Pay via NBC Kiganjani, Wakala or branches. ";
        $message .= "Contact: +255 22 219 7000. NBC SACCOS";
        
        return $this->truncateMessage($message);
    }

    /**
     * Generate guarantor notification SMS
     */
    public function generateGuarantorNotificationSMS($guarantorName, $memberName)
    {
        $message = "Dear {$guarantorName}, you are guarantor for {$memberName} at NBC SACCOS. ";
        $message .= "Please ensure timely payments. ";
        $message .= "Contact: +255 22 219 7000. NBC SACCOS";
        
        return $this->truncateMessage($message);
    }

    /**
     * Generate payment reminder SMS
     */
    public function generatePaymentReminderSMS($memberName, $controlNumber, $amount, $dueDate)
    {
        $formattedAmount = number_format($amount, 0);
        $formattedDate = date('j/m/Y', strtotime($dueDate));
        
        $message = "Dear {$memberName}, payment reminder. ";
        $message .= "Control No: {$controlNumber}, Amount: TZS {$formattedAmount}. ";
        $message .= "Due: {$formattedDate}. ";
        $message .= "Pay via NBC Kiganjani, Wakala or branches. NBC SACCOS";
        
        return $this->truncateMessage($message);
    }

    /**
     * Generate payment confirmation SMS
     */
    public function generatePaymentConfirmationSMS($memberName, $controlNumber, $amount, $transactionId)
    {
        $formattedAmount = number_format($amount, 0);
        
        $message = "Dear {$memberName}, payment confirmed. ";
        $message .= "Control No: {$controlNumber}, Amount: TZS {$formattedAmount}. ";
        $message .= "Ref: {$transactionId}. ";
        $message .= "Thank you. NBC SACCOS";
        
        return $this->truncateMessage($message);
    }

    /**
     * Generate loan approval SMS
     */
    public function generateLoanApprovalSMS($memberName, $loanAmount, $tenure)
    {
        $amount = number_format($loanAmount, 0);
        
        $message = "Dear {$memberName}, congratulations! ";
        $message .= "Your loan of TZS {$amount} for {$tenure} months has been approved. ";
        $message .= "We'll contact you for disbursement. ";
        $message .= "Contact: +255 22 219 7000. NBC SACCOS";
        
        return $this->truncateMessage($message);
    }

    /**
     * Generate loan rejection SMS
     */
    public function generateLoanRejectionSMS($memberName, $reason = null)
    {
        $message = "Dear {$memberName}, your loan application has been reviewed. ";
        
        if ($reason) {
            $message .= "Reason: {$reason}. ";
        } else {
            $message .= "Unfortunately, it was not approved at this time. ";
        }
        
        $message .= "Contact us for more information. ";
        $message .= "Contact: +255 22 219 7000. NBC SACCOS";
        
        return $this->truncateMessage($message);
    }

    /**
     * Generate account status update SMS
     */
    public function generateAccountStatusSMS($memberName, $status, $accountNumber)
    {
        $message = "Dear {$memberName}, your account status has been updated. ";
        $message .= "Account: {$accountNumber}, Status: {$status}. ";
        $message .= "Contact: +255 22 219 7000. NBC SACCOS";
        
        return $this->truncateMessage($message);
    }

    /**
     * Generate emergency notification SMS
     */
    public function generateEmergencyNotificationSMS($memberName, $message)
    {
        $emergencyMessage = "Dear {$memberName}, IMPORTANT: {$message}. ";
        $emergencyMessage .= "Contact: +255 22 219 7000. NBC SACCOS";
        
        return $this->truncateMessage($emergencyMessage);
    }

    /**
     * Generate custom SMS with template
     */
    public function generateCustomSMS($template, $variables = [])
    {
        $message = $template;
        
        foreach ($variables as $key => $value) {
            $message = str_replace("{{" . $key . "}}", $value, $message);
        }
        
        return $this->truncateMessage($message);
    }

    /**
     * Truncate message to fit SMS limits
     * Most SMS providers support 160 characters per message
     * For longer messages, they get split into multiple SMS
     */
    protected function truncateMessage($message, $maxLength = 160)
    {
        if (strlen($message) <= $maxLength) {
            return $message;
        }

        // Try to truncate at word boundaries
        $truncated = substr($message, 0, $maxLength - 3);
        $lastSpace = strrpos($truncated, ' ');
        
        if ($lastSpace !== false) {
            $truncated = substr($truncated, 0, $lastSpace);
        }
        
        return $truncated . '...';
    }

    /**
     * Get SMS character count
     */
    public function getCharacterCount($message)
    {
        return strlen($message);
    }

    /**
     * Get SMS segment count (for messages longer than 160 characters)
     */
    public function getSegmentCount($message, $maxLength = 160)
    {
        return ceil(strlen($message) / $maxLength);
    }

    /**
     * Validate SMS message length
     */
    public function validateMessageLength($message, $maxLength = 160)
    {
        $length = strlen($message);
        $segments = $this->getSegmentCount($message, $maxLength);
        
        return [
            'valid' => $length <= $maxLength,
            'length' => $length,
            'segments' => $segments,
            'max_length' => $maxLength
        ];
    }
} 