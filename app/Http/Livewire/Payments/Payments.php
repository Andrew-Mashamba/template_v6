<?php

namespace App\Http\Livewire\Payments;

use Livewire\Component;
use App\Services\NbcBillsPaymentService;
use Illuminate\Support\Facades\Log;

use App\Services\NbcPayments\NbcLookupService;

use App\Services\NbcPayments\NbcPaymentService;
use App\Services\NbcPayments\FspDetailsService;
use App\Services\NbcPayments\LukuService;
use App\Services\NbcPayments\PaymentProcessorService;
use App\Models\AccountsModel;

class Payments extends Component
{

    public $billers = [];
    public $selectedSpCode = null;
    public $billRef = '';
    public $billDetails = null;
    public $amount = '';
    public $paymentResponse = null;
    public $paymentStatus = null;
    public $transactions = [];

    public $inquiryResult;


    public string $lookupType = 'bank-to-bank';
    public array $form = [
        'accountNumber' => '',
        'bankCode' => '',
        'phoneNumber' => '',
        'walletProvider' => '',
        'merchantId' => '',
        'debitAccount' => '',
        'amount' => '',
        'debitAccountCategory' => 'PERSON'
    ];
    public $response = null;
    public $errorMessage = null;




    // Form fields
    public $transferType = 'bank'; // 'bank' or 'wallet'
    public $beneficiaryAccount;
    public $bankCode;
    public $walletProvider;
    public $phoneNumber;
    public $debitAccount;

    public $remarks;

    // State variables
    public $isProcessing = false;
    public $successMessage = '';

    public $engineRef = '';
    public $beneficiaryName = '';
    public $currentPhase = 'form'; // 'form', 'verify', 'complete'
    public $lookupData = [];
    public $meterNumber;
    public $accountNumber;
    //public $amount;
    public $lookupResult;
    public $paymentResult;

    public $step = 1; // 1: Verification, 2: Payment, 3: Confirmation
    public $controlNumber;
    public $accountNo;
    public $currency = 'TZS';
    public $accounts = [];
    public $verificationResult;
    public $paymentData = [];
    public $paymentType = 'postpaid'; // or 'prepaid'
    //public $isProcessing = false;
    public $transactionResult;
    public $error;

    // Sidebar navigation support
    public $selectedMenuItem = 1; // Default to Dashboard
    public $selectedPaymentType = 'money_transfer'; // Default to Money Transfer
    public $search = '';

    public $availableBanks = [
        'NMIBTZTZ' => 'NMB Bank',
        'CORUTZTZ' => 'CRDB Bank',
        'DTKETZTZ' => 'DTB Bank'
    ];
    public $availableWallets = [
        'VMCASHIN' => 'M-Pesa',
        'AMCASHIN' => 'Airtel Money',
        'APCASHIN' => 'Azam Pesa'
    ];

    protected $rules = [
        'transferType' => 'required|in:bank,wallet',
        'beneficiaryAccount' => 'required_if:transferType,bank|string|nullable',
        'bankCode' => 'required_if:transferType,bank|string|nullable',
        'walletProvider' => 'required_if:transferType,wallet|string|nullable',
        'phoneNumber' => 'required_if:transferType,wallet|string|nullable',
        'debitAccount' => 'required|string',
        'amount' => 'required|numeric|min:1000',
        'remarks' => 'required|string|max:50'
    ];

    protected $messages = [
        'amount.min' => 'Minimum transfer amount is 1000 TZS.',
        'beneficiaryAccount.required_if' => 'Account number is required for bank transfers.',
        'phoneNumber.required_if' => 'Phone number is required for wallet transfers.',
    ];



        public function mount()
    {
        $this->fetchFsp();
        $this->fetchBillers();
        //$this->accounts = AccountsModel::where('user_id', auth()->id())->get();
    }

    public function fetchFsp()
    {
        $fspService = new FspDetailsService();


        Log::info('Initializing FSP Details fetch process...');

        Log::debug('FspDetailsService instance resolved via service container.');

        $result = $fspService->fetchFspDetails();

        if (isset($result['error'])) {
            Log::error('Failed to retrieve FSP details.', [
                'message' => $result['message'] ?? 'Unknown error',
                'details' => $result['details'] ?? [],
            ]);
            // Handle error accordingly
        } else {
            Log::info('FSP details retrieved successfully.', [
                'fsp_count' => count($result),
                'fsp_sample' => array_slice($result, 0, 2), // preview first 2 items
            ]);
            // Process FSP list
        }


    }

    public function fetchBillers()
    {
        try {
            $service = new NbcBillsPaymentService();
            $this->billers = $service->getBillers();

        } catch (\Throwable $e) {
            Log::error('Failed to fetch billers', ['error' => $e->getMessage()]);
        }
    }

    public function selectBiller($spCode)
    {
        $this->selectedSpCode = $spCode;
        $this->billDetails = null;
        $this->paymentResponse = null;
        $this->paymentStatus = null;
    }

    public function inquireBill()
    {
        try {
            $service = new NbcBillsPaymentService();
            $payload = [
                'spCode' => $this->selectedSpCode,
                'billRef' => $this->billRef,
                'userId' => 'USER101',
                'branchCode' => '015',
                'channelRef' => now()->timestamp,
                'extraFields' => [],
            ];

            $this->billDetails = $service->inquireDetailedBill($payload);
$this->inquiryResult = $this->billDetails;
        } catch (\Throwable $e) {
            Log::error('Bill inquiry failed', ['error' => $e->getMessage()]);
        }
    }

    public function makePayment()
    {
        try {
            $service = new NbcBillsPaymentService();
            $channelRef = now()->timestamp;

            $payload = [
                'spCode' => $this->selectedSpCode,
                'billRef' => $this->billRef,
                'amount' => $this->amount,
                'callbackUrl' => route('nbc.payment.callback'),
                'userId' => 'USER101',
                'branchCode' => '015',
                'channelRef' => $channelRef,
                'creditAccount' => '28012040022',
                'debitAccount' => '28012040011',
                'payerName' => 'Nyerere Julias',
                'payerPhone' => '255715000000',
                'payerEmail' => 'Nyerere.Julias@example.com',
                'narration' => 'Livewire Payment Test',
                'creditCurrency' => 'TZS',
                'debitCurrency' => 'TZS',
                'paymentType' => 'ACCOUNT',
                'channelCode' => 'APP',
                'extraFields' => new \stdClass(),
                'inquiryRawResponse' => json_encode($this->billDetails),
            ];

            $this->paymentResponse = $service->processPaymentAsync($payload);

            $this->transactions[] = [
                'spCode' => $this->selectedSpCode,
                'billRef' => $this->billRef,
                'amount' => $this->amount,
                'channelRef' => $channelRef,
                'gatewayRef' => $this->paymentResponse['gatewayRef'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::error('Payment failed', ['error' => $e->getMessage()]);
        }
    }

    public function checkPaymentStatus($channelRef)
    {
        try {
            $service = new NbcBillsPaymentService();
            $this->paymentStatus = $service->checkPaymentStatus([
                'spCode' => $this->selectedSpCode,
                'billRef' => $this->billRef,
                'channelRef' => $channelRef,
            ]);
        } catch (\Throwable $e) {
            Log::error('Status check failed', ['error' => $e->getMessage()]);
        }
    }




    ////////////////////////////////////TIPS////////////////////////////////////////////////////////


    public function updatedLookupType()
    {
        $this->reset(['response', 'errorMessage']);
    }

    public function performLookup()
    {
        $this->validate([
            'form.debitAccount' => 'required',
            'form.amount' => 'required|numeric',
            'form.debitAccountCategory' => 'required|in:PERSON,BUSINESS',
        ]);

        $lookupService = new NbcLookupService();
        $this->reset(['response', 'errorMessage']);

        try {
            switch ($this->lookupType) {
                case 'bank-to-bank':
                    $this->validate([
                        'form.accountNumber' => 'required',
                        'form.bankCode' => 'required',
                    ]);
                    $this->response = $lookupService->bankToBankLookup(
                        $this->form['accountNumber'],
                        $this->form['bankCode'],
                        $this->form['debitAccount'],
                        $this->form['amount'],
                        $this->form['debitAccountCategory']
                    );
                    break;

                case 'bank-to-wallet':
                    $this->validate([
                        'form.phoneNumber' => 'required',
                        'form.walletProvider' => 'required',
                    ]);
                    $this->response = $lookupService->bankToWalletLookup(
                        $this->form['phoneNumber'],
                        $this->form['walletProvider'],
                        $this->form['debitAccount'],
                        $this->form['amount'],
                        $this->form['debitAccountCategory']
                    );
                    break;

                case 'merchant-payment':
                    $this->validate([
                        'form.merchantId' => 'required',
                        'form.bankCode' => 'required',
                    ]);
                    $this->response = $lookupService->merchantPaymentLookup(
                        $this->form['merchantId'],
                        $this->form['bankCode'],
                        $this->form['debitAccount'],
                        $this->form['amount'],
                        $this->form['debitAccountCategory']
                    );
                    break;
            }
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }


////////////////////////////////////////////////////////////////////////BANK TO BANK - BANK TO WALLET////////////////////////////////////////////////


public function verifyBeneficiary(NbcLookupService $lookupService)
{
    $this->validate();
    $this->resetMessages();
    $this->isProcessing = true;

    try {


        if ($this->transferType === 'bank') {
            $lookupResponse = $lookupService->bankToBankLookup(
                $this->beneficiaryAccount,
                $this->bankCode,
                $this->debitAccount,
                $this->amount
            );
        } else {

             // Ensure we have a valid phone number
        if ($this->phoneNumber === null) {
            $this->errorMessage = 'Phone number is required for verification. Please update your profile.';
            $this->isProcessing = false;
            return;
        }

            $lookupResponse = $lookupService->bankToWalletLookup(
                $this->phoneNumber,
                $this->walletProvider,
                $this->debitAccount,
                $this->amount
            );
        }

        if (!$lookupResponse['success']) {
            $this->errorMessage = 'Verification failed: ' . $lookupResponse['message'];
            $this->isProcessing = false;
            return;
        }

        $this->lookupData = $lookupResponse['data'];
        $this->beneficiaryName = $this->lookupData['body']['fullName'] ?? '';
        $this->currentPhase = 'verify';

    } catch (\Exception $e) {
        $this->errorMessage = 'Verification error: ' . $e->getMessage();
        logger()->error('Beneficiary verification error: ' . $e->getMessage());
    }

    $this->isProcessing = false;
}

public function confirmTransfer(NbcPaymentService $paymentService)
{
    $this->resetMessages();
    $this->isProcessing = true;

    try {
        if ($this->transferType === 'bank') {
            $paymentResponse = $paymentService->processBankToBankTransfer(
                $this->lookupData,
                $this->debitAccount,
                $this->amount,
                auth()->user()->phone ?? '255715000000', // Default phone number if null
                auth()->id(),
                $this->remarks
            );
        } else {
            $paymentResponse = $paymentService->processBankToWalletTransfer(
                $this->lookupData,
                $this->debitAccount,
                $this->amount,
                $this->phoneNumber,
                auth()->id(),
                $this->remarks
            );
        }

        if ($paymentResponse['success']) {
            $this->successMessage = 'Transfer initiated successfully!';
            $this->engineRef = $paymentResponse['engineRef'];
            $this->currentPhase = 'complete';
        } else {
            // Get both message and remarks
            $message = $paymentResponse['message'] ?? 'Transfer failed';
            $remarks = '';

            if (isset($paymentResponse['data']) && is_array($paymentResponse['data'])) {
                if (isset($paymentResponse['data']['remarks'])) {
                    $remarks = $paymentResponse['data']['remarks'];
                }
            }

            // Combine message and remarks if both exist
            $this->errorMessage = $remarks ? "{$remarks} - {$message}" : $message;
            $this->currentPhase = 'form'; // Return to form
        }

    } catch (\Exception $e) {
        $this->errorMessage = 'Transfer error: ' . $e->getMessage();
        logger()->error('Money transfer error: ' . $e->getMessage());
        $this->currentPhase = 'form';
    }

    $this->isProcessing = false;
}

public function startNewTransfer()
{
    $this->resetExcept(['availableBanks', 'availableWallets']);
    $this->currentPhase = 'form';
}



protected function resetMessages()
{
    $this->reset(['successMessage', 'errorMessage', 'engineRef']);
}




//////////////////////////////////////////LUKU////////////////////////////////////////////////////////



public function lookup()
{
    $this->validateOnly('meterNumber');
    $service = new LukuService();
    $this->lookupResult = $service->lookup($this->meterNumber, $this->accountNumber);
}

public function pay()
{
    $this->validate();

    $service = new LukuService();
    $this->paymentResult = $service->pay([
        'channel_ref' => 'CHNL' . now()->timestamp,
        'cbp_gw_ref' => 'CBPGW' . now()->timestamp,
        'result_url' => url()->route('luku.callback'),
        'transaction_id' => 'TRX' . rand(10000, 99999),
        'meter_number' => $this->meterNumber,
        'account_number' => $this->accountNumber,
        'amount' => $this->amount,
    ]);
}


/////////////////////////////////////////////////////////GEPG////////////////////////////////////////////////////////






    public function verifyBill()
    {
        $this->validate([
            'controlNumber' => 'required|string|min:5',
            //'accountNo' => 'required|string',
            'currency' => 'required|string|size:3',
        ]);

        $this->isProcessing = true;
        $this->error = null;
        $this->accountNo = "28012040011";

        try {
            $processor = app(PaymentProcessorService::class);
            $result = $processor->verifyBill($this->controlNumber, $this->accountNo, $this->currency);

            if ($result['status'] === 'success') {
                $this->verificationResult = $result['response'];
                $this->preparePaymentData($result);
                $this->step = 2;
            } else {
                $this->error = $result['message'] ?? 'Verification failed';
            }
        } catch (\Exception $e) {
            $this->error = 'An error occurred during verification: ' . $e->getMessage();
        }

        $this->isProcessing = false;
    }

    protected function preparePaymentData(array $verificationResult)
    {
        $billDetails = $verificationResult['response']['BillDtls']['BillDtl'] ?? [];

        if (!is_array($billDetails) || empty($billDetails)) {
            $this->error = 'No bill details found';
            return;
        }

        // For simplicity, we'll process the first bill item
        $firstBill = is_array($billDetails[0]) ? $billDetails[0] : $billDetails;

        $this->paymentData = [
            'channel_ref' => uniqid('pay_'),
            'cbp_gw_ref' => $verificationResult['response']['BillHdr']['CbpGwRef'] ?? '',
            'control_number' => $this->controlNumber,
            'pay_type' => $verificationResult['response']['BillHdr']['PayType'] ?? '1',
            'status_code' => $verificationResult['response']['BillHdr']['BillStsCode'] ?? '7101',
            'debit_account_no' => $this->accountNo,
            'currency' => $this->currency,
            'total_amount' => $firstBill['BillAmt'] ?? 0,
            'items' => [
                [
                    'ChannelTrxId' => uniqid('trx_'),
                    'SpCode' => $firstBill['SpCode'] ?? '',
                    'PayRefId' => $firstBill['PayRefId'] ?? '',
                    'BillCtrNum' => $firstBill['BillCtrNum'] ?? $this->controlNumber,
                    'PaidAmt' => $firstBill['BillAmt'] ?? 0,
                    'TrxDtTm' => now()->format('Y-m-d\TH:i:s'),
                    'PayOpt' => $firstBill['PayOpt'] ?? '1',
                    'PayPlan' => $firstBill['PayPlan'] ?? '1',
                    'BillAmt' => $firstBill['BillAmt'] ?? 0,
                    'MinPayAmt' => $firstBill['MinPayAmt'] ?? 0,
                    'Ccy' => $firstBill['Ccy'] ?? $this->currency,
                    'PyrCellNum' => auth()->user()->phone ?? '',
                    'PyrName' => auth()->user()->name ?? '',
                    'PyrEmail' => auth()->user()->email ?? '',
                ]
            ]
        ];
    }

    public function processPayment()
    {
        $this->isProcessing = true;
        $this->error = null;

        try {
            $processor = app(PaymentProcessorService::class);

            if ($this->paymentType === 'prepaid') {
                $result = $processor->processPrepaidPayment($this->paymentData);
            } else {
                $result = $processor->processPostpaidPayment($this->paymentData);
            }

            if ($result['status'] === 'success') {
                $this->transactionResult = $result;
                $this->step = 3;
            } else {
                $this->error = $result['message'] ?? 'Payment processing failed';
            }
        } catch (\Exception $e) {
            $this->error = 'An error occurred during payment processing: ' . $e->getMessage();
        }

        $this->isProcessing = false;
    }

    public function selectedMenu($menuId)
    {
        $this->selectedMenuItem = $menuId;
    }

    public function render()
    {


        return view('livewire.payments.payments');
    }
}
