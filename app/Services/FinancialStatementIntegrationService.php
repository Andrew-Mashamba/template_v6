<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class FinancialStatementIntegrationService
{
    /**
     * Get complete Statement of Financial Position data with all accounting elements
     */
    public function getStatementOfFinancialPosition($asOfDate = null)
    {
        $asOfDate = $asOfDate ?: Carbon::now()->format('Y-m-d');
        
        try {
            // Get all asset components
            $assets = [
                'current_assets' => $this->getCurrentAssets($asOfDate),
                'non_current_assets' => $this->getNonCurrentAssets($asOfDate),
                'total' => 0
            ];
            
            // Get all liability components
            $liabilities = [
                'current_liabilities' => $this->getCurrentLiabilities($asOfDate),
                'non_current_liabilities' => $this->getNonCurrentLiabilities($asOfDate),
                'total' => 0
            ];
            
            // Get equity components
            $equity = $this->getEquityComponents($asOfDate);
            
            // Calculate totals
            $assets['total'] = $assets['current_assets']['total'] + $assets['non_current_assets']['total'];
            $liabilities['total'] = $liabilities['current_liabilities']['total'] + $liabilities['non_current_liabilities']['total'];
            
            return [
                'as_of_date' => $asOfDate,
                'assets' => $assets,
                'liabilities' => $liabilities,
                'equity' => $equity,
                'totals' => [
                    'total_assets' => $assets['total'],
                    'total_liabilities' => $liabilities['total'],
                    'total_equity' => $equity['total'],
                    'total_liabilities_and_equity' => $liabilities['total'] + $equity['total'],
                    'is_balanced' => abs($assets['total'] - ($liabilities['total'] + $equity['total'])) < 0.01
                ]
            ];
            
        } catch (Exception $e) {
            Log::error('Error generating Statement of Financial Position: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get current assets including all new accounting elements
     */
    private function getCurrentAssets($asOfDate)
    {
        $currentAssets = [
            'components' => [],
            'total' => 0
        ];
        
        // 1. Cash and Cash Equivalents
        $cash = $this->getCashAndCashEquivalents($asOfDate);
        $currentAssets['components']['cash_and_equivalents'] = $cash;
        $currentAssets['total'] += $cash['amount'];
        
        // 2. Trade and Other Receivables (Enhanced)
        $receivables = $this->getTradeAndOtherReceivables($asOfDate);
        $currentAssets['components']['trade_receivables'] = $receivables;
        $currentAssets['total'] += $receivables['amount'];
        
        // 3. Short-term Investments
        $shortTermInvestments = $this->getShortTermInvestments($asOfDate);
        $currentAssets['components']['short_term_investments'] = $shortTermInvestments;
        $currentAssets['total'] += $shortTermInvestments['amount'];
        
        // 4. Current Portion of Loan Portfolio
        $currentLoans = $this->getCurrentPortionOfLoans($asOfDate);
        $currentAssets['components']['current_loans'] = $currentLoans;
        $currentAssets['total'] += $currentLoans['amount'];
        
        // 5. Interest Receivable
        $interestReceivable = $this->getInterestReceivable($asOfDate);
        $currentAssets['components']['interest_receivable'] = $interestReceivable;
        $currentAssets['total'] += $interestReceivable['amount'];
        
        // 6. Prepaid Expenses and Other Current Assets
        $prepaid = $this->getPrepaidExpenses($asOfDate);
        $currentAssets['components']['prepaid_expenses'] = $prepaid;
        $currentAssets['total'] += $prepaid['amount'];
        
        return $currentAssets;
    }
    
    /**
     * Get non-current assets including PPE and long-term investments
     */
    private function getNonCurrentAssets($asOfDate)
    {
        $nonCurrentAssets = [
            'components' => [],
            'total' => 0
        ];
        
        // 1. Property, Plant and Equipment (Net of Depreciation)
        $ppe = $this->getPropertyPlantEquipment($asOfDate);
        $nonCurrentAssets['components']['ppe'] = $ppe;
        $nonCurrentAssets['total'] += $ppe['amount'];
        
        // 2. Long-term Investments
        $longTermInvestments = $this->getLongTermInvestments($asOfDate);
        $nonCurrentAssets['components']['long_term_investments'] = $longTermInvestments;
        $nonCurrentAssets['total'] += $longTermInvestments['amount'];
        
        // 3. Long-term Portion of Loan Portfolio
        $longTermLoans = $this->getLongTermPortionOfLoans($asOfDate);
        $nonCurrentAssets['components']['long_term_loans'] = $longTermLoans;
        $nonCurrentAssets['total'] += $longTermLoans['amount'];
        
        // 4. Intangible Assets
        $intangibles = $this->getIntangibleAssets($asOfDate);
        $nonCurrentAssets['components']['intangible_assets'] = $intangibles;
        $nonCurrentAssets['total'] += $intangibles['amount'];
        
        return $nonCurrentAssets;
    }
    
    /**
     * Get current liabilities including all new accounting elements
     */
    private function getCurrentLiabilities($asOfDate)
    {
        $currentLiabilities = [
            'components' => [],
            'total' => 0
        ];
        
        // 1. Trade and Other Payables (Enhanced)
        $payables = $this->getTradeAndOtherPayables($asOfDate);
        $currentLiabilities['components']['trade_payables'] = $payables;
        $currentLiabilities['total'] += $payables['amount'];
        
        // 2. Current Portion of Borrowings
        $currentBorrowings = $this->getCurrentPortionOfBorrowings($asOfDate);
        $currentLiabilities['components']['current_borrowings'] = $currentBorrowings;
        $currentLiabilities['total'] += $currentBorrowings['amount'];
        
        // 3. Interest Payable (Enhanced)
        $interestPayable = $this->getInterestPayable($asOfDate);
        $currentLiabilities['components']['interest_payable'] = $interestPayable;
        $currentLiabilities['total'] += $interestPayable['amount'];
        
        // 4. Unearned/Deferred Revenue
        $unearnedRevenue = $this->getUnearnedRevenue($asOfDate);
        $currentLiabilities['components']['unearned_revenue'] = $unearnedRevenue;
        $currentLiabilities['total'] += $unearnedRevenue['amount'];
        
        // 5. Member Deposits (Current)
        $memberDeposits = $this->getMemberDeposits($asOfDate);
        $currentLiabilities['components']['member_deposits'] = $memberDeposits;
        $currentLiabilities['total'] += $memberDeposits['amount'];
        
        // 6. Insurance Liabilities
        $insuranceLiabilities = $this->getInsuranceLiabilities($asOfDate);
        $currentLiabilities['components']['insurance_liabilities'] = $insuranceLiabilities;
        $currentLiabilities['total'] += $insuranceLiabilities['amount'];
        
        // 7. Creditors (Enhanced)
        $creditors = $this->getCreditors($asOfDate);
        $currentLiabilities['components']['creditors'] = $creditors;
        $currentLiabilities['total'] += $creditors['amount'];
        
        return $currentLiabilities;
    }
    
    /**
     * Get non-current liabilities
     */
    private function getNonCurrentLiabilities($asOfDate)
    {
        $nonCurrentLiabilities = [
            'components' => [],
            'total' => 0
        ];
        
        // 1. Long-term Borrowings
        $longTermBorrowings = $this->getLongTermBorrowings($asOfDate);
        $nonCurrentLiabilities['components']['long_term_borrowings'] = $longTermBorrowings;
        $nonCurrentLiabilities['total'] += $longTermBorrowings['amount'];
        
        // 2. Long-term Deposits
        $longTermDeposits = $this->getLongTermDeposits($asOfDate);
        $nonCurrentLiabilities['components']['long_term_deposits'] = $longTermDeposits;
        $nonCurrentLiabilities['total'] += $longTermDeposits['amount'];
        
        // 3. Deferred Tax Liabilities
        $deferredTax = $this->getDeferredTaxLiabilities($asOfDate);
        $nonCurrentLiabilities['components']['deferred_tax'] = $deferredTax;
        $nonCurrentLiabilities['total'] += $deferredTax['amount'];
        
        return $nonCurrentLiabilities;
    }
    
    /**
     * Get equity components
     */
    private function getEquityComponents($asOfDate)
    {
        $equity = [
            'components' => [],
            'total' => 0
        ];
        
        // 1. Share Capital
        $shareCapital = $this->getShareCapital($asOfDate);
        $equity['components']['share_capital'] = $shareCapital;
        $equity['total'] += $shareCapital['amount'];
        
        // 2. Retained Earnings (including current year profit/loss)
        $retainedEarnings = $this->getRetainedEarnings($asOfDate);
        $equity['components']['retained_earnings'] = $retainedEarnings;
        $equity['total'] += $retainedEarnings['amount'];
        
        // 3. Reserves
        $reserves = $this->getReserves($asOfDate);
        $equity['components']['reserves'] = $reserves;
        $equity['total'] += $reserves['amount'];
        
        // 4. Other Comprehensive Income
        $oci = $this->getOtherComprehensiveIncome($asOfDate);
        $equity['components']['other_comprehensive_income'] = $oci;
        $equity['total'] += $oci['amount'];
        
        return $equity;
    }
    
    /**
     * Get Statement of Comprehensive Income with all accounting elements
     */
    public function getStatementOfComprehensiveIncome($startDate, $endDate)
    {
        try {
            // Revenue components
            $revenue = [
                'components' => [],
                'total' => 0
            ];
            
            // 1. Interest Income from Loans
            $interestIncome = $this->getInterestIncome($startDate, $endDate);
            $revenue['components']['interest_income'] = $interestIncome;
            $revenue['total'] += $interestIncome['amount'];
            
            // 2. Fee and Commission Income
            $feeIncome = $this->getFeeAndCommissionIncome($startDate, $endDate);
            $revenue['components']['fee_income'] = $feeIncome;
            $revenue['total'] += $feeIncome['amount'];
            
            // 3. Other Income (Enhanced)
            $otherIncome = $this->getOtherIncome($startDate, $endDate);
            $revenue['components']['other_income'] = $otherIncome;
            $revenue['total'] += $otherIncome['amount'];
            
            // 4. Investment Income
            $investmentIncome = $this->getInvestmentIncome($startDate, $endDate);
            $revenue['components']['investment_income'] = $investmentIncome;
            $revenue['total'] += $investmentIncome['amount'];
            
            // 5. Insurance Premium Income
            $insuranceIncome = $this->getInsurancePremiumIncome($startDate, $endDate);
            $revenue['components']['insurance_income'] = $insuranceIncome;
            $revenue['total'] += $insuranceIncome['amount'];
            
            // Expense components
            $expenses = [
                'components' => [],
                'total' => 0
            ];
            
            // 1. Interest Expense (Enhanced)
            $interestExpense = $this->getInterestExpense($startDate, $endDate);
            $expenses['components']['interest_expense'] = $interestExpense;
            $expenses['total'] += $interestExpense['amount'];
            
            // 2. Operating Expenses
            $operatingExpenses = $this->getOperatingExpenses($startDate, $endDate);
            $expenses['components']['operating_expenses'] = $operatingExpenses;
            $expenses['total'] += $operatingExpenses['amount'];
            
            // 3. Depreciation and Amortization
            $depreciation = $this->getDepreciationExpense($startDate, $endDate);
            $expenses['components']['depreciation'] = $depreciation;
            $expenses['total'] += $depreciation['amount'];
            
            // 4. Provision for Loan Losses
            $loanLossProvision = $this->getLoanLossProvision($startDate, $endDate);
            $expenses['components']['loan_loss_provision'] = $loanLossProvision;
            $expenses['total'] += $loanLossProvision['amount'];
            
            // 5. Insurance Claims and Benefits
            $insuranceClaims = $this->getInsuranceClaims($startDate, $endDate);
            $expenses['components']['insurance_claims'] = $insuranceClaims;
            $expenses['total'] += $insuranceClaims['amount'];
            
            // Calculate net income
            $netIncome = $revenue['total'] - $expenses['total'];
            
            return [
                'period_start' => $startDate,
                'period_end' => $endDate,
                'revenue' => $revenue,
                'expenses' => $expenses,
                'net_income' => $netIncome,
                'earnings_per_share' => $this->calculateEPS($netIncome),
                'comprehensive_income' => $this->getComprehensiveIncomeItems($startDate, $endDate, $netIncome)
            ];
            
        } catch (Exception $e) {
            Log::error('Error generating Statement of Comprehensive Income: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get Cash Flow Statement with all accounting elements
     */
    public function getCashFlowStatement($startDate, $endDate)
    {
        try {
            $cashFlows = [
                'operating_activities' => $this->getOperatingCashFlows($startDate, $endDate),
                'investing_activities' => $this->getInvestingCashFlows($startDate, $endDate),
                'financing_activities' => $this->getFinancingCashFlows($startDate, $endDate),
                'beginning_cash' => $this->getCashBalance($startDate),
                'ending_cash' => $this->getCashBalance($endDate)
            ];
            
            $cashFlows['net_change'] = 
                $cashFlows['operating_activities']['net'] +
                $cashFlows['investing_activities']['net'] +
                $cashFlows['financing_activities']['net'];
            
            return $cashFlows;
            
        } catch (Exception $e) {
            Log::error('Error generating Cash Flow Statement: ' . $e->getMessage());
            throw $e;
        }
    }
    
    // Individual component retrieval methods
    
    private function getTradeAndOtherReceivables($asOfDate)
    {
        $grossReceivables = 0;
        $badDebtProvision = 0;
        
        if (Schema::hasTable('trade_receivables')) {
            $grossReceivables = DB::table('trade_receivables')
                ->where('status', 'active')
                ->whereDate('invoice_date', '<=', $asOfDate)
                ->sum(DB::raw('amount - COALESCE(amount_paid, 0)'));
            
            $badDebtProvision = DB::table('trade_receivables')
                ->where('status', 'active')
                ->whereDate('invoice_date', '<=', $asOfDate)
                ->sum('bad_debt_provision');
        }
        
        // Also get from GL if available
        $glBalance = DB::table('general_ledgers')
            ->where('sub_category_code', 'TRADE_RECEIVABLES')
            ->sum('balance');
        
        $netAmount = max($grossReceivables - $badDebtProvision, $glBalance);
        
        return [
            'description' => 'Trade and Other Receivables',
            'gross_amount' => $grossReceivables,
            'provision' => $badDebtProvision,
            'amount' => $netAmount,
            'details' => [
                'current' => $this->getReceivablesByAge($asOfDate, 0, 30),
                '30_days' => $this->getReceivablesByAge($asOfDate, 31, 60),
                '60_days' => $this->getReceivablesByAge($asOfDate, 61, 90),
                'over_90_days' => $this->getReceivablesByAge($asOfDate, 91, null)
            ]
        ];
    }
    
    private function getReceivablesByAge($asOfDate, $fromDays, $toDays)
    {
        if (!Schema::hasTable('trade_receivables')) {
            return 0;
        }
        
        $query = DB::table('trade_receivables')
            ->where('status', 'active')
            ->whereDate('invoice_date', '<=', $asOfDate)
            ->whereRaw('DATEDIFF(?, invoice_date) >= ?', [$asOfDate, $fromDays]);
        
        if ($toDays !== null) {
            $query->whereRaw('DATEDIFF(?, invoice_date) <= ?', [$asOfDate, $toDays]);
        }
        
        return $query->sum(DB::raw('amount - COALESCE(amount_paid, 0)'));
    }
    
    private function getPropertyPlantEquipment($asOfDate)
    {
        $cost = 0;
        $accumulatedDepreciation = 0;
        
        if (Schema::hasTable('ppe_assets')) {
            $cost = DB::table('ppe_assets')
                ->whereDate('acquisition_date', '<=', $asOfDate)
                ->where('status', 'active')
                ->sum('cost');
            
            $accumulatedDepreciation = DB::table('ppe_depreciation')
                ->whereDate('depreciation_date', '<=', $asOfDate)
                ->sum('depreciation_amount');
        }
        
        // Also check GL
        $glCost = DB::table('general_ledgers')
            ->where('sub_category_code', 'PPE_COST')
            ->sum('balance');
        
        $glDepreciation = DB::table('general_ledgers')
            ->where('sub_category_code', 'ACCUMULATED_DEPRECIATION')
            ->sum('balance');
        
        $netBookValue = max($cost - $accumulatedDepreciation, $glCost - $glDepreciation);
        
        return [
            'description' => 'Property, Plant and Equipment',
            'cost' => max($cost, $glCost),
            'accumulated_depreciation' => max($accumulatedDepreciation, $glDepreciation),
            'amount' => $netBookValue
        ];
    }
    
    private function getTradeAndOtherPayables($asOfDate)
    {
        $totalPayables = 0;
        
        if (Schema::hasTable('trade_payables')) {
            $totalPayables = DB::table('trade_payables')
                ->where('status', 'active')
                ->whereDate('bill_date', '<=', $asOfDate)
                ->sum(DB::raw('amount - COALESCE(amount_paid, 0)'));
        }
        
        // Include creditors
        if (Schema::hasTable('creditors')) {
            $creditorsBalance = DB::table('creditors')
                ->where('status', 'active')
                ->sum('current_balance');
            $totalPayables += $creditorsBalance;
        }
        
        // Also check GL
        $glBalance = DB::table('general_ledgers')
            ->where('sub_category_code', 'TRADE_PAYABLES')
            ->sum('balance');
        
        return [
            'description' => 'Trade and Other Payables',
            'amount' => max($totalPayables, $glBalance),
            'details' => [
                'trade_creditors' => $this->getTradeCreditors($asOfDate),
                'accrued_expenses' => $this->getAccruedExpenses($asOfDate),
                'other_payables' => $this->getOtherPayables($asOfDate)
            ]
        ];
    }
    
    private function getInterestPayable($asOfDate)
    {
        $interestPayable = 0;
        
        if (Schema::hasTable('interest_payables')) {
            $interestPayable = DB::table('interest_payables')
                ->where('status', 'active')
                ->whereDate('created_at', '<=', $asOfDate)
                ->sum('outstanding_interest');
        }
        
        // Check GL
        $glBalance = DB::table('general_ledgers')
            ->where('sub_category_code', 'INTEREST_PAYABLE')
            ->sum('balance');
        
        return [
            'description' => 'Interest Payable',
            'amount' => max($interestPayable, $glBalance)
        ];
    }
    
    private function getUnearnedRevenue($asOfDate)
    {
        $unearnedRevenue = 0;
        
        if (Schema::hasTable('unearned_deferred_revenue')) {
            $unearnedRevenue = DB::table('unearned_deferred_revenue')
                ->whereDate('created_at', '<=', $asOfDate)
                ->where('status', 'active')
                ->sum(DB::raw('total_amount - COALESCE(recognized_amount, 0)'));
        }
        
        // Check GL
        $glBalance = DB::table('general_ledgers')
            ->where('sub_category_code', 'UNEARNED_REVENUE')
            ->sum('balance');
        
        return [
            'description' => 'Unearned/Deferred Revenue',
            'amount' => max($unearnedRevenue, $glBalance)
        ];
    }
    
    private function getOtherIncome($startDate, $endDate)
    {
        $otherIncome = 0;
        $breakdown = [];
        
        if (Schema::hasTable('other_income_transactions')) {
            $incomeData = DB::table('other_income_transactions')
                ->select('category', DB::raw('SUM(amount) as total'))
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->where('status', 'received')
                ->groupBy('category')
                ->get();
            
            foreach ($incomeData as $income) {
                $breakdown[$income->category] = $income->total;
                $otherIncome += $income->total;
            }
        }
        
        // Check GL
        $glIncome = DB::table('general_ledgers')
            ->where('sub_category_code', 'OTHER_INCOME')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('credit');
        
        return [
            'description' => 'Other Income',
            'amount' => max($otherIncome, $glIncome),
            'breakdown' => $breakdown
        ];
    }
    
    private function getDepreciationExpense($startDate, $endDate)
    {
        $depreciation = 0;
        
        if (Schema::hasTable('ppe_depreciation')) {
            $depreciation = DB::table('ppe_depreciation')
                ->whereBetween('depreciation_date', [$startDate, $endDate])
                ->sum('depreciation_amount');
        }
        
        // Check GL
        $glDepreciation = DB::table('general_ledgers')
            ->where('sub_category_code', 'DEPRECIATION_EXPENSE')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('debit');
        
        return [
            'description' => 'Depreciation and Amortization',
            'amount' => max($depreciation, $glDepreciation)
        ];
    }
    
    private function getOperatingCashFlows($startDate, $endDate)
    {
        $operating = [
            'inflows' => [],
            'outflows' => [],
            'net' => 0
        ];
        
        // Cash received from customers
        if (Schema::hasTable('receivable_payments')) {
            $collections = DB::table('receivable_payments')
                ->whereBetween('payment_date', [$startDate, $endDate])
                ->sum('amount');
            $operating['inflows']['customer_collections'] = $collections;
        }
        
        // Interest received
        $interestReceived = DB::table('loan_repayments')
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->sum('interest_amount');
        $operating['inflows']['interest_received'] = $interestReceived;
        
        // Other income received
        if (Schema::hasTable('other_income_transactions')) {
            $otherIncomeReceived = DB::table('other_income_transactions')
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->where('status', 'received')
                ->sum('amount');
            $operating['inflows']['other_income'] = $otherIncomeReceived;
        }
        
        // Cash paid to suppliers
        if (Schema::hasTable('payable_payments')) {
            $supplierPayments = DB::table('payable_payments')
                ->whereBetween('payment_date', [$startDate, $endDate])
                ->sum('amount');
            $operating['outflows']['supplier_payments'] = $supplierPayments;
        }
        
        // Interest paid
        if (Schema::hasTable('interest_payments')) {
            $interestPaid = DB::table('interest_payments')
                ->whereBetween('payment_date', [$startDate, $endDate])
                ->sum('amount');
            $operating['outflows']['interest_paid'] = $interestPaid;
        }
        
        // Operating expenses paid
        $operatingExpensesPaid = DB::table('expenses')
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->where('status', 'paid')
            ->sum('amount');
        $operating['outflows']['operating_expenses'] = $operatingExpensesPaid;
        
        $totalInflows = array_sum($operating['inflows']);
        $totalOutflows = array_sum($operating['outflows']);
        $operating['net'] = $totalInflows - $totalOutflows;
        
        return $operating;
    }
    
    private function getInvestingCashFlows($startDate, $endDate)
    {
        $investing = [
            'inflows' => [],
            'outflows' => [],
            'net' => 0
        ];
        
        // Loan disbursements (outflow)
        $loansDisbursed = DB::table('loan_accounts')
            ->whereBetween('disbursement_date', [$startDate, $endDate])
            ->where('status', 'disbursed')
            ->sum('principle_amount');
        $investing['outflows']['loans_disbursed'] = $loansDisbursed;
        
        // Loan principal repayments (inflow)
        $principalRepayments = DB::table('loan_repayments')
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->sum('principle_amount');
        $investing['inflows']['loan_repayments'] = $principalRepayments;
        
        // Purchase of PPE (outflow)
        if (Schema::hasTable('ppe_assets')) {
            $ppePurchases = DB::table('ppe_assets')
                ->whereBetween('acquisition_date', [$startDate, $endDate])
                ->sum('cost');
            $investing['outflows']['ppe_purchases'] = $ppePurchases;
        }
        
        // Purchase of investments (outflow)
        if (Schema::hasTable('investments')) {
            $investmentPurchases = DB::table('investments')
                ->whereBetween('purchase_date', [$startDate, $endDate])
                ->sum('purchase_amount');
            $investing['outflows']['investment_purchases'] = $investmentPurchases;
            
            // Sale/maturity of investments (inflow)
            $investmentRedemptions = DB::table('investment_redemptions')
                ->whereBetween('redemption_date', [$startDate, $endDate])
                ->sum('redemption_amount');
            $investing['inflows']['investment_redemptions'] = $investmentRedemptions;
        }
        
        $totalInflows = array_sum($investing['inflows']);
        $totalOutflows = array_sum($investing['outflows']);
        $investing['net'] = $totalInflows - $totalOutflows;
        
        return $investing;
    }
    
    private function getFinancingCashFlows($startDate, $endDate)
    {
        $financing = [
            'inflows' => [],
            'outflows' => [],
            'net' => 0
        ];
        
        // Member deposits (inflow)
        $memberDeposits = DB::table('member_deposits')
            ->whereBetween('deposit_date', [$startDate, $endDate])
            ->sum('amount');
        $financing['inflows']['member_deposits'] = $memberDeposits;
        
        // Member withdrawals (outflow)
        $memberWithdrawals = DB::table('member_withdrawals')
            ->whereBetween('withdrawal_date', [$startDate, $endDate])
            ->sum('amount');
        $financing['outflows']['member_withdrawals'] = $memberWithdrawals;
        
        // Share capital contributions (inflow)
        $shareContributions = DB::table('share_transactions')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('transaction_type', 'contribution')
            ->sum('amount');
        $financing['inflows']['share_contributions'] = $shareContributions;
        
        // Dividends paid (outflow)
        $dividendsPaid = DB::table('dividend_payments')
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->sum('amount');
        $financing['outflows']['dividends_paid'] = $dividendsPaid;
        
        // New borrowings (inflow)
        if (Schema::hasTable('borrowings')) {
            $newBorrowings = DB::table('borrowings')
                ->whereBetween('borrowing_date', [$startDate, $endDate])
                ->sum('loan_amount');
            $financing['inflows']['new_borrowings'] = $newBorrowings;
            
            // Loan repayments (outflow)
            $loanRepayments = DB::table('loan_payments')
                ->whereBetween('payment_date', [$startDate, $endDate])
                ->sum('principal_amount');
            $financing['outflows']['loan_repayments'] = $loanRepayments;
        }
        
        $totalInflows = array_sum($financing['inflows']);
        $totalOutflows = array_sum($financing['outflows']);
        $financing['net'] = $totalInflows - $totalOutflows;
        
        return $financing;
    }
    
    // Helper methods
    
    private function getCashAndCashEquivalents($asOfDate)
    {
        $cash = DB::table('institution_accounts')
            ->where('account_type', 'CASH')
            ->whereDate('created_at', '<=', $asOfDate)
            ->sum('balance');
        
        $bankBalances = DB::table('institution_accounts')
            ->where('account_type', 'BANK')
            ->whereDate('created_at', '<=', $asOfDate)
            ->sum('balance');
        
        return [
            'description' => 'Cash and Cash Equivalents',
            'amount' => $cash + $bankBalances,
            'details' => [
                'cash_on_hand' => $cash,
                'bank_balances' => $bankBalances
            ]
        ];
    }
    
    private function getCashBalance($date)
    {
        return DB::table('institution_accounts')
            ->whereIn('account_type', ['CASH', 'BANK'])
            ->whereDate('created_at', '<=', $date)
            ->sum('balance');
    }
    
    private function getShortTermInvestments($asOfDate)
    {
        $amount = 0;
        
        if (Schema::hasTable('investments')) {
            $amount = DB::table('investments')
                ->where('investment_type', 'short_term')
                ->where('status', 'active')
                ->whereDate('purchase_date', '<=', $asOfDate)
                ->sum('current_value');
        }
        
        return [
            'description' => 'Short-term Investments',
            'amount' => $amount
        ];
    }
    
    private function getLongTermInvestments($asOfDate)
    {
        $amount = 0;
        
        if (Schema::hasTable('investments')) {
            $amount = DB::table('investments')
                ->where('investment_type', 'long_term')
                ->where('status', 'active')
                ->whereDate('purchase_date', '<=', $asOfDate)
                ->sum('current_value');
        }
        
        return [
            'description' => 'Long-term Investments',
            'amount' => $amount
        ];
    }
    
    private function getCurrentPortionOfLoans($asOfDate)
    {
        // Loans expected to be collected within 12 months
        $currentLoans = DB::table('loan_accounts')
            ->where('status', 'active')
            ->whereDate('disbursement_date', '<=', $asOfDate)
            ->whereRaw('DATEDIFF(maturity_date, ?) <= 365', [$asOfDate])
            ->sum('principle_amount');
        
        return [
            'description' => 'Current Portion of Loan Portfolio',
            'amount' => $currentLoans
        ];
    }
    
    private function getLongTermPortionOfLoans($asOfDate)
    {
        // Loans with maturity beyond 12 months
        $longTermLoans = DB::table('loan_accounts')
            ->where('status', 'active')
            ->whereDate('disbursement_date', '<=', $asOfDate)
            ->whereRaw('DATEDIFF(maturity_date, ?) > 365', [$asOfDate])
            ->sum('principle_amount');
        
        return [
            'description' => 'Long-term Portion of Loan Portfolio',
            'amount' => $longTermLoans
        ];
    }
    
    private function getInterestReceivable($asOfDate)
    {
        $interestReceivable = DB::table('loan_accounts')
            ->where('status', 'active')
            ->whereDate('disbursement_date', '<=', $asOfDate)
            ->sum('accrued_interest');
        
        return [
            'description' => 'Interest Receivable',
            'amount' => $interestReceivable
        ];
    }
    
    private function getPrepaidExpenses($asOfDate)
    {
        $prepaid = DB::table('general_ledgers')
            ->where('sub_category_code', 'PREPAID_EXPENSES')
            ->whereDate('created_at', '<=', $asOfDate)
            ->sum('balance');
        
        return [
            'description' => 'Prepaid Expenses and Other Current Assets',
            'amount' => $prepaid
        ];
    }
    
    private function getIntangibleAssets($asOfDate)
    {
        $intangibles = 0;
        
        if (Schema::hasTable('intangible_assets')) {
            $cost = DB::table('intangible_assets')
                ->whereDate('acquisition_date', '<=', $asOfDate)
                ->where('status', 'active')
                ->sum('cost');
            
            $amortization = DB::table('intangible_assets')
                ->whereDate('acquisition_date', '<=', $asOfDate)
                ->where('status', 'active')
                ->sum('accumulated_amortization');
            
            $intangibles = $cost - $amortization;
        }
        
        return [
            'description' => 'Intangible Assets',
            'amount' => $intangibles
        ];
    }
    
    private function getCurrentPortionOfBorrowings($asOfDate)
    {
        $currentBorrowings = 0;
        
        if (Schema::hasTable('borrowings')) {
            $currentBorrowings = DB::table('borrowings')
                ->where('status', 'active')
                ->whereDate('borrowing_date', '<=', $asOfDate)
                ->whereRaw('DATEDIFF(maturity_date, ?) <= 365', [$asOfDate])
                ->sum('outstanding_amount');
        }
        
        return [
            'description' => 'Current Portion of Borrowings',
            'amount' => $currentBorrowings
        ];
    }
    
    private function getLongTermBorrowings($asOfDate)
    {
        $longTermBorrowings = 0;
        
        if (Schema::hasTable('borrowings')) {
            $longTermBorrowings = DB::table('borrowings')
                ->where('status', 'active')
                ->whereDate('borrowing_date', '<=', $asOfDate)
                ->whereRaw('DATEDIFF(maturity_date, ?) > 365', [$asOfDate])
                ->sum('outstanding_amount');
        }
        
        return [
            'description' => 'Long-term Borrowings',
            'amount' => $longTermBorrowings
        ];
    }
    
    private function getMemberDeposits($asOfDate)
    {
        $deposits = DB::table('member_accounts')
            ->where('account_type', 'SAVINGS')
            ->whereDate('created_at', '<=', $asOfDate)
            ->sum('balance');
        
        return [
            'description' => 'Member Deposits',
            'amount' => $deposits
        ];
    }
    
    private function getLongTermDeposits($asOfDate)
    {
        $deposits = DB::table('member_accounts')
            ->where('account_type', 'FIXED_DEPOSIT')
            ->whereDate('created_at', '<=', $asOfDate)
            ->sum('balance');
        
        return [
            'description' => 'Long-term Deposits',
            'amount' => $deposits
        ];
    }
    
    private function getInsuranceLiabilities($asOfDate)
    {
        $insuranceLiabilities = 0;
        
        if (Schema::hasTable('financial_insurance_policies')) {
            // Unearned premiums
            $unearnedPremiums = DB::table('insurance_premiums')
                ->whereDate('created_at', '<=', $asOfDate)
                ->where('status', 'active')
                ->sum(DB::raw('premium_amount - earned_amount'));
            
            // Outstanding claims
            $outstandingClaims = DB::table('insurance_claims')
                ->whereDate('claim_date', '<=', $asOfDate)
                ->whereIn('status', ['pending', 'approved'])
                ->sum('claim_amount');
            
            $insuranceLiabilities = $unearnedPremiums + $outstandingClaims;
        }
        
        return [
            'description' => 'Insurance Liabilities',
            'amount' => $insuranceLiabilities,
            'details' => [
                'unearned_premiums' => $unearnedPremiums ?? 0,
                'claims_payable' => $outstandingClaims ?? 0
            ]
        ];
    }
    
    private function getCreditors($asOfDate)
    {
        $creditors = 0;
        
        if (Schema::hasTable('creditors')) {
            $creditors = DB::table('creditors')
                ->where('status', 'active')
                ->whereDate('created_at', '<=', $asOfDate)
                ->sum('current_balance');
        }
        
        return [
            'description' => 'Creditors',
            'amount' => $creditors
        ];
    }
    
    private function getShareCapital($asOfDate)
    {
        $shareCapital = DB::table('member_accounts')
            ->where('account_type', 'SHARES')
            ->whereDate('created_at', '<=', $asOfDate)
            ->sum('balance');
        
        return [
            'description' => 'Share Capital',
            'amount' => $shareCapital
        ];
    }
    
    private function getRetainedEarnings($asOfDate)
    {
        // Get accumulated retained earnings
        $retainedEarnings = DB::table('general_ledgers')
            ->where('sub_category_code', 'RETAINED_EARNINGS')
            ->whereDate('created_at', '<=', $asOfDate)
            ->sum('balance');
        
        // Add current year profit/loss
        $currentYearStart = Carbon::parse($asOfDate)->startOfYear()->format('Y-m-d');
        $currentYearIncome = $this->getNetIncome($currentYearStart, $asOfDate);
        
        return [
            'description' => 'Retained Earnings',
            'amount' => $retainedEarnings + $currentYearIncome,
            'details' => [
                'accumulated' => $retainedEarnings,
                'current_year' => $currentYearIncome
            ]
        ];
    }
    
    private function getReserves($asOfDate)
    {
        $reserves = DB::table('general_ledgers')
            ->where('sub_category_code', 'RESERVES')
            ->whereDate('created_at', '<=', $asOfDate)
            ->sum('balance');
        
        return [
            'description' => 'Reserves',
            'amount' => $reserves
        ];
    }
    
    private function getOtherComprehensiveIncome($asOfDate)
    {
        // This would include unrealized gains/losses on investments, foreign currency translation, etc.
        $oci = 0;
        
        if (Schema::hasTable('investments')) {
            $unrealizedGains = DB::table('investments')
                ->where('status', 'active')
                ->whereDate('purchase_date', '<=', $asOfDate)
                ->sum(DB::raw('current_value - purchase_amount'));
            $oci += $unrealizedGains;
        }
        
        return [
            'description' => 'Other Comprehensive Income',
            'amount' => $oci
        ];
    }
    
    private function getNetIncome($startDate, $endDate)
    {
        $revenue = DB::table('general_ledgers')
            ->whereIn('major_category_code', ['4000']) // Revenue accounts
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('credit');
        
        $expenses = DB::table('general_ledgers')
            ->whereIn('major_category_code', ['5000']) // Expense accounts
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('debit');
        
        return $revenue - $expenses;
    }
    
    private function getInterestIncome($startDate, $endDate)
    {
        $interestIncome = DB::table('loan_repayments')
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->sum('interest_amount');
        
        return [
            'description' => 'Interest Income from Loans',
            'amount' => $interestIncome
        ];
    }
    
    private function getFeeAndCommissionIncome($startDate, $endDate)
    {
        $feeIncome = DB::table('general_ledgers')
            ->where('sub_category_code', 'FEE_INCOME')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('credit');
        
        return [
            'description' => 'Fee and Commission Income',
            'amount' => $feeIncome
        ];
    }
    
    private function getInvestmentIncome($startDate, $endDate)
    {
        $investmentIncome = 0;
        
        if (Schema::hasTable('investment_income')) {
            $investmentIncome = DB::table('investment_income')
                ->whereBetween('income_date', [$startDate, $endDate])
                ->sum('amount');
        }
        
        return [
            'description' => 'Investment Income',
            'amount' => $investmentIncome
        ];
    }
    
    private function getInsurancePremiumIncome($startDate, $endDate)
    {
        $premiumIncome = 0;
        
        if (Schema::hasTable('insurance_premiums')) {
            $premiumIncome = DB::table('insurance_premiums')
                ->whereBetween('earned_date', [$startDate, $endDate])
                ->sum('earned_amount');
        }
        
        return [
            'description' => 'Insurance Premium Income',
            'amount' => $premiumIncome
        ];
    }
    
    private function getInterestExpense($startDate, $endDate)
    {
        $interestExpense = 0;
        
        // Interest on member deposits
        $depositInterest = DB::table('general_ledgers')
            ->where('sub_category_code', 'DEPOSIT_INTEREST')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('debit');
        
        // Interest on borrowings
        if (Schema::hasTable('interest_payments')) {
            $borrowingInterest = DB::table('interest_payments')
                ->whereBetween('payment_date', [$startDate, $endDate])
                ->sum('amount');
            $interestExpense = $depositInterest + $borrowingInterest;
        } else {
            $interestExpense = $depositInterest;
        }
        
        return [
            'description' => 'Interest Expense',
            'amount' => $interestExpense
        ];
    }
    
    private function getOperatingExpenses($startDate, $endDate)
    {
        $operatingExpenses = DB::table('expenses')
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->where('status', 'approved')
            ->sum('amount');
        
        return [
            'description' => 'Operating Expenses',
            'amount' => $operatingExpenses
        ];
    }
    
    private function getLoanLossProvision($startDate, $endDate)
    {
        $provision = DB::table('general_ledgers')
            ->where('sub_category_code', 'LOAN_LOSS_PROVISION')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('debit');
        
        return [
            'description' => 'Provision for Loan Losses',
            'amount' => $provision
        ];
    }
    
    private function getInsuranceClaims($startDate, $endDate)
    {
        $claims = 0;
        
        if (Schema::hasTable('insurance_claims')) {
            $claims = DB::table('insurance_claims')
                ->whereBetween('payment_date', [$startDate, $endDate])
                ->where('status', 'paid')
                ->sum('claim_amount');
        }
        
        return [
            'description' => 'Insurance Claims and Benefits',
            'amount' => $claims
        ];
    }
    
    private function calculateEPS($netIncome)
    {
        $shares = DB::table('member_accounts')
            ->where('account_type', 'SHARES')
            ->sum('balance');
        
        $shareValue = 1000; // Assuming each share is worth 1000
        $numberOfShares = $shares / $shareValue;
        
        return $numberOfShares > 0 ? $netIncome / $numberOfShares : 0;
    }
    
    private function getComprehensiveIncomeItems($startDate, $endDate, $netIncome)
    {
        $items = [
            'net_income' => $netIncome,
            'other_comprehensive_income' => []
        ];
        
        // Unrealized gains on investments
        if (Schema::hasTable('investments')) {
            $unrealizedGains = DB::table('investment_valuations')
                ->whereBetween('valuation_date', [$startDate, $endDate])
                ->sum('unrealized_gain_loss');
            $items['other_comprehensive_income']['unrealized_investment_gains'] = $unrealizedGains;
        }
        
        $items['total_comprehensive_income'] = $netIncome + array_sum($items['other_comprehensive_income']);
        
        return $items;
    }
    
    // Additional helper methods for specific account types
    
    private function getTradeCreditors($asOfDate)
    {
        if (Schema::hasTable('creditors')) {
            return DB::table('creditors')
                ->where('status', 'active')
                ->where('type', 'supplier')
                ->whereDate('created_at', '<=', $asOfDate)
                ->sum('current_balance');
        }
        return 0;
    }
    
    private function getAccruedExpenses($asOfDate)
    {
        return DB::table('general_ledgers')
            ->where('sub_category_code', 'ACCRUED_EXPENSES')
            ->whereDate('created_at', '<=', $asOfDate)
            ->sum('balance');
    }
    
    private function getOtherPayables($asOfDate)
    {
        return DB::table('general_ledgers')
            ->where('sub_category_code', 'OTHER_PAYABLES')
            ->whereDate('created_at', '<=', $asOfDate)
            ->sum('balance');
    }
    
    private function getDeferredTaxLiabilities($asOfDate)
    {
        return DB::table('general_ledgers')
            ->where('sub_category_code', 'DEFERRED_TAX')
            ->whereDate('created_at', '<=', $asOfDate)
            ->sum('balance');
    }
}