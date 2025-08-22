<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Loan;
use App\Models\Saving;
use App\Models\Share;
use App\Models\Member;
use App\Models\Transaction;
use App\Models\Asset;
use App\Models\Investment;
use App\Models\Insurance;
use App\Models\Document;
use App\Models\PerformanceMetric;
use App\Services\DividendCalculationService;
use App\Services\InterestCalculationService;
use App\Services\NotificationService;
use App\Services\ReportGenerationService;
use App\Services\SecurityService;
use App\Services\BackupService;
use App\Services\MandatorySavingsService;
use App\Services\SimpleDailyLoanReportsService;
use App\Services\OptimizedDailyLoanService;
use Illuminate\Support\Facades\Cache;

class DailySystemActivitiesService
{
    protected $previousDay;
    protected $dividendService;
    protected $interestService;
    protected $notificationService;
    protected $reportService;
    protected $securityService;
    protected $backupService;
    protected $mandatorySavingsService;

    public function __construct(
        // DividendCalculationService $dividendService,
        InterestCalculationService $interestService,
        // NotificationService $notificationService,
        ReportGenerationService $reportService,
        SecurityService $securityService,
        BackupService $backupService,
        MandatorySavingsService $mandatorySavingsService
    ) {
        $this->previousDay = Carbon::now()->subDay();
        // $this->dividendService = $dividendService;
        $this->interestService = $interestService;
        // $this->notificationService = $notificationService;
        $this->reportService = $reportService;
        $this->securityService = $securityService;
        $this->backupService = $backupService;
        $this->mandatorySavingsService = $mandatorySavingsService;
    }

    public function executeDailyActivities()
    {
        try {
            DB::beginTransaction();

            // 1. Financial Core Activities
            $this->processLoanActivities();
            $this->processSavingsAndDeposits();
            $this->processShareManagement();
            $this->processFinancialReconciliation();

            // 2. Member and Compliance Activities
            $this->processMemberServices();
            $this->processComplianceAndReporting();

            // 3. System and Security Activities
            $this->processSystemMaintenance();
            $this->processSecurityAndAccessControl();

            // 4. Asset and Investment Activities
            $this->processAssetManagement();
            $this->processInvestmentManagement();
            $this->processInsuranceActivities();

            // 5. Document and Performance Activities
            $this->processDocumentManagement();
            $this->processPerformanceMonitoring();

            // 6. Communication and Notifications
            $this->processCommunicationAndNotifications();
            // Temporarily disabled due to error
            // $this->processMandatorySavings();

            DB::commit();
            Log::info('Daily activities completed successfully for ' . $this->previousDay->format('Y-m-d'));
            return ['status' => 'success', 'date' => $this->previousDay->format('Y-m-d')];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Daily activities failed: ' . $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    protected function processLoanActivities()
    {
        try {
            // Use the optimized service for high-volume processing
            $optimizedService = new OptimizedDailyLoanService();
            $optimizedService->processDailyActivities();
            $statistics = $optimizedService->getStatistics();
            
            // Cache the statistics for monitoring
            Cache::put('daily_loan_processing_stats', $statistics, 86400); // 24 hours
            
            // Generate arrears report
            $this->reportService->generateLoanArrearsReport($this->previousDay);
            
            // Update loan loss provisions
            $this->updateLoanLossProvisions();
            
            // Generate and send daily loan reports with statistics
            $this->generateAndSendDailyLoanReportsWithStats($statistics);

            Log::info('Loan activities completed for ' . $this->previousDay->format('Y-m-d'));
            Log::info('Processing statistics: ' . json_encode($statistics));
        } catch (\Exception $e) {
            Log::error('Loan activities failed: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function processSavingsAndDeposits()
    {
        try {
            // Calculate daily interest
            // $this->interestService->calculateDailySavingsInterest($this->previousDay);

            // Process fixed deposit maturities
            $this->processFixedDepositMaturities();

            // Update deposit balances
            $this->updateDepositBalances();

            // Generate interest reports
            $this->reportService->generateDepositInterestReport($this->previousDay);

            // Process recurring deposits
            $this->processRecurringDeposits();

            Log::info('Savings and deposits activities completed for ' . $this->previousDay->format('Y-m-d'));
        } catch (\Exception $e) {
            Log::error('Savings and deposits activities failed: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function processShareManagement()
    {
        try {
            // Update share values
            // $this->dividendService->updateShareValues($this->previousDay);

            // Process share transactions
            $this->processShareTransactions();

            // Calculate dividends
            // $this->dividendService->calculateDailyDividends($this->previousDay);

            // Update member share balances
            $this->updateMemberShareBalances();

            // Generate share movement reports
            $this->reportService->generateShareMovementReport($this->previousDay);

            Log::info('Share management activities completed for ' . $this->previousDay->format('Y-m-d'));
        } catch (\Exception $e) {
            Log::error('Share management activities failed: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function processFinancialReconciliation()
    {
        try {
            // Reconcile bank accounts
            $this->reconcileBankAccounts();

            // Match bank transactions
            $this->matchBankTransactions();

            // Update general ledger
            $this->updateGeneralLedger();

            // Generate trial balance
            $this->reportService->generateDailyTrialBalance($this->previousDay);

            // Process bank charges
            $this->processBankCharges();

            Log::info('Financial reconciliation completed for ' . $this->previousDay->format('Y-m-d'));
        } catch (\Exception $e) {
            Log::error('Financial reconciliation failed: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function processMemberServices()
    {
        try {
            // Process withdrawals
            $this->processMemberWithdrawals();

            // Update account statuses
            $this->updateMemberAccountStatuses();

            // Generate statements
            $this->reportService->generateMemberStatements($this->previousDay);

            // Process benefits
            $this->processMemberBenefits();

            // Update eligibility
            $this->updateMemberEligibility();

            Log::info('Member services completed for ' . $this->previousDay->format('Y-m-d'));
        } catch (\Exception $e) {
            Log::error('Member services failed: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function processComplianceAndReporting()
    {
        try {
            // Generate regulatory reports
            $this->reportService->generateRegulatoryReports($this->previousDay);

            // Update compliance status
            $this->updateComplianceStatus();

            // Process tax calculations
            $this->processTaxCalculations();

            // Generate audit trails
            $this->generateAuditTrails();

            // Update risk assessments
            $this->updateRiskAssessments();

            Log::info('Compliance and reporting completed for ' . $this->previousDay->format('Y-m-d'));
        } catch (\Exception $e) {
            Log::error('Compliance and reporting failed: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function processSystemMaintenance()
    {
        try {
            // Database backup
            $this->backupService->createDatabaseBackup();

            // Clean system logs
            $this->cleanSystemLogs();

            // Clear cache
            $this->clearSystemCache();

            // Clean temporary files
            $this->cleanTemporaryFiles();

            // Optimize performance
            $this->optimizeSystemPerformance();

            Log::info('System maintenance completed for ' . $this->previousDay->format('Y-m-d'));
        } catch (\Exception $e) {
            Log::error('System maintenance failed: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function processSecurityAndAccessControl()
    {
        try {
            // Audit user activities
            $this->securityService->auditUserActivities($this->previousDay);

            // Update access logs
            $this->securityService->updateAccessLogs();

            // Check suspicious activities
            $this->securityService->checkSuspiciousActivities();

            // Rotate security keys
            $this->securityService->rotateSecurityKeys();

            // Update session records
            $this->securityService->updateSessionRecords();

            Log::info('Security and access control completed for ' . $this->previousDay->format('Y-m-d'));
        } catch (\Exception $e) {
            Log::error('Security and access control failed: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function processAssetManagement()
    {
        try {
            // Update depreciation
            $this->updateAssetDepreciation();

            // Process maintenance schedules
            $this->processAssetMaintenance();

            // Update inventory
            $this->updateInventoryRecords();

            // Generate asset reports
            $this->reportService->generateAssetReports($this->previousDay);

            // Process insurance updates
            $this->processAssetInsurance();

            Log::info('Asset management completed for ' . $this->previousDay->format('Y-m-d'));
        } catch (\Exception $e) {
            Log::error('Asset management failed: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function processInvestmentManagement()
    {
        try {
            // Update investment values
            $this->updateInvestmentValues();

            // Process investment returns
            $this->processInvestmentReturns();

            // Generate investment reports
            $this->reportService->generateInvestmentReports($this->previousDay);

            // Update portfolio allocations
            $this->updatePortfolioAllocations();

            // Process investment maturities
            $this->processInvestmentMaturities();

            Log::info('Investment management completed for ' . $this->previousDay->format('Y-m-d'));
        } catch (\Exception $e) {
            Log::error('Investment management failed: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function processInsuranceActivities()
    {
        try {
            // Update insurance policies
            $this->updateInsurancePolicies();

            // Process insurance claims
            $this->processInsuranceClaims();

            // Generate insurance reports
            $this->reportService->generateInsuranceReports($this->previousDay);

            // Update coverage status
            $this->updateCoverageStatus();

            // Process premium payments
            $this->processPremiumPayments();

            Log::info('Insurance activities completed for ' . $this->previousDay->format('Y-m-d'));
        } catch (\Exception $e) {
            Log::error('Insurance activities failed: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function processDocumentManagement()
    {
        try {
            // Archive old documents
            $this->archiveOldDocuments();

            // Update document statuses
            $this->updateDocumentStatuses();

            // Generate document reports
            $this->reportService->generateDocumentReports($this->previousDay);

            // Process document expiries
            $this->processDocumentExpiries();

            // Update document metadata
            $this->updateDocumentMetadata();

            Log::info('Document management completed for ' . $this->previousDay->format('Y-m-d'));
        } catch (\Exception $e) {
            Log::error('Document management failed: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function processPerformanceMonitoring()
    {
        try {
            // Update performance metrics
            $this->updatePerformanceMetrics();

            // Generate performance reports
            $this->reportService->generatePerformanceReports($this->previousDay);

            // Process KPI calculations
            $this->processKPICalculations();

            // Update benchmark comparisons
            $this->updateBenchmarkComparisons();

            // Generate trend analysis
            $this->generateTrendAnalysis();

            Log::info('Performance monitoring completed for ' . $this->previousDay->format('Y-m-d'));
        } catch (\Exception $e) {
            Log::error('Performance monitoring failed: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function processCommunicationAndNotifications()
    {
        try {
            // Send daily notifications
            // $this->notificationService->sendDailyNotifications($this->previousDay);

            // Process email notifications
            // $this->processEmailNotifications();

            // Process SMS notifications
            // $this->processSMSNotifications();

            // Update notification statuses
            // $this->updateNotificationStatuses();

            // Generate communication reports
            // $this->reportService->generateCommunicationReport($this->previousDay);

            Log::info('Communication and notifications completed for ' . $this->previousDay->format('Y-m-d'));
        } catch (\Exception $e) {
            Log::error('Communication and notifications failed: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function processMandatorySavings()
    {
        try {
            $year = now()->year;
            $month = now()->month;
            
            // Generate tracking records for current month if needed
            $this->mandatorySavingsService->generateTrackingRecords($year, $month);
            
            // Update tracking from new payments
            $this->mandatorySavingsService->updateTrackingFromPayments($year, $month);
            
            // Process overdue records
            $this->mandatorySavingsService->processOverdueRecords();
            
            // Generate notifications for upcoming payments
            $this->mandatorySavingsService->generateNotifications($year, $month);
            
            // Calculate and process arrears
            $this->mandatorySavingsService->calculateArrears();
            
            Log::info('Mandatory savings daily processing completed for ' . $this->previousDay->format('Y-m-d'));
        } catch (\Exception $e) {
            Log::error('Mandatory savings daily processing failed: ' . $e->getMessage());
            throw $e;
        }
    }

    // Helper methods for specific tasks
    private function processLoanRepayments()
    {
        try {
            Log::info('🔄 Starting automatic loan repayment processing for ' . $this->previousDay->format('Y-m-d'));
            
            // Get all active loans with overdue schedules
            $activeLoans = DB::table('loans')
                ->where('loan_status', 'active')
                ->whereNotNull('disbursement_date')
                ->get();
            
            $totalProcessed = 0;
            $totalAmount = 0;
            
            foreach ($activeLoans as $loan) {
                // Get member with deposit account (product_number = '3000')
                $depositAccount = DB::table('accounts')
                    ->where('member_id', $loan->client_id)
                    ->where('product_number', '3000')
                    ->where('status', 'active')
                    ->first();
                
                if (!$depositAccount || $depositAccount->balance <= 0) {
                    continue;
                }
                
                // Get overdue and current schedules
                $schedules = DB::table('loans_schedules')
                    ->where('loan_id', $loan->loan_id)
                    ->whereIn('status', ['overdue', 'pending'])
                    ->where('installment_date', '<=', now())
                    ->orderBy('installment_date', 'asc')
                    ->get();
                
                if ($schedules->isEmpty()) {
                    continue;
                }
                
                $availableBalance = $depositAccount->balance;
                $transactionService = app(\App\Services\TransactionPostingService::class);
                $repaymentService = app(\App\Services\LoanRepaymentService::class);
                
                foreach ($schedules as $schedule) {
                    if ($availableBalance <= 0) {
                        break;
                    }
                    
                    // Calculate arrears
                    $daysOverdue = now()->diffInDays($schedule->installment_date);
                    $amountDue = ($schedule->interest ?? 0) + ($schedule->principle ?? 0) - ($schedule->interest_paid ?? 0) - ($schedule->principle_paid ?? 0);
                    
                    if ($amountDue <= 0) {
                        continue;
                    }
                    
                    // Update arrears information
                    DB::table('loans_schedules')
                        ->where('id', $schedule->id)
                        ->update([
                            'amount_in_arrears' => $amountDue,
                            'days_in_arrears' => $daysOverdue > 0 ? $daysOverdue : 0,
                            'updated_at' => now()
                        ]);
                    
                    // Process payment based on available balance
                    $paymentAmount = min($availableBalance, $amountDue);
                    
                    // Priority: Interest Arrears → Principal Arrears → Current Interest → Current Principal
                    $interestDue = ($schedule->interest ?? 0) - ($schedule->interest_paid ?? 0);
                    $principalDue = ($schedule->principle ?? 0) - ($schedule->principle_paid ?? 0);
                    
                    $interestPayment = min($paymentAmount, $interestDue);
                    $principalPayment = min($paymentAmount - $interestPayment, $principalDue);
                    
                    if ($paymentAmount > 0) {
                        Log::info("💰 Processing automatic repayment for Loan: {$loan->loan_id}, Schedule: {$schedule->id}, Amount: {$paymentAmount}");
                        
                        // Debit deposit account
                        $transactionService->postDoubleEntry(
                            $depositAccount->account_number,
                            'loans_control_account',
                            $paymentAmount,
                            'AUTO_REPAY',
                            "Automatic loan repayment for {$loan->loan_id}",
                            'loan_repayment',
                            auth()->id() ?? 1,
                            $loan->branch_id
                        );
                        
                        // Update deposit account balance
                        DB::table('accounts')
                            ->where('id', $depositAccount->id)
                            ->decrement('balance', $paymentAmount);
                        
                        // Update loan schedule
                        DB::table('loans_schedules')
                            ->where('id', $schedule->id)
                            ->update([
                                'interest_paid' => DB::raw("interest_paid + {$interestPayment}"),
                                'principle_paid' => DB::raw("principle_paid + {$principalPayment}"),
                                'amount_in_arrears' => DB::raw("GREATEST(0, amount_in_arrears - {$paymentAmount})"),
                                'days_in_arrears' => $amountDue <= $paymentAmount ? 0 : $daysOverdue,
                                'status' => $amountDue <= $paymentAmount ? 'paid' : 'partially_paid',
                                'updated_at' => now()
                            ]);
                        
                        // Update loan balance
                        DB::table('loans')
                            ->where('id', $loan->id)
                            ->update([
                                'total_interest_paid' => DB::raw("total_interest_paid + {$interestPayment}"),
                                'total_principal_paid' => DB::raw("total_principal_paid + {$principalPayment}"),
                                'balance' => DB::raw("balance - {$principalPayment}"),
                                'updated_at' => now()
                            ]);
                        
                        // Create payment record
                        DB::table('loan_payments')->insert([
                            'loan_id' => $loan->loan_id,
                            'member_id' => $loan->client_id,
                            'payment_date' => now(),
                            'amount' => $paymentAmount,
                            'principal_amount' => $principalPayment,
                            'interest_amount' => $interestPayment,
                            'payment_method' => 'auto_deduction',
                            'payment_reference' => 'AUTO_' . now()->format('YmdHis') . '_' . $loan->loan_id,
                            'status' => 'completed',
                            'processed_by' => 'SYSTEM',
                            'notes' => "Automatic repayment from deposit account {$depositAccount->account_number}",
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                        
                        $availableBalance -= $paymentAmount;
                        $totalAmount += $paymentAmount;
                        $totalProcessed++;
                    }
                }
                
                // Check if loan is fully paid
                $remainingBalance = DB::table('loans')
                    ->where('id', $loan->id)
                    ->value('balance');
                
                if ($remainingBalance <= 0) {
                    DB::table('loans')
                        ->where('id', $loan->id)
                        ->update([
                            'loan_status' => 'closed',
                            'closed_date' => now(),
                            'updated_at' => now()
                        ]);
                    
                    Log::info("✅ Loan {$loan->loan_id} fully paid and closed");
                }
            }
            
            // Process member notifications for automatic repayments
            $memberNotifications = [];
            
            // Send notifications to members whose accounts were debited
            foreach ($activeLoans as $loan) {
                // Get member details
                $member = DB::table('clients')
                    ->where('id', $loan->client_id)
                    ->first();
                    
                if (!$member) continue;
                
                // Check if this member had any repayments processed
                $memberRepayments = DB::table('loan_payments')
                    ->where('loan_id', $loan->loan_id)
                    ->where('payment_method', 'auto_deduction')
                    ->whereDate('payment_date', now()->toDateString())
                    ->get();
                
                if ($memberRepayments->count() > 0) {
                    $totalMemberAmount = $memberRepayments->sum('amount');
                    
                    // Send notification to member
                    $memberMessage = "Dear {$member->first_name} {$member->last_name},\n\n";
                    $memberMessage .= "Automatic loan repayment has been processed from your deposit account.\n\n";
                    $memberMessage .= "Loan ID: {$loan->loan_id}\n";
                    $memberMessage .= "Amount Deducted: TZS " . number_format($totalMemberAmount, 2) . "\n";
                    $memberMessage .= "Date: " . now()->format('Y-m-d') . "\n";
                    $memberMessage .= "Remaining Loan Balance: TZS " . number_format($loan->balance, 2) . "\n\n";
                    $memberMessage .= "Thank you for maintaining your loan repayments.";
                    
                    // Get user account for this member
                    $userAccount = DB::table('users')
                        ->where('institution_id', $member->client_number)
                        ->first();
                    
                    if ($userAccount) {
                        $notificationService = app(\App\Services\NotificationService::class);
                        $notificationService->createNotification(
                            $userAccount->id,
                            'loan_repayment',
                            'Automatic Loan Repayment Processed',
                            $memberMessage
                        );
                        
                        // Also send email if available
                        if ($member->email) {
                            try {
                                \Illuminate\Support\Facades\Mail::send([], [], function ($message) use ($member, $memberMessage) {
                                    $message->to($member->email)
                                            ->subject('Automatic Loan Repayment Notification')
                                            ->html(nl2br($memberMessage));
                                });
                            } catch (\Exception $e) {
                                Log::error("Failed to send email to {$member->email}: " . $e->getMessage());
                            }
                        }
                        
                        $memberNotifications[] = $member->client_number;
                    }
                }
            }
            
            Log::info("📊 Automatic loan repayment summary: Processed {$totalProcessed} repayments totaling " . number_format($totalAmount, 2));
            Log::info("📧 Notifications sent to " . count($memberNotifications) . " members: " . implode(', ', $memberNotifications));
            
        } catch (\Exception $e) {
            Log::error('❌ Error processing automatic loan repayments: ' . $e->getMessage());
            throw $e;
        }
    }

    private function updateLoanStatuses()
    {
        try {
            Log::info('📊 Updating loan statuses and arrears for ' . $this->previousDay->format('Y-m-d'));
            
            // Update all overdue schedules with arrears information
            $overdueSchedules = DB::table('loans_schedules')
                ->whereIn('status', ['pending', 'overdue', 'partially_paid'])
                ->where('installment_date', '<', now())
                ->get();
            
            foreach ($overdueSchedules as $schedule) {
                $daysOverdue = now()->diffInDays($schedule->installment_date);
                $amountDue = ($schedule->interest ?? 0) + ($schedule->principle ?? 0) 
                            - ($schedule->interest_paid ?? 0) - ($schedule->principle_paid ?? 0);
                
                // Update arrears and status
                DB::table('loans_schedules')
                    ->where('id', $schedule->id)
                    ->update([
                        'amount_in_arrears' => max(0, $amountDue),
                        'days_in_arrears' => $daysOverdue > 0 ? $daysOverdue : 0,
                        'status' => $amountDue > 0 ? 'overdue' : 'paid',
                        'updated_at' => now()
                    ]);
            }
            
            // Update loan statuses based on arrears
            $loansWithArrears = DB::table('loans')
                ->where('loan_status', 'active')
                ->whereNotNull('disbursement_date')
                ->get();
            
            foreach ($loansWithArrears as $loan) {
                $totalArrears = DB::table('loans_schedules')
                    ->where('loan_id', $loan->loan_id)
                    ->where('status', 'overdue')
                    ->sum('amount_in_arrears');
                
                $maxDaysInArrears = DB::table('loans_schedules')
                    ->where('loan_id', $loan->loan_id)
                    ->where('status', 'overdue')
                    ->max('days_in_arrears');
                
                // Update loan with arrears summary
                DB::table('loans')
                    ->where('id', $loan->id)
                    ->update([
                        'total_arrears' => $totalArrears,
                        'days_in_arrears' => $maxDaysInArrears ?? 0,
                        'updated_at' => now()
                    ]);
                
                // Update loan classification based on days in arrears
                $classification = 'PERFORMING';
                if ($maxDaysInArrears > 180) {
                    $classification = 'LOSS';
                } elseif ($maxDaysInArrears > 90) {
                    $classification = 'DOUBTFUL';
                } elseif ($maxDaysInArrears > 30) {
                    $classification = 'SUBSTANDARD';
                } elseif ($maxDaysInArrears > 0) {
                    $classification = 'WATCH';
                }
                
                DB::table('loans')
                    ->where('id', $loan->id)
                    ->update([
                        'loan_classification' => $classification,
                        'updated_at' => now()
                    ]);
            }
            
            Log::info('✅ Loan statuses and arrears updated successfully');
            
        } catch (\Exception $e) {
            Log::error('❌ Error updating loan statuses: ' . $e->getMessage());
            throw $e;
        }
    }

    private function updateLoanLossProvisions()
    {
        try {
            Log::info('💰 Calculating loan loss provisions...');
            
            // Initialize the provision service
            $provisionService = new \App\Services\LoanLossProvisionService();
            
            // Calculate provisions for the previous day
            $summary = $provisionService->calculateDailyProvisions($this->previousDay);
            
            // Log summary statistics
            Log::info('📊 Loan Loss Provisions Summary:');
            Log::info('  - Total Outstanding: TZS ' . number_format($summary['total_outstanding'], 2));
            Log::info('  - General Provisions: TZS ' . number_format($summary['general_provisions'], 2));
            Log::info('  - Specific Provisions: TZS ' . number_format($summary['specific_provisions'], 2));
            Log::info('  - Total Provisions: TZS ' . number_format($summary['total_provisions'], 2));
            Log::info('  - NPL Ratio: ' . number_format($summary['npl_ratio'], 2) . '%');
            Log::info('  - Coverage Ratio: ' . number_format($summary['provision_coverage_ratio'], 2) . '%');
            
            // Alert if NPL ratio exceeds threshold
            if ($summary['npl_ratio'] > 5.0) {
                Log::warning('⚠️ NPL Ratio exceeds 5% threshold: ' . number_format($summary['npl_ratio'], 2) . '%');
                
                // Send alert to management
                $this->sendNPLAlert($summary);
            }
            
            // Alert if coverage ratio is below minimum
            if ($summary['provision_coverage_ratio'] < 100.0) {
                Log::warning('⚠️ Provision coverage below 100%: ' . number_format($summary['provision_coverage_ratio'], 2) . '%');
            }
            
            Log::info('✅ Loan loss provisions updated successfully');
            
        } catch (\Exception $e) {
            Log::error('❌ Error updating loan loss provisions: ' . $e->getMessage());
            // Don't throw - allow other activities to continue
        }
    }
    
    private function sendNPLAlert($summary)
    {
        try {
            // Get management users
            $managers = DB::table('users')
                ->whereIn('role', ['admin', 'manager', 'credit_manager'])
                ->where('status', 'active')
                ->get();
            
            foreach ($managers as $manager) {
                $message = "ALERT: Non-Performing Loans ratio has exceeded the 5% threshold.\n\n";
                $message .= "Current NPL Ratio: " . number_format($summary['npl_ratio'], 2) . "%\n";
                $message .= "Total Outstanding: TZS " . number_format($summary['total_outstanding'], 2) . "\n";
                $message .= "NPL Amount: TZS " . number_format($summary['doubtful_balance'] + $summary['loss_balance'], 2) . "\n";
                $message .= "Provision Coverage: " . number_format($summary['provision_coverage_ratio'], 2) . "%\n\n";
                $message .= "Immediate action required to review and manage non-performing loans.";
                
                // Create notification
                if (class_exists(\App\Services\NotificationService::class)) {
                    $notificationService = app(\App\Services\NotificationService::class);
                    $notificationService->createNotification(
                        $manager->id,
                        'npl_alert',
                        'High NPL Ratio Alert',
                        $message
                    );
                }
                
                // Send email if available
                if ($manager->email) {
                    \Illuminate\Support\Facades\Mail::send([], [], function ($mail) use ($manager, $message, $summary) {
                        $htmlContent = "
                        <html>
                        <body style='font-family: Arial, sans-serif;'>
                            <div style='background-color: #ff4444; color: white; padding: 20px;'>
                                <h2>⚠️ High NPL Ratio Alert</h2>
                            </div>
                            <div style='padding: 20px;'>
                                <p>Dear {$manager->name},</p>
                                <p><strong>The Non-Performing Loans ratio has exceeded the 5% threshold.</strong></p>
                                <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
                                    <tr>
                                        <td style='padding: 10px; border: 1px solid #ddd;'><strong>NPL Ratio:</strong></td>
                                        <td style='padding: 10px; border: 1px solid #ddd; color: red;'>" . number_format($summary['npl_ratio'], 2) . "%</td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 10px; border: 1px solid #ddd;'><strong>Total Outstanding:</strong></td>
                                        <td style='padding: 10px; border: 1px solid #ddd;'>TZS " . number_format($summary['total_outstanding'], 2) . "</td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 10px; border: 1px solid #ddd;'><strong>NPL Amount:</strong></td>
                                        <td style='padding: 10px; border: 1px solid #ddd;'>TZS " . number_format($summary['doubtful_balance'] + $summary['loss_balance'], 2) . "</td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 10px; border: 1px solid #ddd;'><strong>Provision Coverage:</strong></td>
                                        <td style='padding: 10px; border: 1px solid #ddd;'>" . number_format($summary['provision_coverage_ratio'], 2) . "%</td>
                                    </tr>
                                </table>
                                <p style='color: red;'><strong>Immediate action required to review and manage non-performing loans.</strong></p>
                                <p>Best regards,<br>SACCOS System</p>
                            </div>
                        </body>
                        </html>";
                        
                        $mail->to($manager->email)
                             ->subject('URGENT: High NPL Ratio Alert - ' . number_format($summary['npl_ratio'], 2) . '%')
                             ->html($htmlContent);
                    });
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Error sending NPL alerts: ' . $e->getMessage());
        }
    }

    private function processFixedDepositMaturities()
    {
        // Implementation for processing fixed deposit maturities
    }

    private function updateDepositBalances()
    {
        // Implementation for updating deposit balances
    }

    private function processRecurringDeposits()
    {
        // Implementation for processing recurring deposits
    }

    private function processShareTransactions()
    {
        // Implementation for processing share transactions
    }

    private function updateMemberShareBalances()
    {
        // Implementation for updating member share balances
    }

    private function reconcileBankAccounts()
    {
        // Implementation for reconciling bank accounts
    }

    private function matchBankTransactions()
    {
        // Implementation for matching bank transactions
    }

    private function updateGeneralLedger()
    {
        // Implementation for updating general ledger
    }

    private function processBankCharges()
    {
        // Implementation for processing bank charges
    }

    private function processMemberWithdrawals()
    {
        // Implementation for processing member withdrawals
    }

    private function updateMemberAccountStatuses()
    {
        // Implementation for updating member account statuses
    }

    private function processMemberBenefits()
    {
        // Implementation for processing member benefits
    }

    private function updateMemberEligibility()
    {
        // Implementation for updating member eligibility
    }

    private function updateComplianceStatus()
    {
        // Implementation for updating compliance status
    }

    private function processTaxCalculations()
    {
        // Implementation for processing tax calculations
    }

    private function generateAuditTrails()
    {
        // Implementation for generating audit trails
    }

    private function updateRiskAssessments()
    {
        // Implementation for updating risk assessments
    }

    private function cleanSystemLogs()
    {
        // Implementation for cleaning system logs
    }

    private function clearSystemCache()
    {
        // Implementation for clearing system cache
    }

    private function cleanTemporaryFiles()
    {
        // Implementation for cleaning temporary files
    }

    private function optimizeSystemPerformance()
    {
        // Implementation for optimizing system performance
    }

    private function updateAssetDepreciation()
    {
        // Implementation for updating asset depreciation
    }

    private function processAssetMaintenance()
    {
        // Implementation for processing asset maintenance
    }

    private function updateInventoryRecords()
    {
        // Implementation for updating inventory records
    }

    private function processAssetInsurance()
    {
        // Implementation for processing asset insurance
    }

    private function updateInvestmentValues()
    {
        // Implementation for updating investment values
    }

    private function processInvestmentReturns()
    {
        // Implementation for processing investment returns
    }

    private function updatePortfolioAllocations()
    {
        // Implementation for updating portfolio allocations
    }

    private function processInvestmentMaturities()
    {
        // Implementation for processing investment maturities
    }

    private function updateInsurancePolicies()
    {
        // Implementation for updating insurance policies
    }

    private function processInsuranceClaims()
    {
        // Implementation for processing insurance claims
    }

    private function updateCoverageStatus()
    {
        // Implementation for updating coverage status
    }

    private function processPremiumPayments()
    {
        // Implementation for processing premium payments
    }

    private function archiveOldDocuments()
    {
        // Implementation for archiving old documents
    }

    private function updateDocumentStatuses()
    {
        // Implementation for updating document statuses
    }

    private function processDocumentExpiries()
    {
        // Implementation for processing document expiries
    }

    private function updateDocumentMetadata()
    {
        // Implementation for updating document metadata
    }

    private function updatePerformanceMetrics()
    {
        // Implementation for updating performance metrics
    }

    private function processKPICalculations()
    {
        // Implementation for processing KPI calculations
    }

    private function updateBenchmarkComparisons()
    {
        // Implementation for updating benchmark comparisons
    }

    private function generateTrendAnalysis()
    {
        // Implementation for generating trend analysis
    }
    
    private function generateAndSendDailyLoanReports()
    {
        try {
            Log::info('📊 Generating daily loan reports...');
            
            $reportsService = new SimpleDailyLoanReportsService();
            $reportsService->generateAndSendReports($this->previousDay);
            
            Log::info('📧 Daily loan reports generated and sent to all system users');
            
        } catch (\Exception $e) {
            Log::error('❌ Error generating/sending daily loan reports: ' . $e->getMessage());
            // Don't throw - allow other activities to continue
        }
    }
    
    private function generateAndSendDailyLoanReportsWithStats($statistics)
    {
        try {
            Log::info('📊 Generating optimized daily loan reports with statistics...');
            
            // Dispatch the report generation job with statistics
            dispatch(new \App\Jobs\GenerateDailyLoanReports($this->previousDay, $statistics));
            
            Log::info('📧 Daily loan reports job dispatched with processing statistics');
            
        } catch (\Exception $e) {
            Log::error('❌ Error dispatching daily loan reports job: ' . $e->getMessage());
            // Don't throw - allow other activities to continue
        }
    }
} 