<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scheduled Report - {{ $reportType }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            border-radius: 10px 10px 0 0;
            text-align: center;
        }
        .content {
            background: #f8f9fa;
            padding: 30px 20px;
            border-radius: 0 0 10px 10px;
        }
        .report-details {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .label {
            font-weight: bold;
            color: #666;
        }
        .value {
            color: #333;
        }
        .custom-message {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 0 4px 4px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 14px;
        }
        .attachment-notice {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">NBC SACCOS</div>
        <h1>Scheduled Report Delivery</h1>
        <p>{{ $reportType }}</p>
    </div>

    <div class="content">
        <p>Dear Recipient,</p>
        
        <p>Your scheduled report has been generated and is ready for review. Please find the attached PDF document containing the requested financial report.</p>

        @if(!empty($customMessage))
        <div class="custom-message">
            <strong>Additional Message:</strong><br>
            {{ $customMessage }}
        </div>
        @endif

        <div class="report-details">
            <h3>Report Details</h3>
            
            <div class="detail-row">
                <span class="label">Report Type:</span>
                <span class="value">{{ $reportType }}</span>
            </div>
            
            <div class="detail-row">
                <span class="label">Report Period:</span>
                <span class="value">{{ ucfirst($reportPeriod) }}</span>
            </div>
            
            @if($startDate && $endDate)
            <div class="detail-row">
                <span class="label">Date Range:</span>
                <span class="value">{{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}</span>
            </div>
            @endif
            
            <div class="detail-row">
                <span class="label">Currency:</span>
                <span class="value">{{ $currency }}</span>
            </div>
            
            <div class="detail-row">
                <span class="label">Frequency:</span>
                <span class="value">{{ ucfirst($frequency) }}</span>
            </div>
            
            <div class="detail-row">
                <span class="label">Generated On:</span>
                <span class="value">{{ now()->format('M d, Y \a\t H:i') }}</span>
            </div>
        </div>

        <div class="attachment-notice">
            <strong>📎 Attachment Information:</strong><br>
            This email contains a PDF attachment with your requested report. Please ensure your email client supports attachments and check your downloads folder if the attachment doesn't appear automatically.
        </div>

        <h3>Report Summary</h3>
        <p>This {{ strtolower($reportType) }} has been prepared in accordance with:</p>
        <ul>
            <li>International Financial Reporting Standards (IFRS)</li>
            <li>Bank of Tanzania (BOT) Regulatory Requirements</li>
            <li>NBC SACCOS Internal Policies and Procedures</li>
        </ul>

        <p><strong>Important Note:</strong> This report contains confidential financial information. Please handle it securely and in accordance with your organization's data protection policies.</p>

        @if($frequency !== 'once')
        <div style="background: #f0f8ff; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <strong>🔄 Recurring Report:</strong><br>
            This is a {{ $frequency }} scheduled report. You will continue to receive this report automatically according to the configured schedule.
        </div>
        @endif

        <div class="footer">
            <p><strong>NBC SACCOS Financial Reporting System</strong></p>
            <p>This is an automated message. Please do not reply to this email.</p>
            <p>Generated on {{ now()->format('Y-m-d H:i:s') }}</p>
            
            @if($frequency !== 'once')
            <p style="font-size: 12px; color: #888;">
                Next report scheduled for: {{ \Carbon\Carbon::parse($scheduledAt)->addMonths($frequency === 'monthly' ? 1 : ($frequency === 'quarterly' ? 3 : 12))->format('M d, Y') }}
            </p>
            @endif
        </div>
    </div>
</body>
</html> 