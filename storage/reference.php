<?php

namespace App\Http\Livewire\Loans;


use App\Models\Charges;
use App\Models\Employee;
use App\Models\general_ledger;
use App\Models\Loan_sub_products;
use App\Models\loans_schedules;
use App\Models\loans_summary;
use App\Models\LoansModel;
use App\Models\MembersModel;
use App\Services\LoanScheduleServiceVersionTwo;
use App\Services\CreditScoreService;
use App\Services\ClientInformationServicex;
use App\Services\ProductParametersServicex;
use App\Services\ExceptionService;
use App\Services\NbcLoansServicex;
use App\Services\SettlementService;
use Carbon\Carbon;
use DateTime;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Livewire\Component;
use Livewire\WithFileUploads;

class Assessment extends Component
{
    use WithFileUploads;

    public $photo, $futureInterest = false, $collateral_type, $collateral_description, $daily_sales, $loan, $collateral_value, $loan_sub_product;
    public $principle = 0, $member, $guarantor, $disbursement_account, $collection_account_loan_interest;
    public $collection_account_loan_principle, $collection_account_loan_charges, $collection_account_loan_penalties;
    public $principle_min_value, $principle_max_value, $min_term, $max_term, $interest_value;
    public $principle_grace_period, $interest_grace_period, $amortization_method;
    public $days_in_a_month = 30, $loan_id, $loan_account_number, $member_number, $topUpBoolena, $new_principle;
    public $interest = 0, $business_licence_number, $business_tin_number, $business_inventory, $cash_at_hand;
    public $cost_of_goods_sold, $operating_expenses, $monthly_taxes, $other_expenses, $monthly_sales;
    public $gross_profit, $table = [], $tablefooter = [], $recommended_tenure, $recommended_installment;
    public $totalAmount, $recommended = true,$monthlyInstallmentValue, $business_age, $bank1 = 123456, $available_funds;
    public $interest_method, $loan_is_settled=false, $approved_term = 12, $approved_loan_value = 0, $future_interests, $futureInsteresAmount, $valueAmmount, $net_profit, $status, $products;
    public $coverage;
    public $idx;
    public $sub_product_id;
    public $product, $account;
    public $charges;

    public $institution1;
    public $institutionAmount;

    public $institution2;
    public $institutionAmount2;
    public $daysBetweenx = 0;


    ///////////////
    public $non_permanent_income_non_taxable = 0;
    public $non_permanent_income_taxable = 0;
    public $take_home = 0;
    public $totalInstallment = 0;
    public $tenure = 12;
    public $max_loan, $selectedContracts=[];
    public $x ;
    public $isPhysicalCollateral = false;
    public $account1,$insurance_list=[];
    public $account2;

    public $selectedLoan;
    public $ClosedLoanBalance;

    public $listeners = [
        'refreshAssessment' => '$refresh',
        'checkAssessmentCompletion' => 'checkAssessmentCompletion'
    ];

    public $errorMessage;

    public $age, $name, $monthsToRetirement, $savings;

    public $creditScoreData;
    protected $creditScoreService;
    public $clientInfoData;
    protected $clientInformationService;
    public $productParamsData;
    protected $productParametersService;
    public $exceptionData;
    protected $exceptionService;
    public $nbcLoansData;
    protected $nbcLoansService;
    public $settlementData;
    protected $settlementService;
    
    // Button states and action tracking
    public $actionCompleted = false;
    public $actionType = '';
    public $actionMessage = '';
    public $showActionButtons = true;
    public $isProcessing = false;
    
    // Settlement form properties
    public $settlementForm = [
        'institution' => '',
        'account' => '',
        'amount' => 0
    ];
    public $editingSettlementId = null;
    public $showSettlementForm = false;

    public function toggleAmount($amount, $key)
    {
        try {
            // Check if the current contract is selected or not
            if (in_array($key, $this->selectedContracts)) {
                // If selected, remove it and decrement the total amount
                $this->totalAmount -= $amount;
                $this->selectedContracts = array_diff($this->selectedContracts, [$key]);

                DB::table('settled_loans')->where('loan_array_id', $key)
                ->where('loan_id',session('currentloanID'))
                ->update([
                    'is_selected'=>false
                ]);
            } else {
                // If not selected, add it and increment the total amount
                $this->totalAmount += $amount;
                $this->selectedContracts[] = $key;
                DB::table('settled_loans')->where('loan_array_id', $key)
                ->where('loan_id',session('currentloanID'))
                ->update([
                    'is_selected'=>true
                ]);

            }
        } catch (\Exception $e) {
            Log::error('Error toggling amount: ' . $e->getMessage());
            $this->errorMessage = 'Error updating selection. Please try again.';
        }
    }

    public function calculateLoanProductCharge($product_id, $principle)
    {
        try {
            if (!$product_id || !$principle) {
                return 0;
            }
            
            // Use the new loan_product_charges table
            $charges = DB::table('loan_product_charges')
                ->where('loan_product_id', $product_id)
                ->where('type', 'charge')
                ->get();

            $totalAmount = 0;

            foreach ($charges as $charge) {
                // Calculate the charge amount based on its type
                $chargeAmount = ($charge->value_type === "percentage")
                    ? ($principle * ($charge->value / 100))
                    : $charge->value;

                // Accumulate the total amount
                $totalAmount += $chargeAmount;
            }

            return $totalAmount;
        } catch (\Exception $e) {
            Log::error('Error calculating loan product charge: ' . $e->getMessage());
            return 0;
        }
    }




    public function calculateLoanProductInsurance($product_id,$principle){
        try {
            if (!$product_id || !$principle) {
                return 0;
            }
            
            // Use the new loan_product_charges table
            $insurances = DB::table('loan_product_charges')
                ->where('loan_product_id', $product_id)
                ->where('type', 'insurance')
                ->get();

            $totalAmount = 0;

            foreach ($insurances as $insurance) {
                $Amount = ($insurance->value_type === "percentage")
                    ? ($principle * ($insurance->value / 100))
                    : $insurance->value;

                // Accumulate the total amount
                $totalAmount += $Amount;
            }

            return $totalAmount;
        } catch (\Exception $e) {
            Log::error('Error calculating loan product insurance: ' . $e->getMessage());
            return 0;
        }
    }




    public function boot(): void
    {
        try {
            // Initialize credit score service
            $this->creditScoreService = new CreditScoreService();

            // Initialize client information service
            $this->clientInformationService = new ClientInformationServicex();

            // Initialize product parameters service
            $this->productParametersService = new ProductParametersServicex();

            //$this->x = new LoanScheduleServiceVersionTwo();

            $loan = LoansModel::find(Session::get('currentloanID'));

            if ($loan) {
                $this->idx = $loan->id;
                $this->loan_id = $loan->loan_id;
                $this->member_number = $loan->member_number;
                $this->sub_product_id = $loan->loan_sub_product;
                $this->take_home = $loan->take_home;

                // Load credit score data
                $this->loadCreditScoreData($loan->client_number);

                // Load client information data
                $this->loadClientInformationData($loan->client_number);

                // Load product parameters data
                $this->loadProductParametersData($loan->loan_sub_product, $loan->principle);

                // Assuming LoanSubProduct is an Eloquent model
                //$this->product = Loan_sub_products::where('sub_product_id', $this->sub_product_id)->first();

                $this->product = Loan_sub_products::where('id', 1)->first();

               //dd($this->product);

                if ($this->product) {
                    // Assuming Charges is related to LoanSubProduct via a 'product_id' foreign key




                    //$this->calculateLoanProductInsurance($this->product->sub_product_id,$loan->principle);
                }
            }



            $this->interest_method = "flat";
            $this->loadLoanDetails();
            $this->loadProductDetails();
            $this->loadMemberDetails();
            $this->receiveData();


            // Get data for loan_array_id = 1
            $settlement1 = DB::table('settled_loans')
                ->where('loan_id', session('currentloanID'))
                ->where('loan_array_id', 1)
                ->first();

            if ($settlement1) {
                $this->institution1 = $settlement1->institution;
                $this->institutionAmount = $settlement1->amount;
                $this->account1 = $settlement1->account;
            }

            // Get data for loan_array_id = 2
            $settlement2 = DB::table('settled_loans')
                ->where('loan_id', session('currentloanID'))
                ->where('loan_array_id', 2)
                ->first();

            if ($settlement2) {
                $this->institution2 = $settlement2->institution;
                $this->institutionAmount2 = $settlement2->amount;
                $this->account2 = $settlement2->account;
            }

            // Calculate total amount for buybacks
            $this->calculateTotal();

            // Initialize exception service after all data is loaded
            $this->loadExceptionData();

            // Initialize NBC loans service
            $this->nbcLoansService = new NbcLoansServicex(
                $this->loan->client_number ?? '',
                $this->selectedLoan
            );

            // Initialize settlement service
            $this->settlementService = new SettlementService(
                Session::get('currentloanID'),
                $this->loan->client_number ?? ''
            );

            // Get NBC loans data
            $this->nbcLoansData = $this->nbcLoansService->getNbcLoansData();
            
            // Get settlement data
            $this->settlementData = $this->settlementService->getSettlementData();
        } catch (\Exception $e) {
            // Log the error and set default values
            Log::error('Assessment component boot error: ' . $e->getMessage());
            $this->errorMessage = 'Error loading assessment data. Please try again.';
        }
    }

    /**
     * Load credit score data for the client
     */
    private function loadCreditScoreData($clientNumber)
    {
        try {
            $this->creditScoreData = $this->creditScoreService->getClientCreditScore($clientNumber);
        } catch (\Exception $e) {
            Log::error('Error loading credit score data: ' . $e->getMessage());
            $this->creditScoreData = $this->creditScoreService->getClientCreditScore(0); // Get default
        }
    }

    /**
     * Load client information data
     */
    private function loadClientInformationData($clientNumber)
    {
        try {
            $this->clientInfoData = $this->clientInformationService->getClientInformation($clientNumber);
        } catch (\Exception $e) {
            Log::error('Error loading client information data: ' . $e->getMessage());
            $this->clientInfoData = $this->clientInformationService->getClientInformation(0); // Get default
        }
    }

    /**
     * Load product parameters data
     */
    private function loadProductParametersData($productId, $approvedLoanValue = 0)
    {
        try {
            $this->productParamsData = $this->productParametersService->getProductParameters($productId, $approvedLoanValue);
        } catch (\Exception $e) {
            Log::error('Error loading product parameters data: ' . $e->getMessage());
            $this->productParamsData = $this->productParametersService->getProductParameters(0, 0); // Get default
        }
    }

    /**
     * Load exception data
     */
    private function loadExceptionData()
    {
        try {
            // Initialize exception service with current data
            $this->exceptionService = new ExceptionService(
                $this->loan,
                $this->product,
                $this->member,
                $this->creditScoreData,
                $this->approved_loan_value,
                $this->approved_term,
                $this->take_home,
                $this->monthlyInstallmentValue,
                $this->collateral_value,
                $this->isPhysicalCollateral
            );

            // Get exception data
            $this->exceptionData = $this->exceptionService->getExceptions();
        } catch (\Exception $e) {
            Log::error('Error loading exception data: ' . $e->getMessage());
            $this->exceptionData = [
                'loan_amount' => ['status' => 'ERROR', 'is_exceeded' => false],
                'term' => ['status' => 'ERROR', 'is_exceeded' => false],
                'credit_score' => ['status' => 'ERROR', 'is_exceeded' => false],
                'salary_installment' => ['status' => 'ERROR', 'is_exceeded' => false],
                'collateral' => ['status' => 'ERROR', 'is_exceeded' => false],
                'summary' => ['overall_status' => 'ERROR', 'can_approve' => false]
            ];
        }
    }

    /**
     * Load NBC loans data
     */
    private function loadNbcLoansData()
    {
        try {
            // Initialize NBC loans service
            $this->nbcLoansService = new NbcLoansServicex(
                $this->loan->client_number ?? '',
                $this->selectedLoan
            );

            // Get NBC loans data
            $this->nbcLoansData = $this->nbcLoansService->getNbcLoansData();
            
            // Get settlement data
            $this->settlementData = $this->nbcLoansService->getSettlementData() ?? [];
        } catch (\Exception $e) {
            Log::error('Error loading NBC loans data: ' . $e->getMessage());
            $this->nbcLoansData = [
                'loans' => [],
                'summary' => ['total_balance' => 0, 'total_installment' => 0, 'total_loans' => 0],
                'has_loans' => false,
                'total_balance' => 0,
                'total_installment' => 0,
                'selected_loan' => null
            ];
            $this->settlementData = [];
        }
    }

    /**
     * Check if credit score meets product requirements
     */
    public function checkCreditScoreException()
    {
        if (!$this->product || !$this->creditScoreData) {
            return false;
        }

        return !$this->creditScoreService->meetsProductRequirements(
            $this->loan->client_number, 
            $this->product->score_limit ?? 0
        );
    }

    public function updated($fieldName, $value) {
        try {
            //dd($fieldName, $value);
            $this->updateFieldInDatabase($fieldName, $value);
            
            // Refresh exception data when key loan parameters change
            if (in_array($fieldName, ['approved_loan_value', 'approved_term', 'take_home', 'collateral_value'])) {
                $this->loadExceptionData();
            }
            
            // Refresh NBC loans data when selected loan changes
            if ($fieldName === 'selectedLoan') {
                $this->loadNbcLoansData();
            }

            // Check assessment completion when key assessment fields are updated
            if (in_array($fieldName, ['approved_loan_value', 'approved_term', 'monthlyInstallmentValue', 'take_home'])) {
                // Add a small delay to ensure the database update is complete
                $this->dispatchBrowserEvent('assessment-field-updated');
                
                // Check completion status after a brief delay
                $this->dispatchBrowserEvent('check-assessment-completion');
            }
        } catch (\Exception $e) {
            Log::error('Error updating field: ' . $e->getMessage());
            $this->errorMessage = 'Error updating field. Please try again.';
        }
    }

    public function updateFieldInDatabase($fieldName, $value) {
        try {
            $model = LoansModel::find(Session::get('currentloanID'));
            if ($model) {
                $model->$fieldName = $value; // Update the field dynamically
                $model->save();
            }
        } catch (\Exception $e) {
            Log::error('Error updating field in database: ' . $e->getMessage());
            $this->errorMessage = 'Error updating loan data. Please try again.';
        }
    }



    // Calculate the first interest amount of the first installment
    // Calculate the first interest amount of the first installment
    public function calculateFirstInterestAmount($principal, $monthlyInterestRate, $dayOfTheMonth) {
        try {
            if ($principal <= 0 || $monthlyInterestRate <= 0 || $dayOfTheMonth <= 0) {
                return 0;
            }
            
            // Get the current date (disbursement date)
            $disbursementDate = new DateTime(); // current date

            // Clone the current date to calculate the next drawdown date
            $nextDrawdownDate = clone $disbursementDate;

            // Set the day of the month for the drawdown date in the current month
            $nextDrawdownDate->setDate($disbursementDate->format('Y'), $disbursementDate->format('m'), $dayOfTheMonth);

            // Check if today's date equals the drawdown date, if yes, set daysBetween to 0
            if ($disbursementDate->format('Y-m-d') === $nextDrawdownDate->format('Y-m-d')) {
                $daysBetween = 0;
            } else {
                // If the drawdown date for this month has already passed, move to the next month
                if ($disbursementDate > $nextDrawdownDate) {
                    $nextDrawdownDate->modify('first day of next month');
                    $nextDrawdownDate->setDate($nextDrawdownDate->format('Y'), $nextDrawdownDate->format('m'), $dayOfTheMonth);
                }

                // Calculate the number of days between the disbursement date and the next drawdown date
                $daysBetween = $disbursementDate->diff($nextDrawdownDate)->days;
            }

            // Store the days between for debugging or further use
            $this->daysBetweenx = $daysBetween;

            // Get the number of days in the current month
            $daysInMonth = (int) $disbursementDate->format('t');

            // Calculate the daily interest rate based on the monthly interest rate
            $dailyInterestRate = $monthlyInterestRate / $daysInMonth;

            // Calculate the interest accrued for the days between
            $interestAccrued = $principal * $dailyInterestRate * $daysBetween;

            return $interestAccrued;
        } catch (\Exception $e) {
            Log::error('Error calculating first interest amount: ' . $e->getMessage());
            return 0;
        }
    }




    public function calculateTotal()
    {
        try {
            // Use settlement service to get total
            $this->totalAmount = $this->settlementData['total_amount'] ?? 0;
        } catch (\Exception $e) {
            Log::error('Error calculating total: ' . $e->getMessage());
            $this->totalAmount = 0;
        }
    }

    public function setSettlement()
    {
        try {
            $data = [
                'institution' => $this->institution1,
                'account' => $this->account1,
                'amount' => $this->institutionAmount
            ];
            
            $success = $this->settlementService->saveSettlement(1, $data);
            
            if ($success) {
                $this->settlementData = $this->settlementService->getSettlementData();
                $this->calculateTotal();
            } else {
                $this->errorMessage = 'Error saving settlement data. Please try again.';
            }
        } catch (\Exception $e) {
            Log::error('Error setting settlement 1: ' . $e->getMessage());
            $this->errorMessage = 'Error saving settlement data. Please try again.';
        }
    }

    public function setSettlement2()
    {
        try {
            $success = $this->settlementService->saveSettlement(2, [
                'institution' => $this->institution2,
                'account' => $this->account2,
                'amount' => $this->institutionAmount2
            ]);
            
            if ($success) {
                $this->settlementData = $this->settlementService->getSettlementData();
                $this->calculateTotal();
            } else {
                $this->errorMessage = 'Error saving settlement data. Please try again.';
            }
        } catch (\Exception $e) {
            Log::error('Error setting settlement 2: ' . $e->getMessage());
            $this->errorMessage = 'Error saving settlement data. Please try again.';
        }
    }





    public function actionBtns($x)
    {
        switch ($x) {
            case 1:
                $this->recommended = false;
                $this->receiveData();
                break;
            case 2:
                $this->recommended = true;
                break;
            case 3:
                $this->commit();
                break;
            case 4:
                $this->approve();
                break;
            case 5:
                $this->reject();
                break;
            case 6:
                $this->disburse();
                break;
            case 7:
                $this->receiveData();
                break;
            case 33:
                $this->topUpBoolena = true;
                $this->topUp();
                break;
            case 44:
                $this->restructure();
                break;
            case 45:
                $this->disburse();
                break;
            case 55:
                $this->futureInterest = true;
                $this->closeLoan();
                break;
        }
    }

    public function receiveData()
    {
        try {
            $principle = (float)($this->principle ?? 0);
            $interest = (float)($this->interest ?? 0);
            $tenure = (float)($this->tenure ?? 12);
            
            if ($principle > 0 && $interest > 0 && $tenure > 0) {
                $this->generateSchedule($principle, $interest, $tenure);
            }
        } catch (\Exception $e) {
            Log::error('Error generating schedule: ' . $e->getMessage());
            $this->errorMessage = 'Error generating loan schedule. Please check your input values.';
        }
    }

    private function loadLoanDetails(): void
    {
        try {
            $this->loan = LoansModel::find(Session::get('currentloanID'));
            if ($this->loan) {
                $this->fill($this->loan->toArray());

                // Initialize collateral values using the new system
                $this->collateral_value = 0;
                $this->collateral_type = "";
                $this->isPhysicalCollateral = false;

                // Load collateral information using the new system
                $this->collateral_type = $this->collateralType(session('currentloanID'));
                $this->collateral_value = $this->calculateCollateralValue(session('currentloanID'));

                // Calculate coverage percentage
                if ($this->loan->principle > 0) {
                    $this->coverage = (($this->collateral_value / $this->loan->principle) * 100);
                } else {
                    $this->coverage = 0;
                }
                
                $this->monthly_sales = ($this->loan->daily_sales ?? 0) * 30;
                $this->gross_profit = $this->monthly_sales - ($this->cost_of_goods_sold ?? 0);
                $this->net_profit = $this->gross_profit - ($this->monthly_taxes ?? 0);
                $this->available_funds = ($this->net_profit - ($this->other_expenses ?? 0)) / 2;

                // Restore assessment data from JSON if available
                if (!empty($this->loan->assessment_data)) {
                    $assessmentData = json_decode($this->loan->assessment_data, true);
                    if ($assessmentData) {
                        // Restore calculated values
                        $this->coverage = $assessmentData['coverage'] ?? $this->coverage;
                        $this->monthly_sales = $assessmentData['monthly_sales'] ?? $this->monthly_sales;
                        $this->gross_profit = $assessmentData['gross_profit'] ?? $this->gross_profit;
                        $this->net_profit = $assessmentData['net_profit'] ?? $this->net_profit;
                        $this->available_funds = $assessmentData['available_funds'] ?? $this->available_funds;
                        $this->recommended = $assessmentData['recommended'] ?? false;
                        $this->recommended_tenure = $assessmentData['recommended_tenure'] ?? $this->tenure;
                        $this->selectedLoan = $assessmentData['selectedLoan'] ?? null;

                        // Restore service data if available
                        if (isset($assessmentData['credit_score_data'])) {
                            $this->creditScoreData = $assessmentData['credit_score_data'];
                        }
                        if (isset($assessmentData['client_info_data'])) {
                            $this->clientInfoData = $assessmentData['client_info_data'];
                        }
                        if (isset($assessmentData['product_params_data'])) {
                            $this->productParamsData = $assessmentData['product_params_data'];
                        }
                        if (isset($assessmentData['exception_data'])) {
                            $this->exceptionData = $assessmentData['exception_data'];
                        }
                        if (isset($assessmentData['nbc_loans_data'])) {
                            $this->nbcLoansData = $assessmentData['nbc_loans_data'];
                        }
                        if (isset($assessmentData['settlement_data'])) {
                            $this->settlementData = $assessmentData['settlement_data'];
                        }

                        Log::info('Restored assessment data from stored JSON', [
                            'loan_id' => $this->loan->id,
                            'data_keys' => array_keys($assessmentData)
                        ]);
                    }
                }
            } else {
                // If loan not found, create a default loan object to prevent null errors
                Log::warning("Loan not found for currentloanID: " . Session::get('currentloanID'));
                $this->loan = new LoansModel();
                $this->loan->status = 'N/A';
                $this->loan->risk_level = 'N/A';
                $this->loan->recommendation = 'N/A';
                $this->loan->principle = 0;
                $this->loan->daily_sales = 0;
            }
        } catch (\Exception $e) {
            Log::error('Error loading loan details: ' . $e->getMessage());
            $this->errorMessage = 'Error loading loan details. Please try again.';
            
            // Create a default loan object to prevent null errors
            $this->loan = new LoansModel();
            $this->loan->status = 'Error';
            $this->loan->risk_level = 'N/A';
            $this->loan->recommendation = 'N/A';
            $this->loan->principle = 0;
            $this->loan->daily_sales = 0;
        }
    }

    public function collateralType($loan_id)
    {
        try {
            if (!$loan_id) {
                Log::info('collateralType: No loan_id provided');
                return '';
            }
            
            $collateral_type = '';
            $this->isPhysicalCollateral = false;
            
            // Use the new collateral system - get collateral from loan_guarantors and loan_collaterals tables
            $guarantors = DB::table('loan_guarantors')
                ->where('loan_id', $loan_id)
                ->where('status', 'active')
                ->get();
            
            Log::info("collateralType: Found " . $guarantors->count() . " active guarantors for loan_id: " . $loan_id);
            
            foreach ($guarantors as $guarantor) {
                $collaterals = DB::table('loan_collaterals')
                    ->where('loan_guarantor_id', $guarantor->id)
                    ->where('status', 'active')
                    ->get();
                
                Log::info("collateralType: Found " . $collaterals->count() . " active collaterals for guarantor_id: " . $guarantor->id);
                
                foreach ($collaterals as $collateral) {
                    Log::info("collateralType: Processing collateral type: " . $collateral->collateral_type . " for collateral_id: " . $collateral->id);
                    
                    if (in_array($collateral->collateral_type, ['savings', 'deposits', 'shares'])) {
                        $collateral_type = $collateral->collateral_type . '/' . $collateral_type;
                    } else {
                        $collateral_type = $collateral->collateral_type . '/' . $collateral_type;
                        $this->isPhysicalCollateral = true;
                        Log::info("collateralType: Set isPhysicalCollateral to true for collateral_id: " . $collateral->id);
                    }
                }
            }
            
            Log::info("collateralType: Final collateral type: " . $collateral_type . " for loan_id: " . $loan_id);
            return $collateral_type;
        } catch (\Exception $e) {
            Log::error('Error calculating collateral type: ' . $e->getMessage());
            return '';
        }
    }

    public function calculateCollateralValue($loan_id)
    {
        try {
            if (!$loan_id) {
                Log::info('calculateCollateralValue: No loan_id provided');
                return 0;
            }
            
            $amount = 0;
            
            // Use the new collateral system - get collateral from loan_guarantors and loan_collaterals tables
            $guarantors = DB::table('loan_guarantors')
                ->where('loan_id', $loan_id)
                ->where('status', 'active')
                ->get();
            
            Log::info("calculateCollateralValue: Found " . $guarantors->count() . " active guarantors for loan_id: " . $loan_id);
            
            foreach ($guarantors as $guarantor) {
                $collaterals = DB::table('loan_collaterals')
                    ->where('loan_guarantor_id', $guarantor->id)
                    ->where('status', 'active')
                    ->get();
                
                Log::info("calculateCollateralValue: Found " . $collaterals->count() . " active collaterals for guarantor_id: " . $guarantor->id);
                
                foreach ($collaterals as $collateral) {
                    $collateralAmount = (float)($collateral->collateral_amount ?? 0);
                    $amount += $collateralAmount;
                    Log::info("calculateCollateralValue: Added collateral amount: " . $collateralAmount . " for collateral_id: " . $collateral->id);
                }
            }
            
            Log::info("calculateCollateralValue: Total collateral value: " . $amount . " for loan_id: " . $loan_id);
            return $amount;
        } catch (\Exception $e) {
            Log::error('Error calculating collateral value: ' . $e->getMessage());
            return 0;
        }
    }

    private function loadProductDetails(): void
    {
        try {
            if ($this->loan_sub_product) {
                $this->products = Loan_sub_products::where('sub_product_id', $this->loan_sub_product)->get();
                foreach ($this->products as $product) {
                    $this->disbursement_account = $product->disbursement_account;
                    $this->collection_account_loan_interest = $product->collection_account_loan_interest;
                    $this->collection_account_loan_principle = $product->collection_account_loan_principle;
                    $this->collection_account_loan_charges = $product->collection_account_loan_charges;
                    $this->collection_account_loan_penalties = $product->collection_account_loan_penalties;
                    $this->principle_min_value = $product->principle_min_value;
                    $this->principle_max_value = $product->principle_max_value;
                    $this->min_term = $product->min_term;
                    $this->max_term = $product->max_term;
                    $this->interest_value = $product->interest_value;
                    $this->principle_grace_period = $product->principle_grace_period;
                    $this->interest_grace_period = $product->interest_grace_period;
                    $this->amortization_method = $product->amortization_method;
                }
            }
            
            // If no product found, create a default product object to prevent null errors
            if (empty($this->products) || $this->products->isEmpty()) {
                Log::warning("Product not found for loan_sub_product: {$this->loan_sub_product}");
                $this->product = new Loan_sub_products();
                $this->product->sub_product_name = 'N/A';
                $this->product->interest_value = 0;
                $this->product->max_term = 0;
                $this->product->principle_max_value = 0;
                $this->product->sub_product_id = null;
            } else {
                // Set the first product as the main product
                $this->product = $this->products->first();
            }
        } catch (\Exception $e) {
            Log::error('Error loading product details: ' . $e->getMessage());
            $this->errorMessage = 'Error loading product details. Please try again.';
            
            // Create a default product object to prevent null errors
            $this->product = new Loan_sub_products();
            $this->product->sub_product_name = 'Error';
            $this->product->interest_value = 0;
            $this->product->max_term = 0;
            $this->product->principle_max_value = 0;
            $this->product->sub_product_id = null;
        }
    }

    private function loadMemberDetails(): void
    {
        try {
            if ($this->guarantor) {
                $this->guarantor = MembersModel::where('client_number', $this->guarantor)->first();
            }
            if ($this->member_number) {
                $this->member = MembersModel::where('client_number', $this->member_number)->first();
                
                // If member not found, try alternative search methods
                if (!$this->member) {
                    // Try searching by member_number field
                    $this->member = MembersModel::where('member_number', $this->member_number)->first();
                }
                
                if (!$this->member) {
                    // Try searching by account_number field
                    $this->member = MembersModel::where('account_number', $this->member_number)->first();
                }
                
                // If still not found, create a default member object to prevent null errors
                if (!$this->member) {
                    Log::warning("Member not found for member_number: {$this->member_number}");
                    $this->member = new MembersModel();
                    $this->member->first_name = 'N/A';
                    $this->member->middle_name = '';
                    $this->member->last_name = 'N/A';
                    $this->member->date_of_birth = null;
                    $this->member->member_category = 'N/A';
                }
            } else {
                // If no member_number, create a default member object
                $this->member = new MembersModel();
                $this->member->first_name = 'N/A';
                $this->member->middle_name = '';
                $this->member->last_name = 'N/A';
                $this->member->date_of_birth = null;
                $this->member->member_category = 'N/A';
            }
            
            // Initialize additional variables
            $this->age = $this->calculateAge($this->member->date_of_birth ?? null);
            $this->name = $this->member->member_category ?? 'N/A';
            $this->monthsToRetirement = $this->calculateMonthsToRetirement($this->member->date_of_birth ?? null);
            $this->savings = $this->calculateTotalSavings($this->member_number ?? null);
            
        } catch (\Exception $e) {
            Log::error('Error loading member details: ' . $e->getMessage());
            $this->errorMessage = 'Error loading member details. Please try again.';
            
            // Create a default member object to prevent null errors
            $this->member = new MembersModel();
            $this->member->first_name = 'Error';
            $this->member->middle_name = '';
            $this->member->last_name = 'Loading';
            $this->member->date_of_birth = null;
            $this->member->member_category = 'N/A';
            
            // Initialize default values for additional variables
            $this->age = 'N/A';
            $this->name = 'N/A';
            $this->monthsToRetirement = 'N/A';
            $this->savings = 0;
        }
    }
    
    /**
     * Calculate age from date of birth
     */
    private function calculateAge($dateOfBirth): string
    {
        try {
            if (!$dateOfBirth) {
                return 'N/A';
            }
            
            $birthDate = new \DateTime($dateOfBirth);
            $currentDate = new \DateTime();
            $age = $currentDate->diff($birthDate)->y;
            
            return (string) $age;
        } catch (\Exception $e) {
            Log::error('Error calculating age: ' . $e->getMessage());
            return 'N/A';
        }
    }
    
    /**
     * Calculate months to retirement (assuming retirement at 60)
     */
    private function calculateMonthsToRetirement($dateOfBirth): string
    {
        try {
            if (!$dateOfBirth) {
                return 'N/A';
            }
            
            $birthDate = new \DateTime($dateOfBirth);
            $currentDate = new \DateTime();
            $retirementDate = clone $birthDate;
            $retirementDate->modify('+60 years');
            
            if ($currentDate >= $retirementDate) {
                return '0';
            }
            
            $diff = $currentDate->diff($retirementDate);
            $months = ($diff->y * 12) + $diff->m;
            
            return (string) $months;
        } catch (\Exception $e) {
            Log::error('Error calculating months to retirement: ' . $e->getMessage());
            return 'N/A';
        }
    }
    
    /**
     * Calculate total savings for the member
     */
    private function calculateTotalSavings($memberNumber): float
    {
        try {
            if (!$memberNumber) {
                return 0.0;
            }
            
            // Query savings from the share_ownership table
            $shareOwnership = DB::table('share_ownership')
                ->where('client_number', $memberNumber)
                ->first();
                
            if ($shareOwnership) {
                $savings = (float) ($shareOwnership->savings ?? 0);
                $deposits = (float) ($shareOwnership->deposits ?? 0);
                return $savings + $deposits;
            }
            
            return 0.0;
        } catch (\Exception $e) {
            Log::error('Error calculating total savings: ' . $e->getMessage());
            return 0.0;
        }
    }

    function sendToException(){
        try {
            $data = [
                'loan_type_3'  => "Exception",
            ];

            // Check if stage_id is numeric
            LoansModel::where('id', Session::get('currentloanID'))->update($data);
            Session::flash('loan_commit', 'The loan has been committed!');
            Session::flash('alert-class', 'alert-success');
            Session::put('currentloanID', null);
            Session::put('currentloanClient', null);
            $this->emit('currentloanID');
        } catch (\Exception $e) {
            Log::error('Error sending to exception: ' . $e->getMessage());
            $this->errorMessage = 'Error sending loan to exception. Please try again.';
        }
    }


    public function render(): Factory|\Illuminate\Contracts\View\View|\Illuminate\Contracts\Foundation\Application
    {
        try {
            if ($this->product && $this->product->sub_product_id) {
                // Use the new loan_product_charges table instead of the old system
                $chargesAndInsurance = DB::table('loan_product_charges')
                    ->where('loan_product_id', $this->product->sub_product_id)
                    ->get();

                // Separate charges and insurance
                $this->charges = $chargesAndInsurance->filter(function($item) {
                    return $item->type === 'charge';
                })->values();
                $this->insurance_list = $chargesAndInsurance->filter(function($item) {
                    return $item->type === 'insurance';
                })->values()->toArray();

                Log::info('Loaded charges and insurance from new system', [
                    'product_id' => $this->product->sub_product_id,
                    'charges_count' => $this->charges->count(),
                    'insurance_count' => count($this->insurance_list)
                ]);
            } else {
                $this->charges = collect();
                $this->insurance_list = [];
            }

            return view('livewire.loans.assessment');
        } catch (\Exception $e) {
            Log::error('Error rendering assessment component: ' . $e->getMessage());
            $this->errorMessage = 'Error rendering assessment. Please try again.';
            return view('livewire.loans.assessment');
        }
    }

    private function generateSchedule($disbursed_amount, $interest_rate, $tenure): void
    {
        try {
            if ($disbursed_amount <= 0 || $interest_rate <= 0 || $tenure <= 0) {
                $this->table = [];
                $this->tablefooter = [];
                $this->recommended_tenure = $tenure;
                return;
            }

            $principal = $disbursed_amount;
            $dailyInterestRate = $interest_rate / 100;
            $termDays = $tenure;
            $balance = $principal;
            $date = Carbon::now()->addDay();
            $datalist = [];
            $totPayment = 0;
            $totInterest = 0;
            $totPrincipal = 0;

            for ($i = 0; $i < $termDays; $i++) {
                $dailyInstallment = ($principal + ($principal * $dailyInterestRate)) / $termDays;
                $principalPayment = $principal / $termDays;
                $interest = $dailyInstallment - $principalPayment;
                $balance -= $principalPayment;
                $totPayment += $dailyInstallment;
                $totInterest += $interest;
                $totPrincipal += $principalPayment;

                $datalist[] = [
                    "Payment" => $dailyInstallment,
                    "Interest" => $interest,
                    "Principle" => $principalPayment,
                    "balance" => $balance,
                    "Date" => $date->format('Y-m-d')
                ];

                $date->modify('+30 day');
            }

            $this->table = $datalist;
            $this->tablefooter = [[
                "Payment" => $totPayment,
                "Interest" => $totInterest,
                "Principle" => $totPrincipal,
                "balance" => $balance,
            ]];

            $this->recommended_tenure = $termDays;
            //$this->recommended_installment = $dailyInstallment;
        } catch (\Exception $e) {
            Log::error('Error generating schedule: ' . $e->getMessage());
            $this->errorMessage = 'Error generating loan schedule. Please check your input values.';
            $this->table = [];
            $this->tablefooter = [];
        }
    }

    function getStage($loan_id)
    {

        $loan = DB::table('loans')->where('id', $loan_id)->first();
        $product_id = $loan->loan_sub_product;
        $stage_id = $loan->stage_id;
        $loan_product_id = DB::table('loan_sub_products')->where('sub_product_id', $product_id)->value('id');
        $current_stage = DB::table('loan_stages')->where('committee_id', $stage_id)
            ->where('loan_product_id', $loan_product_id)->value('stage_id');

        $next_statge = $current_stage + 1;

        if (DB::table('loan_stages')->where('stage_id', $next_statge)
            ->where('loan_product_id', $loan_product_id)->exists()
        ) {

            $committee_id = DB::table('loan_stages')->where('stage_id', $next_statge)
                ->where('loan_product_id', $loan_product_id)->value('committee_id');
        } else {
            $committee_id = "AWAITING_APPROVAL";
        }

        return $committee_id;
    }


    function getBackStage($loan_id)
    {

        $loan = DB::table('loans')->where('id', $loan_id)->first();
        $product_id = $loan->loan_sub_product;
        $stage_id = $loan->stage_id;
        $loan_product_id = DB::table('loan_sub_products')->where('sub_product_id', $product_id)->value('id');
        $current_stage = DB::table('loan_stages')->where('committee_id', $stage_id)
            ->where('loan_product_id', $loan_product_id)->value('stage_id');

        $next_statge = $current_stage - 1;

        if (DB::table('loan_stages')->where('stage_id', $next_statge)
            ->where('loan_product_id', $loan_product_id)->exists()
        ) {

            $committee_id = DB::table('loan_stages')->where('stage_id', $next_statge)
                ->where('loan_product_id', $loan_product_id)->value('committee_id');
        } else {
            $committee_id = $stage_id;
        }

        return $committee_id;
    }



    public function commit()
    {
        $currentStage = Session::get('LoanStage');
        //dd($currentStage);
        $status = 'ONPROGRESS';
        if ($currentStage == 'ONPROGRESS') {
            $status = 'BRANCH COMMITTEE';
        }
        if ($currentStage == 'BRANCH COMMITTEE') {
            $status = 'CREDIT ANALYST';
        }
        if ($currentStage == 'CREDIT ANALYST') {
            $status = 'HQ COMMITTEE';
        }
        if ($currentStage == 'HQ COMMITTEE') {
            $status = 'CREDIT ADMINISTRATION';
        }
        if ($currentStage == 'CREDIT ADMINISTRATION') {
            $status = 'AWAITING DISBURSEMENT';
        }

        $stage= $this->getStage(Session::get('currentloanID'));

        if($stage=="AWAITING_APPROVAL"){

            $status=$stage;

        }else{
            $stage_id=$stage;
        }


        $data = [
            'principle'        => $this->principle,
            'interest'         => $this->interest,
            'tenure'           => $this->recommended ? $this->recommended_tenure : $this->tenure,
            'available_funds'  => $this->available_funds,
            'status'           => $status,
            'interest_method'  => $this->interest_method,
        ];

        // Check if stage_id is numeric
        if (is_numeric($stage_id)) {
            // Fetch the stage name from the database
            $stage_name = DB::table('committees')->where('id', $stage_id)->value('name');

            // Merge additional data into the existing array
            $data = array_merge($data, [
                'stage_id' => $stage_id,
                'stage'    => $stage_name,
            ]);
        }


        LoansModel::where('id', Session::get('currentloanID'))->update($data);
        Session::flash('loan_commit', 'The loan has been committed!');
        Session::flash('alert-class', 'alert-success');
        Session::put('currentloanID', null);
        Session::put('currentloanClient', null);
        $this->emit('currentloanID');
    }


    function returnBack(){

        $stage_id= $this->getBackStage(Session::get('currentloanID'));
        $data = [
            'principle' => $this->principle,
            'interest' => $this->interest,
            'tenure' => $this->recommended ? $this->recommended_tenure : $this->tenure,
            'available_funds' => $this->available_funds,
            'status' =>DB::table('committees')->where('id',$stage_id)->value('name'),
            'interest_method' => $this->interest_method,
            'stage_id'=>$stage_id,
            'stage'=>DB::table('committees')->where('id',$stage_id)->value('name'),
        ];

        LoansModel::where('id', Session::get('currentloanID'))->update($data);
        Session::flash('loan_commit', 'The loan has been committed!');
        Session::flash('alert-class', 'alert-success');
        Session::put('currentloanID', null);
        Session::put('currentloanClient', null);
        $this->emit('currentloanID');

    }

    function rejectLoan(){

        $data = [
            'principle' => $this->principle,
            'interest' => $this->interest,
            'tenure' => $this->recommended ? $this->recommended_tenure : $this->tenure,
            'available_funds' => $this->available_funds,
            'status' =>"REJECTED",
            'interest_method' => $this->interest_method,
        ];

        LoansModel::where('id', Session::get('currentloanID'))->update($data);
        Session::flash('loan_commit', 'The loan has been committed!');
        Session::flash('alert-class', 'alert-success');
        Session::put('currentloanID', null);
        Session::put('currentloanClient', null);
        $this->emit('currentloanID');
    }

    function approveLoanxx(){


        $loan = LoansModel::find(Session::get('currentloanID'));
        $status = $loan->status;

        $currentLoanID = Session::get('currentloanID');

        // Fetch all loan stages for the current loan ID
        $current_loans_stages = DB::table('current_loans_stages')
            ->where('loan_id', $currentLoanID)
            ->get();

        // Iterate through each loan stage
        foreach ($current_loans_stages as $stageIndex => $stage) {
            // Check if the current stage's status matches the loan's status
            if ($status == $stage->stage_name) {
                // Get the current stage ID
                $currentStageID = $stage->id;

                // Fetch the next record in current_loans_stages after the current stage
                $nextStage = DB::table('current_loans_stages')
                    ->where('loan_id', $currentLoanID)
                    ->where('id', '>', $currentStageID) // Find the next stage with a greater ID
                    ->orderBy('id', 'asc') // Ensure the next stage is the one with the next higher ID
                    ->first();

                // Check if the next stage exists
                if ($nextStage) {
                    $nextStageID = $nextStage->id; // Get the next stage ID

                    $stage_name = DB::table('current_loans_stages')
                        ->where('id', $nextStageID) // Find the next stage with a greater ID
                        ->orderBy('id', 'asc') // Ensure the next stage is the one with the next higher ID
                        ->value('stage_name');



                        //dd($currentLoanID,$status,auth()->user()->id);

/////////////////////HAPAAAAAAAAAAAAAAAAAAAA
///
                        if($stage->stage_type == 'Committee'){
                            DB::table('approvers_of_loans_stages')
                                ->where('loan_id', $currentLoanID)
                                ->where('stage_name', $status)
                                ->where('user_id', auth()->user()->id) // Find the next stage with a greater ID
                                ->update([
                                    'status' => 'APPROVED'
                                ]);

                            $currentLoanID = Session::get('currentloanID');

                            // Fetch all loan stages for the current loan ID
                            $current_loans_stages = DB::table('approvers_of_loans_stages')
                                ->where('loan_id', $currentLoanID)
                                ->where('stage_name', $status)
                                ->get();

                            // Fetch all loan stages where status is 'APPROVED' for the current loan ID
                            $approved_loans_stages = DB::table('approvers_of_loans_stages')
                                ->where('loan_id', $currentLoanID)
                                ->where('stage_name', $status)
                                ->where('status', 'APPROVED')
                                ->get();

                            // Check if the count of approved stages equals the count of all stages
                            if ($current_loans_stages->count() == $approved_loans_stages->count()) {
                                //dd($stage_name);
                                LoansModel::where('id', Session::get('currentloanID'))
                                    ->update([
                                        'status' => $stage_name
                                    ]);
                            } else {
                                // Not all loan stages are approved
                                // Handle this case accordingly
                            }



                        }else{
                            DB::table('approvers_of_loans_stages')
                                ->where('loan_id', $currentLoanID)
                                ->where('stage_name', $status)
                                ->where('user_id', null) // Find the next stage with a greater ID
                                ->update([
                                    'status' => 'APPROVED',
                                    'user_id' => auth()->user()->id,
                                    'user_name' => auth()->user()->name
                                ]);

                            LoansModel::where('id', Session::get('currentloanID'))
                                ->update([
                                    'status' => $stage_name
                                ]);

                        }


                    Session::flash('loan_commit', 'The loan has been committed!');
                    Session::flash('alert-class', 'alert-success');
                    //Session::put('currentloanID', null);
                    //Session::put('currentloanClient', null);
                    $this->emit('currentloanID');

                } else {
                    // hapaa echo "No next stage found.";
                }
            }
        }



        //LoansModel::where('id', Session::get('currentloanID'))->update($data);


    }


    function approveLoan($health) {
        // Retrieve the loan and its current status
        $loan = LoansModel::find(Session::get('currentloanID'));
        $status = $loan->status;
        $currentLoanID = Session::get('currentloanID');

        // Fetch all loan stages for the current loan ID
        $current_loans_stages = DB::table('current_loans_stages')
            ->where('loan_id', $currentLoanID)
            ->get();

        // Iterate through each loan stage
        foreach ($current_loans_stages as $stage) {
            // Check if the current stage's status matches the loan's status
            if ($status == $stage->stage_name) {
                // Get the current stage ID
                $currentStageID = $stage->id;

                // Fetch the next stage after the current one
                $nextStage = DB::table('current_loans_stages')
                    ->where('loan_id', $currentLoanID)
                    ->where('id', '>', $currentStageID) // Find the next stage with a greater ID
                    ->orderBy('id', 'asc') // Ensure the next stage is the one with the next higher ID
                    ->first();

                // Handle missing next stage (loan might be completed)
                if (!$nextStage) {

                    if ($stage->stage_type == 'Committee') {

                        // Fetch all stages for the current loan and check if all are approved
                        $allStages = DB::table('approvers_of_loans_stages')
                            ->where('loan_id', $currentLoanID)
                            ->where('stage_name', $status)
                            ->get();

                        $approvedStages = DB::table('approvers_of_loans_stages')
                            ->where('loan_id', $currentLoanID)
                            ->where('stage_name', $status)
                            ->where('status', 'APPROVED')
                            ->get();

                        DB::table('approvers_of_loans_stages')
                            ->where('loan_id', $currentLoanID)
                            ->where('stage_name', $status)
                            ->where('user_id', auth()->user()->id) // Example approver ID
                            ->update([
                                'status' => 'APPROVED'
                            ]);

                        if ($allStages->count() == ($approvedStages->count() + 1)) {
                            LoansModel::where('id', $currentLoanID)
                                ->update(['status' => 'ACCOUNTING']);
                        }else{

                        }
                    }else{
                        //dd('hh');
                        DB::table('approvers_of_loans_stages')
                            ->where('loan_id', $currentLoanID)
                            ->where('stage_name', $status)
                            ->where('user_id', null) // Example approver ID
                            ->update([
                                'status' => 'APPROVED',
                                'user_id' => auth()->user()->id,
                                'user_name' => auth()->user()->name

                            ]);

                        LoansModel::where('id', $currentLoanID)
                            ->update(['status' => 'ACCOUNTING']); // Mark loan as completed
                        Session::flash('loan_commit', 'Loan has been completed!');
                        Session::flash('alert-class', 'alert-success');
                    }




                    return;
                }

                // Get the next stage name
                $nextStageName = $nextStage->stage_name;

                // Handle 'Committee' stage type
                if ($stage->stage_type == 'Committee') {
                    // Update approval status for the current stage
                    DB::table('approvers_of_loans_stages')
                        ->where('loan_id', $currentLoanID)
                        ->where('stage_name', $status)
                        ->where('user_id', auth()->user()->id) // Example approver ID
                        ->update([
                            'status' => 'APPROVED'
                        ]);

                    // Fetch all stages for the current loan and check if all are approved
                    $allStages = DB::table('approvers_of_loans_stages')
                        ->where('loan_id', $currentLoanID)
                        ->where('stage_name', $status)
                        ->get();

                    $approvedStages = DB::table('approvers_of_loans_stages')
                        ->where('loan_id', $currentLoanID)
                        ->where('stage_name', $status)
                        ->where('status', 'APPROVED')
                        ->get();

                    // If all stages are approved, update loan status to the next stage
                    if ($allStages->count() == $approvedStages->count()) {
                        LoansModel::where('id', $currentLoanID)
                            ->update(['status' => $nextStageName]);
                    } else {
                        // If not all are approved, handle the case as necessary
                        // You could flash a message or take another action
                        Session::flash('loan_commit', 'Loan not fully approved yet.');
                        Session::flash('alert-class', 'alert-warning');
                    }
                } else {
                    // Handle non-Committee stages: approve stage and update loan status
                    DB::table('approvers_of_loans_stages')
                        ->where('loan_id', $currentLoanID)
                        ->where('stage_name', $status)
                        ->where('user_id', null) // Find approver-less stage
                        ->update([
                            'status' => 'APPROVED',
                            'user_id' => auth()->user()->id,
                            'user_name' => auth()->user()->name
                        ]);

                    // Move the loan to the next stage
                    LoansModel::where('id', $currentLoanID)
                        ->update(['status' => $nextStageName]);
                }

                LoansModel::where('id', $currentLoanID)
                    ->update(['heath' => $health]);

                // Flash success message and emit event
                Session::flash('loan_commit', 'The loan has been committed!');
                Session::flash('alert-class', 'alert-success');
                $this->emit('currentloanID');
            }
        }
    }



    public function updatedFutureInsteresAmount()
    {
        try {
            if ($this->futureInsteresAmount > $this->valueAmmount) {
                return $this->futureInsteresAmount = round($this->valueAmmount, 2);
            }
            return $this->futureInsteresAmount;
        } catch (\Exception $e) {
            Log::error('Error updating future interest amount: ' . $e->getMessage());
            return 0;
        }
    }

    public function closeLoan()
    {
        try {
            $loan_data = LoansModel::where('id', Session::get('currentloanID'))->first();
            if ($loan_data) {
                LoansModel::where('id', Session::get('currentloanID'))->update(['status' => "CLOSED"]);

                if ($this->future_interests) {
                    $this->handleFutureInterests($loan_data);
                } else {
                    $this->emit('refreshAssessment');
                }
            }
        } catch (\Exception $e) {
            Log::error('Error closing loan: ' . $e->getMessage());
            $this->errorMessage = 'Error closing loan. Please try again.';
        }
    }

    private function handleFutureInterests($loan_data)
    {
        try {
            $total_principle_amount = loans_schedules::where('loan_id', $loan_data->loan_id)
                ->where('installment_date', '>', Carbon::today()->format('Y-m-d'))
                ->sum('principle');

            $total_interest_amount = loans_schedules::where('loan_id', $loan_data->loan_id)
                ->where('installment_date', '>', Carbon::today()->format('Y-m-d'))
                ->sum('interest');

            $principle_collection_account = $this->getCollectionAccountDetails($loan_data->loan_sub_product, 'collection_account_loan_principle');
            $interest_collection_account = $this->getCollectionAccountDetails($loan_data->loan_sub_product, 'collection_account_loan_interest');

            general_ledger::create($this->createLedgerData($loan_data, $total_principle_amount, $total_interest_amount, $principle_collection_account, $interest_collection_account));

            loans_schedules::where('loan_id', $loan_data->loan_id)->where('installment_date', '>', Carbon::today()->format('Y-m-d'))->delete();

            $this->emit('refreshAssessment');
        } catch (\Exception $e) {
            Log::error('Error handling future interests: ' . $e->getMessage());
            $this->errorMessage = 'Error handling future interests. Please try again.';
        }
    }

    private function getCollectionAccountDetails($loan_sub_product, $account_type)
    {
        try {
            $product = Loan_sub_products::where('sub_product_id', $loan_sub_product)->first();
            return $product ? $product[$account_type] : null;
        } catch (\Exception $e) {
            Log::error('Error getting collection account details: ' . $e->getMessage());
            return null;
        }
    }

    private function createLedgerData($loan_data, $total_principle_amount, $total_interest_amount, $principle_collection_account, $interest_collection_account)
    {
        try {
            return [
                [
                    'gl_code' => $principle_collection_account,
                    'description' => 'LOAN PRINCIPLE RECEIVED ON CLOSURE',
                    'narrative' => 'LOAN PRINCIPLE RECEIVED ON CLOSURE',
                    'debit' => $total_principle_amount,
                    'credit' => 0,
                    'branch' => $loan_data->branch,
                    'date' => Carbon::now(),
                    'teller' => Auth::user()->id,
                ],
                [
                    'gl_code' => $interest_collection_account,
                    'description' => 'LOAN INTEREST RECEIVED ON CLOSURE',
                    'narrative' => 'LOAN INTEREST RECEIVED ON CLOSURE',
                    'debit' => $total_interest_amount,
                    'credit' => 0,
                    'branch' => $loan_data->branch,
                    'date' => Carbon::now(),
                    'teller' => Auth::user()->id,
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Error creating ledger data: ' . $e->getMessage());
            return [];
        }
    }




    public function approve()
    {


        // CREATE LOAN ACCOUNT
        $loan =  LoansModel::where('id', session()->get('currentloanID'))->first();


        // $client_email = MembersModel::where('client_number', $loan->member_number)->first();
        // // $client_name = $client_email->first_name . ' ' . $client_email->middle_name . ' ' . $client_email->last_name;
        // // $officer_phone_number = Employee::where('id', $client_email->loan_officer)->value('email');

        //        Mail::to($client_email->email)->send(new LoanProgress($officer_phone_number,$client_name,'Your loan has been approved! We are now finalizing the disbursement process'));
        if (LoansModel::where('id', session()->get('currentloanID'))->value('loan_status') == "RESTRUCTURED") {

            loans_schedules::where('loan_id', $loan->restructure_loanId)->where('completion_status', 'ACTIVE')->update([
                'completion_status' => 'CLOSED',
                'account_status' => 'CLOSED'
            ]);


            //  LoansModel::where('id',session()->get('currentloanID'))->update(['status'=>"CLOSED"]);
            // source account number

            $next_due_date = Carbon::now()->toDateTimeString();

            foreach ($this->table as $installment) {
                $next_due_date = date('Y-m-d', strtotime($next_due_date . ' +30 days'));
                $product = new loans_schedules;
                $product->loan_id = $loan->loan_id;
                $product->installment = $installment['Payment'];
                $product->interest = $installment['Interest'];
                $product->principle = $installment['Principle'];
                $product->balance = $installment['balance'];
                $product->bank_account_number = $loan->bank1;
                $product->completion_status = "ACTIVE";
                $product->account_status = "ACTIVE";
                $product->installment_date = $next_due_date;
                // $product->save();
            }

            foreach ($this->tablefooter as $installment) {
                $next_due_date = date('Y-m-d', strtotime($next_due_date . ' +30 days'));
                $product = new loans_summary;
                $product->loan_id = $loan->loan_id;
                $product->installment = $installment['Payment'];
                $product->interest = $installment['Interest'];
                $product->principle = $installment['Principle'];
                $product->balance = $installment['balance'];
                $product->bank_account_number = $loan->bank1;
                $product->completion_status = "ACTIVE";
                $product->account_status = "ACTIVE";
                //   $product->save();
            }



            LoansModel::where('id', Session::get('currentloanID'))->update([
                'status' => 'AWAITING DISBURSEMENT',
                //
            ]);
        } elseif (LoansModel::where('id', session()->get('currentloanID'))->value('loan_status') == "TOPUP") {
            // top up process here  TOPUP

            $loanValues = LoansModel::where('id', session()->get('currentloanID'))->where('loan_status', 'TOPUP')->first();


            //principle
            $prev_loan = $loanValues->restructure_loanId;
            // close loan
            LoansModel::where('loan_id', $loanValues->restructure_loanId)->update(['status' => "CLOSED"]);

            // close installment
            loans_schedules::where('loan_id', $prev_loan)->update(['completion_status' => 'CLOSED']);

            $next_due_date = Carbon::now()->toDateTimeString();

            foreach ($this->table as $installment) {
                $next_due_date = date('Y-m-d', strtotime($next_due_date . ' +30 days'));
                $product = new loans_schedules;
                $product->loan_id = $loan->loan_id;
                $product->installment = $installment['Payment'];
                $product->interest = $installment['Interest'];
                $product->principle = $installment['Principle'];
                $product->balance = $installment['balance'];
                $product->bank_account_number = $loan->bank1;
                $product->completion_status = "ACTIVE";
                $product->account_status = "ACTIVE";
                $product->installment_date = $next_due_date;
                //   $product->save();
            }

            foreach ($this->tablefooter as $installment) {
                $next_due_date = date('Y-m-d', strtotime($next_due_date . ' +30 days'));
                $product = new loans_summary;
                $product->loan_id = $loan->loan_id;
                $product->installment = $installment['Payment'];
                $product->interest = $installment['Interest'];
                $product->principle = $installment['Principle'];
                $product->balance = $installment['balance'];
                $product->bank_account_number = $loan->bank1;
                $product->completion_status = "ACTIVE";
                $product->account_status = "ACTIVE";
                //   $product->save();
            }



            LoansModel::where('id', Session::get('currentloanID'))->update([
                'status' => 'AWAITING DISBURSEMENT',
                //
            ]);





            Session::flash('loan_commit', 'The loan has been Approved!');
            Session::flash('alert-class', 'alert-success');

            Session::put('currentloanID', null);
            Session::put('currentloanClient', null);
            $this->emit('currentloanID');
        } else {

            $next_due_date = Carbon::now()->toDateTimeString();

            foreach ($this->table as $installment) {
                $next_due_date = date('Y-m-d', strtotime($next_due_date . ' +30 days'));
                $product = new loans_schedules;
                $product->loan_id = $loan->loan_id;
                $product->installment = $installment['Payment'];
                $product->interest = $installment['Interest'];
                $product->principle = $installment['Principle'];
                $product->balance = $installment['balance'];
                $product->bank_account_number = $loan->bank1;
                $product->completion_status = "ACTIVE";
                $product->account_status = "ACTIVE";
                $product->installment_date = $next_due_date;
                //  $product->save();
            }

            foreach ($this->tablefooter as $installment) {
                $next_due_date = date('Y-m-d', strtotime($next_due_date . ' +30 days'));
                $product = new loans_summary;
                $product->loan_id = $loan->loan_id;
                $product->installment = $installment['Payment'];
                $product->interest = $installment['Interest'];
                $product->principle = $installment['Principle'];
                $product->balance = $installment['balance'];
                $product->bank_account_number = $loan->bank1;
                $product->completion_status = "ACTIVE";
                $product->account_status = "ACTIVE";
                //   $product->save();
            }

            $currentStage = Session::get('LoanStage');
            //dd($currentStage);
            $status = 'ONPROGRESS';
            if ($currentStage == 'ONPROGRESS') {
                $status = 'BRANCH COMMITTEE';
            }
            if ($currentStage == 'BRANCH COMMITTEE') {
                $status = 'CREDIT ANALYST';
            }
            if ($currentStage == 'CREDIT ANALYST') {
                $status = 'HQ COMMITTEE';
            }
            if ($currentStage == 'HQ COMMITTEE') {
                $status = 'CREDIT ADMINISTRATION';
            }
            if ($currentStage == 'CREDIT ADMINISTRATION') {
                $status = 'ACTIVE';
            }

            LoansModel::where('id', Session::get('currentloanID'))->update([
                'status' => $status

            ]);
        }

        Session::flash('loan_commit', 'The loan has been Approved!');
        Session::flash('alert-class', 'alert-success');

        Session::put('currentloanID', null);
        Session::put('currentloanClient', null);
        $this->emit('currentloanID');
    }

    public function reject()
    {

        $currentStage = Session::get('LoanStage');
        $status = 'ONPROGRESS';
        if ($currentStage == 'PROGRESS') {
            $status = 'ONPROGRESS';
        }
        if ($currentStage == 'BRANCH COMMITTEE') {
            $status = 'ONPROGRESS';
        }
        if ($currentStage == 'CREDIT ANALYST') {
            $status = 'ONPROGRESS';
        }
        if ($currentStage == 'HQ COMMITTEE') {
            $status = 'ONPROGRESS';
        }
        if ($currentStage == 'CREDIT ADMINISTRATION') {
            $status = 'ONPROGRESS';
        }


        LoansModel::where('id', Session::get('currentloanID'))->update([
            'status' => $status
        ]);
        MembersModel::where('id', DB::table('loans')->where('id', Session::get('currentloanID'))->value('client_id'))->update([
            'client_status' => $status
        ]);

        Session::flash('loan_commit', 'The loan has been Rejected!');
        Session::flash('alert-class', 'alert-danger');

        Session::put('currentloanID', null);
        Session::put('currentloanClient', null);
        $this->emit('currentloanID');
    }

    public function disburse()
    {



        $this->emit('approvalAndDisburse', Session::get('currentloanID'));

        LoansModel::where('id', Session::get('currentloanID'))->update([
            'status' => 'AWAITING DISBURSEMENT',

        ]);

        $member_number = DB::table('loans')->where('id', Session::get('currentloanID'))->value('client_number');

        DB::table('clients')->where('client_number', $member_number)->update([
            'client_status' => 'ACTIVE',
        ]);


        Session::flash('loan_commit', 'The loan has been Approved!');
        Session::flash('alert-class', 'alert-success');

        Session::put('currentloanID', null);
        Session::put('currentloanClient', null);
        $this->emit('currentloanID');
    }

    /**
     * Check if loan has any exceptions that would prevent approval
     */
    public function hasExceptions()
    {
        if (!$this->exceptionData || !isset($this->exceptionData['summary'])) {
            return true; // Assume there are exceptions if data is not available
        }
        
        return $this->exceptionData['summary']['requires_exception'];
    }

    /**
     * Check if loan can be approved without exceptions
     */
    public function canApproveWithoutExceptions()
    {
        if (!$this->exceptionData || !isset($this->exceptionData['summary'])) {
            return false; // Cannot approve if data is not available
        }
        
        return $this->exceptionData['summary']['can_approve'];
    }

    /**
     * Add new settlement
     */
    public function addSettlement()
    {
        try {
            // Validate the form data
            $validation = $this->settlementService->validateSettlement($this->settlementForm);
            
            if (!$validation['is_valid']) {
                $this->errorMessage = implode(', ', $validation['errors']);
                return;
            }

            // Get next available slot
            $availableSlots = $this->settlementService->getAvailableSlots();
            if (empty($availableSlots)) {
                $this->errorMessage = 'Maximum number of settlements reached (5)';
                return;
            }

            $settlementId = $availableSlots[0];
            
            // Save the settlement
            $success = $this->settlementService->saveSettlement($settlementId, $this->settlementForm);
            
            if ($success) {
                // Refresh settlement data
                $this->settlementData = $this->settlementService->getSettlementData();
                
                // Reset form
                $this->resetSettlementForm();
                
                $this->errorMessage = null;
            } else {
                $this->errorMessage = 'Error saving settlement. Please try again.';
            }
        } catch (\Exception $e) {
            Log::error('Error adding settlement: ' . $e->getMessage());
            $this->errorMessage = 'Error adding settlement. Please try again.';
        }
    }

    /**
     * Edit existing settlement
     */
    public function editSettlement($settlementId)
    {
        try {
            $settlement = collect($this->settlementData['settlements'])->firstWhere('id', $settlementId);
            
            if ($settlement) {
                $this->settlementForm = [
                    'institution' => $settlement['institution'],
                    'account' => $settlement['account'],
                    'amount' => $settlement['amount']
                ];
                $this->editingSettlementId = $settlementId;
                $this->showSettlementForm = true;
            }
        } catch (\Exception $e) {
            Log::error('Error editing settlement: ' . $e->getMessage());
            $this->errorMessage = 'Error loading settlement data. Please try again.';
        }
    }

    /**
     * Update existing settlement
     */
    public function updateSettlement()
    {
        try {
            if (!$this->editingSettlementId) {
                $this->errorMessage = 'No settlement selected for editing';
                return;
            }

            // Validate the form data
            $validation = $this->settlementService->validateSettlement($this->settlementForm);
            
            if (!$validation['is_valid']) {
                $this->errorMessage = implode(', ', $validation['errors']);
                return;
            }

            // Update the settlement
            $success = $this->settlementService->saveSettlement($this->editingSettlementId, $this->settlementForm);
            
            if ($success) {
                // Refresh settlement data
                $this->settlementData = $this->settlementService->getSettlementData();
                
                // Reset form
                $this->resetSettlementForm();
                
                $this->errorMessage = null;
            } else {
                $this->errorMessage = 'Error updating settlement. Please try again.';
            }
        } catch (\Exception $e) {
            Log::error('Error updating settlement: ' . $e->getMessage());
            $this->errorMessage = 'Error updating settlement. Please try again.';
        }
    }

    /**
     * Delete settlement
     */
    public function deleteSettlement($settlementId)
    {
        try {
            $success = $this->settlementService->deleteSettlement($settlementId);
            
            if ($success) {
                // Refresh settlement data
                $this->settlementData = $this->settlementService->getSettlementData();
                
                // Reset form if editing this settlement
                if ($this->editingSettlementId == $settlementId) {
                    $this->resetSettlementForm();
                }
                
                $this->errorMessage = null;
            } else {
                $this->errorMessage = 'Error deleting settlement. Please try again.';
            }
        } catch (\Exception $e) {
            Log::error('Error deleting settlement: ' . $e->getMessage());
            $this->errorMessage = 'Error deleting settlement. Please try again.';
        }
    }

    /**
     * Reset settlement form
     */
    public function resetSettlementForm()
    {
        $this->settlementForm = [
            'institution' => '',
            'account' => '',
            'amount' => 0
        ];
        $this->editingSettlementId = null;
        $this->showSettlementForm = false;
    }

    /**
     * Cancel settlement form
     */
    public function cancelSettlementForm()
    {
        $this->resetSettlementForm();
        $this->errorMessage = null;
    }

    /**
     * Get settlement summary
     */
    public function getSettlementSummary()
    {
        return $this->settlementService->getSettlementSummary();
    }

    /**
     * Get settlement statistics
     */
    public function getSettlementStatistics()
    {
        // Implementation for getting settlement statistics
        return [
            'total_settlements' => 0,
            'total_amount' => 0,
            'average_amount' => 0
        ];
    }

    // Action Methods for Approval Workflow
    public function sendForApproval()
    {
        try {
            $this->isProcessing = true;
            
            $user = Auth::user();
            $loanId = session('currentloanID');
            $loan = LoansModel::find($loanId);

            if (!$loan) {
                throw new \Exception('Loan not found');
            }

            // Update loan status and assessment data
            $loan->update([
                'status' => 'PENDING_APPROVAL',
                'monthly_installment' => $this->monthlyInstallmentValue ?? 0,
                'assessment_data' => json_encode([
                    'approved_loan_value' => $this->approved_loan_value,
                    'approved_term' => $this->approved_term,
                    'monthly_installment' => $this->monthlyInstallmentValue,
                    'assessed_by' => $user->id,
                    'assessed_at' => now(),
                    'assessment_type' => 'standard'
                ])
            ]);

            // Create approval record
            $approvalData = [
                'institution_id' => $user->institution_id ?? 1,
                'process_name' => 'Loan Approval',
                'process_description' => "Loan approval request for client {$loan->client_number} - Amount: " . number_format($this->approved_loan_value, 2) . " TZS",
                'approval_process_description' => 'Loan assessment completed and ready for approval',
                'process_code' => 'LOAN_DISB',
                'process_id' => $loanId,
                'process_status' => 'PENDING',
                'user_id' => $user->id,
                'team_id' => $user->currentTeam->id ?? "",
                'created_at' => now(),
                'updated_at' => now()
            ];

            DB::table('approvals')->insert($approvalData);

            // Save tab state and mark as completed
            $this->saveAssessmentTabState();

            // Set action completion states
            $this->actionCompleted = true;
            $this->actionType = 'approval_sent';
            $this->actionMessage = 'Loan has been sent for approval successfully.';
            $this->showActionButtons = false;
            $this->isProcessing = false;

            session()->flash('message', 'Loan sent for approval successfully.');
            
        } catch (\Exception $e) {
            $this->isProcessing = false;
            Log::error('Error sending for approval: ' . $e->getMessage());
            session()->flash('error', 'Error sending for approval: ' . $e->getMessage());
        }
    }

    /**
     * Send for approval with exceptions
     */
    public function sendForApprovalWithExceptions()
    {
        try {
            $this->isProcessing = true;
            
            $user = Auth::user();
            $loanId = session('currentloanID');
            $loan = LoansModel::find($loanId);

            if (!$loan) {
                throw new \Exception('Loan not found');
            }

            // Update loan status and assessment data
            $loan->update([
                'status' => 'PENDING_EXCEPTION_APPROVAL',
                'monthly_installment' => $this->monthlyInstallmentValue ?? 0,
                'assessment_data' => json_encode([
                    'approved_loan_value' => $this->approved_loan_value,
                    'approved_term' => $this->approved_term,
                    'monthly_installment' => $this->monthlyInstallmentValue,
                    'exception_data' => $this->exceptionData,
                    'assessed_by' => $user->id,
                    'assessed_at' => now(),
                    'assessment_type' => 'with_exceptions'
                ])
            ]);

            // Create approval record for exception approval
            $approvalData = [
                'institution_id' => $user->institution_id ?? 1,
                'process_name' => 'Loan Exception Approval',
                'process_description' => "Loan exception approval request for client {$loan->client_number} - Amount: " . number_format($this->approved_loan_value, 2) . " TZS",
                'approval_process_description' => 'Loan assessment completed with exceptions requiring approval',
                'process_code' => 'LOAN_DISB',
                'process_id' => $loanId,
                'process_status' => 'PENDING',
                'user_id' => $user->id,
                'team_id' => $user->currentTeam->id ?? "",
                'created_at' => now(),
                'updated_at' => now()
            ];

            DB::table('approvals')->insert($approvalData);

            // Save tab state and mark as completed
            $this->saveAssessmentTabState();

            // Set action completion states
            $this->actionCompleted = true;
            $this->actionType = 'exception_approval_sent';
            $this->actionMessage = 'Loan has been sent for exception approval successfully.';
            $this->showActionButtons = false;
            $this->isProcessing = false;

            session()->flash('message', 'Loan sent for exception approval successfully.');
            
        } catch (\Exception $e) {
            $this->isProcessing = false;
            Log::error('Error sending for exception approval: ' . $e->getMessage());
            session()->flash('error', 'Error sending for exception approval: ' . $e->getMessage());
        }
    }

    /**
     * Decline loan
     */
    public function declineLoan()
    {
        try {
            $this->isProcessing = true;
            
            $user = Auth::user();
            $loanId = session('currentloanID');
            $loan = LoansModel::find($loanId);

            if (!$loan) {
                throw new \Exception('Loan not found');
            }

            // Update loan status to rejected
            $loan->update([
                'status' => 'REJECTED',
                'assessment_data' => json_encode([
                    'rejected_at' => now(),
                    'rejected_by' => $user->id,
                    'rejection_reason' => 'Failed to meet loan criteria',
                    'exception_data' => $this->exceptionData,
                    'current_assessment' => [
                        'approved_loan_value' => $this->approved_loan_value,
                        'approved_term' => $this->approved_term,
                        'monthly_installment' => $this->monthlyInstallmentValue
                    ]
                ])
            ]);

            // Set action completion states
            $this->actionCompleted = true;
            $this->actionType = 'loan_declined';
            $this->actionMessage = 'Loan has been declined due to not meeting criteria.';
            $this->isProcessing = false;

            session()->flash('message', 'Loan has been declined.');
            
        } catch (\Exception $e) {
            $this->isProcessing = false;
            Log::error('Error declining loan: ' . $e->getMessage());
            session()->flash('error', 'Error declining loan: ' . $e->getMessage());
        }
    }

    /**
     * Save assessment tab state using the tab state service
     */
    protected function saveAssessmentTabState()
    {
        try {
            $loanId = session('currentloanID');
            if (!$loanId) {
                return;
            }

            // Get the tab state service
            $tabStateService = app(\App\Services\LoanTabStateService::class);
            
            // Save assessment tab state
            $tabStateService->saveTabState($loanId, 'assessment', [
                'approved_loan_value' => $this->approved_loan_value,
                'approved_term' => $this->approved_term,
                'monthly_installment' => $this->monthlyInstallmentValue,
                'take_home' => $this->take_home,
                'exception_data' => $this->exceptionData,
                'assessed_at' => now(),
                'assessed_by' => auth()->id()
            ]);

            // Update completion status
            $completedTabs = $tabStateService->getCompletedTabs($loanId);
            $tabStateService->saveTabCompletionStatus($loanId, $completedTabs);

            // Emit event to parent component
            $this->emit('tabCompleted', 'assessment');

        } catch (\Exception $e) {
            Log::error('Error saving assessment tab state: ' . $e->getMessage());
        }
    }

    public function saveAsDraft()
    {
        try {
            $this->isProcessing = true;
            
            $loanId = session('currentloanID');
            $user = Auth::user();
            
            // Validate loan exists
            $loan = LoansModel::find($loanId);
            if (!$loan) {
                throw new \Exception('Loan not found');
            }

            // Save current assessment as draft with comprehensive data
            $loan->update([
                'status' => 'DRAFT',
                'approved_loan_value' => $this->approved_loan_value,
                'approved_term' => $this->approved_term,
                'monthly_installment' => $this->monthlyInstallmentValue,
                'take_home' => $this->take_home,
                'collateral_value' => $this->collateral_value,
                'collateral_type' => $this->collateral_type,
                'available_funds' => $this->available_funds,
                'principle' => $this->principle,
                'interest' => $this->interest,
                'tenure' => $this->tenure,
                'interest_method' => $this->interest_method,
                'assessment_data' => json_encode([
                    'take_home' => $this->take_home,
                    'approved_loan_value' => $this->approved_loan_value,
                    'approved_term' => $this->approved_term,
                    'monthly_installment' => $this->monthlyInstallmentValue,
                    'collateral_value' => $this->collateral_value,
                    'collateral_type' => $this->collateral_type,
                    'available_funds' => $this->available_funds,
                    'coverage' => $this->coverage,
                    'monthly_sales' => $this->monthly_sales,
                    'gross_profit' => $this->gross_profit,
                    'net_profit' => $this->net_profit,
                    'recommended' => $this->recommended,
                    'recommended_tenure' => $this->recommended_tenure,
                    'selectedLoan' => $this->selectedLoan,
                    'exception_data' => $this->exceptionData,
                    'credit_score_data' => $this->creditScoreData,
                    'client_info_data' => $this->clientInfoData,
                    'product_params_data' => $this->productParamsData,
                    'nbc_loans_data' => $this->nbcLoansData,
                    'settlement_data' => $this->settlementData,
                    'assessed_at' => now(),
                    'assessed_by' => $user->id
                ])
            ]);

            // Save tab state
            $this->saveAssessmentTabState();

            // Set action completion states
            $this->actionCompleted = true;
            $this->actionType = 'draft_saved';
            $this->actionMessage = 'Assessment has been saved as draft successfully.';
            $this->isProcessing = false;

            session()->flash('message', 'Assessment saved as draft successfully.');
            
        } catch (\Exception $e) {
            $this->isProcessing = false;
            Log::error('Error saving as draft: ' . $e->getMessage());
            session()->flash('error', 'Error saving as draft: ' . $e->getMessage());
        }
    }

    public function modifyLoanParameters()
    {
        try {
            // This method would typically open a modal or redirect to edit form
            // For now, we'll just show a message
            session()->flash('info', 'Please modify the loan parameters above and save the changes.');
            
        } catch (\Exception $e) {
            Log::error('Error modifying loan parameters: ' . $e->getMessage());
            session()->flash('error', 'Failed to modify loan parameters. Please try again.');
        }
    }

    public function requestAdditionalDocuments()
    {
        try {
            $this->isProcessing = true;
            
            $loanId = session('currentloanID');
            $user = Auth::user();
            
            // Validate loan exists
            $loan = LoansModel::find($loanId);
            if (!$loan) {
                throw new \Exception('Loan not found');
            }

            // Update loan status to request documents
            $loan->update([
                'status' => 'DOCUMENTS_REQUESTED',
                'assessment_data' => json_encode([
                    'documents_requested_at' => now(),
                    'requested_by' => $user->id,
                    'current_assessment' => [
                        'approved_loan_value' => $this->approved_loan_value,
                        'approved_term' => $this->approved_term,
                        'monthly_installment' => $this->monthlyInstallmentValue,
                        'exception_data' => $this->exceptionData
                    ]
                ])
            ]);

            Log::info('Additional documents requested for loan', [
                'loan_id' => $loanId,
                'user_id' => $user->id
            ]);

            session()->flash('success', 'Additional documents have been requested for this loan.');
            $this->emit('documentsRequested', $loanId);
            
        } catch (\Exception $e) {
            Log::error('Error requesting additional documents: ' . $e->getMessage());
            session()->flash('error', 'Failed to request additional documents. Please try again.');
        }
    }

    // Helper methods for approval workflow
    public function canSendForApproval()
    {
        $canApprove = isset($this->exceptionData['summary']['can_approve']) && 
               $this->exceptionData['summary']['can_approve'] === true;
        
        Log::info('canSendForApproval check', [
            'exceptionData_summary' => $this->exceptionData['summary'] ?? 'not_set',
            'can_approve' => $canApprove
        ]);
        
        return $canApprove;
    }

    public function canSendForExceptionApproval()
    {
        $requiresException = isset($this->exceptionData['summary']['requires_exception']) && 
               $this->exceptionData['summary']['requires_exception'] === true;
        
        Log::info('canSendForExceptionApproval check', [
            'exceptionData_summary' => $this->exceptionData['summary'] ?? 'not_set',
            'requires_exception' => $requiresException
        ]);
        
        return $requiresException;
    }

    public function shouldDeclineLoan()
    {
        $shouldDecline = !$this->canSendForApproval() && !$this->canSendForExceptionApproval();
        
        Log::info('shouldDeclineLoan check', [
            'can_send_for_approval' => $this->canSendForApproval(),
            'can_send_for_exception_approval' => $this->canSendForExceptionApproval(),
            'should_decline' => $shouldDecline
        ]);
        
        return $shouldDecline;
    }

    /**
     * Check if assessment is completed and emit tab completion event
     */
    public function checkAssessmentCompletion()
    {
        try {
            $loanId = session('currentloanID');
            if (!$loanId) {
                return;
            }

            $tabStateService = app(\App\Services\LoanTabStateService::class);
            $isCompleted = $tabStateService->isTabCompleted($loanId, 'assessment');

            if ($isCompleted) {
                $this->emit('tabCompleted', 'assessment');
            }

            return $isCompleted;
        } catch (\Exception $e) {
            Log::error('Error checking assessment completion: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if assessment is completed
     */
    public function isAssessmentCompleted()
    {
        try {
            $loanId = session('currentloanID');
            if (!$loanId) {
                return false;
            }

            $tabStateService = app(\App\Services\LoanTabStateService::class);
            return $tabStateService->isTabCompleted($loanId, 'assessment');
        } catch (\Exception $e) {
            Log::error('Error checking if assessment is completed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Mount method to check completion status on component load
     */
    public function mount()
    {
        // Check if assessment is completed and emit event if needed
        $this->checkAssessmentCompletion();
        
        // Ensure exceptionData is always initialized
        if (!isset($this->exceptionData) || !is_array($this->exceptionData)) {
            $this->exceptionData = [
                'loan_amount' => ['status' => 'PENDING', 'is_exceeded' => false],
                'term' => ['status' => 'PENDING', 'is_exceeded' => false],
                'credit_score' => ['status' => 'PENDING', 'is_exceeded' => false],
                'salary_installment' => ['status' => 'PENDING', 'is_exceeded' => false],
                'collateral' => ['status' => 'PENDING', 'is_exceeded' => false],
                'summary' => [
                    'overall_status' => 'PENDING',
                    'can_approve' => false,
                    'requires_exception' => false,
                    'total_checks' => 0,
                    'passed_checks' => 0,
                    'failed_checks' => 0
                ]
            ];
        }
    }

    /**
     * Get detailed collateral information for the loan
     */
    public function getCollateralDetails($loan_id)
    {
        try {
            if (!$loan_id) {
                return [];
            }
            
            $collateralDetails = [];
            
            // Get guarantors and their collaterals
            $guarantors = DB::table('loan_guarantors')
                ->where('loan_id', $loan_id)
                ->where('status', 'active')
                ->get();
            
            foreach ($guarantors as $guarantor) {
                $collaterals = DB::table('loan_collaterals')
                    ->where('loan_guarantor_id', $guarantor->id)
                    ->where('status', 'active')
                    ->get();
                
                foreach ($collaterals as $collateral) {
                    $collateralInfo = [
                        'id' => $collateral->id,
                        'type' => $collateral->collateral_type,
                        'amount' => (float)$collateral->collateral_amount,
                        'locked_amount' => (float)$collateral->locked_amount,
                        'available_amount' => (float)$collateral->available_amount,
                        'guarantor_type' => $guarantor->guarantor_type,
                        'guarantor_id' => $guarantor->id
                    ];
                    
                    // Add account information for financial collaterals
                    if (in_array($collateral->collateral_type, ['savings', 'deposits', 'shares']) && $collateral->account_id) {
                        $account = DB::table('accounts')->find($collateral->account_id);
                        if ($account) {
                            $collateralInfo['account_number'] = $account->account_number;
                            $collateralInfo['account_balance'] = (float)$account->balance;
                        }
                    }
                    
                    // Add physical collateral details
                    if ($collateral->collateral_type === 'physical') {
                        $collateralInfo['description'] = $collateral->physical_collateral_description;
                        $collateralInfo['location'] = $collateral->physical_collateral_location;
                        $collateralInfo['owner_name'] = $collateral->physical_collateral_owner_name;
                        $collateralInfo['value'] = (float)$collateral->physical_collateral_value;
                    }
                    
                    $collateralDetails[] = $collateralInfo;
                }
            }
            
            return $collateralDetails;
        } catch (\Exception $e) {
            Log::error('Error getting collateral details: ' . $e->getMessage());
            return [];
        }
    }
}