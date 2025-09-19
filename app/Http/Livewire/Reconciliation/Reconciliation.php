<?php

namespace App\Http\Livewire\Reconciliation;

use App\Models\AnalysisSession;
use App\Models\BankTransaction;
use App\Models\Transaction;
use App\Services\BankReconciliationService;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Traits\Livewire\WithModulePermissions;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Facades\DB;

class Reconciliation extends Component
{
    use WithFileUploads;
    use WithModulePermissions;

    // File upload properties
    public $bankStatement;
    public $pdfFile;

    // Validation rules
    protected $rules = [
        'pdfFile' => 'required|mimes:pdf|max:10240', // Max 10MB
        'bankStatement' => 'required|in:im,crdb,nmb,abbsa'
    ];

    // Data properties
    public $sessions;
    public $activeSessionId = null;
    public $showData = false;
    public $reconciliationResults = null;
    public $selectedBankTransactions = [];
    public $availableTransactions = [];

    // UI state properties
    public $isProcessing = false;
    public $processingMessage = '';
    public $showReconciliationModal = false;
    public $selectedBankTransactionId = null;

    public function mount()
    {
        // Initialize the permission system for this module
        $this->initializeWithModulePermissions();
        
        $this->loadSessions();
    }
    
    /**
     * Override to specify the module name for permissions
     * 
     * @return string
     */
    protected function getModuleName(): string
    {
        return 'reconciliation';
    }

    public function loadSessions()
    {
        $this->sessions = AnalysisSession::with('bankTransactions')
            ->latest()
            ->get();
    }

    public function setActiveSession($sessionId)
    {
        if (!$this->authorize('view', 'You do not have permission to view reconciliation sessions')) {
            return;
        }
        $this->activeSessionId = $sessionId;
        $this->showData = true;
        $this->reconciliationResults = null;
    }

    public function uploadFile()
    {
        if (!$this->authorize('upload', 'You do not have permission to upload bank statements')) {
            return;
        }
        try {
            $this->validate();

            if (!$this->pdfFile) {
                session()->flash('error', 'No file selected.');
                return;
            }

            // Store the file
            $filePath = $this->pdfFile->store('bank_statements', 'public');
            Log::info('File stored successfully: ' . $filePath);

            // Parse the PDF
            $parser = new Parser();
            $pdf = $parser->parseFile(storage_path("app/public/" . $filePath));
            $text = $pdf->getText();

            Log::info('PDF parsed successfully. Content length: ' . strlen($text));

            // Process based on bank type
            $sessionId = $this->processBankStatement($text);

            if ($sessionId) {
                $this->loadSessions();
                $this->setActiveSession($sessionId);
                
                session()->flash('success', 'Bank statement uploaded and processed successfully!');
                $this->reset(['pdfFile', 'bankStatement']);
            } else {
                session()->flash('error', 'Failed to process bank statement.');
            }

        } catch (Exception $e) {
            Log::error("Error processing PDF: " . $e->getMessage(), ['exception' => $e]);
            session()->flash('error', 'An error occurred while processing the file: ' . $e->getMessage());
        }
    }

    private function processBankStatement($text)
    {
        switch ($this->bankStatement) {
            case 'im':
                return $this->processIMBankStatement($text);
            case 'crdb':
                return $this->processCRDBBankStatement($text);
            case 'nmb':
                return $this->processNMBBankStatement($text);
            case 'abbsa':
                return $this->processABBSABankStatement($text);
            default:
                throw new Exception('Unsupported bank type');
        }
    }

    private function processIMBankStatement($text)
    {
        // Extract account info
        preg_match('/ACCOUNT NAME\s*:\s*(.*)/', $text, $accountMatches);
        preg_match('/STATEMENT FOR THE PERIOD OF\s*:\s*(.*)/', $text, $periodMatches);

        $accountName = trim($accountMatches[1] ?? 'Unknown Account');
        $statementPeriod = trim($periodMatches[1] ?? 'Unknown Period');

        // Create analysis session
        $session = AnalysisSession::create([
            'account_name' => $accountName,
            'statement_period' => $statementPeriod,
            'bank' => 'I&M BANK',
            'status' => 'processing'
        ]);

        // Extract transactions
        $lines = explode("\n", $text);
        $pattern = '/^\|\d{2}-\d{2}-\d{2}\s*\|\d{2}-\d{2}-\d{2}/';
        $transactions = [];

        foreach ($lines as $line) {
            if (preg_match($pattern, $line)) {
                $cleanedLine = trim($line, "| ");
                $columns = array_map('trim', explode('|', $cleanedLine));

                if (count($columns) >= 7) {
                    $withdrawalAmount = !empty($columns[4]) ? floatval(str_replace([',', 'Cr'], '', $columns[4])) : 0.00;
                    $depositAmount = !empty($columns[5]) ? floatval(str_replace([',', 'Cr'], '', $columns[5])) : 0.00;

                    $transactions[] = [
                        'session_id' => $session->id,
                        'transaction_date' => $this->parseDate($columns[0]),
                        'value_date' => $this->parseDate($columns[1]),
                        'narration' => $columns[3] ?? '',
                        'withdrawal_amount' => $withdrawalAmount,
                        'deposit_amount' => $depositAmount,
                        'balance' => isset($columns[6]) ? floatval(str_replace([',', 'Cr'], '', $columns[6])) : null,
                        'raw_data' => $columns
                    ];
                }
            }
        }

        // Save transactions
        if (!empty($transactions)) {
            BankTransaction::insert($transactions);
            $session->update([
                'total_transactions' => count($transactions),
                'status' => 'completed'
            ]);
        }

        return $session->id;
    }

    private function processCRDBBankStatement($text)
    {
        // Extract account info
        preg_match('/^([A-Z\s]+)\n/m', $text, $accountMatches);
        preg_match('/Account:\s+(\d+)/', $text, $accountNumberMatches);
        preg_match('/Period:\s+([\d\/]+)\s*-\s*([\d\/]+)/', $text, $periodMatches);

        $accountName = trim($accountMatches[1] ?? 'Unknown Account');
        $accountNumber = $accountNumberMatches[1] ?? null;
        $statementPeriod = ($periodMatches[1] ?? '') . ' - ' . ($periodMatches[2] ?? '');

        // Create analysis session
        $session = AnalysisSession::create([
            'account_name' => $accountName,
            'account_number' => $accountNumber,
            'statement_period' => $statementPeriod,
            'bank' => 'CRDB BANK',
            'status' => 'processing'
        ]);

        // Extract transactions using regex pattern
        $pattern = '/(\d{2}\.\d{2}\.\d{4})\s*(\d{2}:\d{2}:\d{2})\s*(.*?)(\d{2}\.\d{2}\.\d{4})\s*(\d{2}:\d{2}:\d{2})\s*([\d,]+\.\d{2})\s*([\d,]+\.\d{2})\s*([\d,]+)/s';
        preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);

        $transactions = [];
        foreach ($matches as $match) {
            $debitAmount = $this->normalizeAmount($match[6]);
            $creditAmount = $this->normalizeAmount($match[7]);
            $balance = $this->normalizeAmount($match[8]);

            $transactions[] = [
                'session_id' => $session->id,
                'transaction_date' => $this->convertDateFormat($match[1]),
                'value_date' => $this->convertDateFormat($match[4]),
                'narration' => trim($match[3]),
                'withdrawal_amount' => $debitAmount,
                'deposit_amount' => $creditAmount,
                'balance' => $balance,
                'raw_data' => $match
            ];
        }

        // Save transactions
        if (!empty($transactions)) {
            BankTransaction::insert($transactions);
            $session->update([
                'total_transactions' => count($transactions),
                'status' => 'completed'
            ]);
        }

        return $session->id;
    }

    private function processNMBBankStatement($text)
    {
        // Extract account info
        preg_match('/Name\s*:\s*(.*?)\s*Value Date/', $text, $accountMatches);
        preg_match('/From Date\s*\t(.*?)\t/', $text, $fromDateMatches);
        preg_match('/To Date\s*\t(.*?)\n/', $text, $toDateMatches);

        $accountName = trim($accountMatches[1] ?? 'Unknown Account');
        $statementPeriod = ($fromDateMatches[1] ?? '') . ' - ' . ($toDateMatches[1] ?? '');

        // Create analysis session
        $session = AnalysisSession::create([
            'account_name' => $accountName,
            'statement_period' => $statementPeriod,
            'bank' => 'NMB BANK',
            'status' => 'processing'
        ]);

        // Extract transactions
        $transactionStart = strpos($text, "Book Date Value Date");
        if ($transactionStart !== false) {
            $transactionData = substr($text, $transactionStart);
            $lines = explode("\n", $transactionData);
            
            $transactions = [];
            $currentTransaction = null;

            foreach ($lines as $line) {
                $line = trim($line);
                if (preg_match('/^\d{2}\s\w{3}\s\d{4}/', $line)) {
                    if ($currentTransaction) {
                        $transactions[] = $currentTransaction;
                    }
                    $currentTransaction = $this->parseNMBTransactionLine($line, $session->id);
                } elseif ($currentTransaction && !empty($line)) {
                    $currentTransaction['narration'] .= ' ' . $line;
                }
            }
            
            if ($currentTransaction) {
                $transactions[] = $currentTransaction;
            }

            // Save transactions
            if (!empty($transactions)) {
                BankTransaction::insert($transactions);
                $session->update([
                    'total_transactions' => count($transactions),
                    'status' => 'completed'
                ]);
            }
        }

        return $session->id;
    }

    private function processABBSABankStatement($text)
    {
        // Extract account info
        preg_match('/Account name:\s*(.*?)\s*Account number:/', $text, $accountMatches);
        preg_match('/Account number:\s*(\d+)\t/', $text, $accountNumberMatches);

        $accountName = trim($accountMatches[1] ?? 'Unknown Account');
        $accountNumber = $accountNumberMatches[1] ?? null;

        // Create analysis session
        $session = AnalysisSession::create([
            'account_name' => $accountName,
            'account_number' => $accountNumber,
            'statement_period' => 'Statement Period',
            'bank' => 'ABBSA BANK',
            'status' => 'processing'
        ]);

        // Extract transactions
        $pattern = '/(\d{2}-\d{2}-\d{4}\s\d{2}:\d{2})\s*(\d{2}-\d{2}-\d{4}\s\d{2}:\d{2})\s*(.*?)\s*\t0\t([\d.]+)\s([\d.]+)\s([\d.]+)\n/s';
        preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);

        $transactions = [];
        foreach ($matches as $match) {
            $transactions[] = [
                'session_id' => $session->id,
                'transaction_date' => $this->parseDateTime($match[1]),
                'value_date' => $this->parseDateTime($match[2]),
                'narration' => trim($match[3]),
                'withdrawal_amount' => floatval($match[4]),
                'deposit_amount' => floatval($match[5]),
                'balance' => floatval($match[6]),
                'raw_data' => $match
            ];
        }

        // Save transactions
        if (!empty($transactions)) {
            BankTransaction::insert($transactions);
            $session->update([
                'total_transactions' => count($transactions),
                'status' => 'completed'
            ]);
        }

        return $session->id;
    }

    public function runReconciliation()
    {
        if (!$this->authorize('reconcile', 'You do not have permission to run reconciliation')) {
            return;
        }
        if (!$this->activeSessionId) {
            session()->flash('error', 'No session selected for reconciliation.');
            return;
        }

        try {
            $this->isProcessing = true;
            $this->processingMessage = 'Running reconciliation...';

            $reconciliationService = new BankReconciliationService();
            $this->reconciliationResults = $reconciliationService->reconcileBankTransactions($this->activeSessionId);

            session()->flash('success', 'Reconciliation completed successfully!');
            
        } catch (Exception $e) {
            Log::error("Reconciliation error: " . $e->getMessage());
            session()->flash('error', 'Reconciliation failed: ' . $e->getMessage());
        } finally {
            $this->isProcessing = false;
            $this->processingMessage = '';
        }
    }

    public function showManualReconciliation($bankTransactionId)
    {
        if (!$this->authorize('reconcile', 'You do not have permission to perform manual reconciliation')) {
            return;
        }
        $this->selectedBankTransactionId = $bankTransactionId;
        $this->showReconciliationModal = true;
        
        // Load available transactions for manual matching
        $bankTransaction = BankTransaction::find($bankTransactionId);
        $this->availableTransactions = Transaction::where('amount', $bankTransaction->amount)
            ->where('status', 'completed')
            ->whereNull('matched_transaction_id')
            ->limit(10)
            ->get();
    }

    public function manualReconcile($transactionId, $notes = null)
    {
        if (!$this->authorize('reconcile', 'You do not have permission to perform manual reconciliation')) {
            return;
        }
        try {
            $reconciliationService = new BankReconciliationService();
            $result = $reconciliationService->manualReconcile($this->selectedBankTransactionId, $transactionId, $notes);
            
            session()->flash('success', $result['message']);
            $this->showReconciliationModal = false;
            $this->selectedBankTransactionId = null;
            
        } catch (Exception $e) {
            session()->flash('error', 'Manual reconciliation failed: ' . $e->getMessage());
        }
    }

    // Utility methods
    private function parseDate($dateString)
    {
        try {
            return Carbon::createFromFormat('d-m-y', trim($dateString))->format('Y-m-d');
        } catch (Exception $e) {
            return null;
        }
    }

    private function convertDateFormat($date)
    {
        try {
            return Carbon::createFromFormat('d.m.Y', $date)->format('Y-m-d');
        } catch (Exception $e) {
            return null;
        }
    }

    private function parseDateTime($dateTimeString)
    {
        try {
            return Carbon::createFromFormat('d-m-Y H:i', $dateTimeString)->format('Y-m-d');
        } catch (Exception $e) {
            return null;
        }
    }

    private function normalizeAmount($amount)
    {
        return (float) str_replace(',', '', $amount);
    }

    private function parseNMBTransactionLine($line, $sessionId)
    {
        if (preg_match('/(\d{2}\s\w{3}\s\d{4})(\d{2}\s\w{3}\s\d{4})(\w+)\s*(\w+)\s*(.*?)$/', $line, $matches)) {
            return [
                'session_id' => $sessionId,
                'transaction_date' => $this->parseNMBDate($matches[1]),
                'value_date' => $this->parseNMBDate($matches[2]),
                'reference_number' => $matches[3],
                'branch' => $matches[4],
                'narration' => trim($matches[5]),
                'withdrawal_amount' => 0,
                'deposit_amount' => 0,
                'balance' => null,
                'raw_data' => $matches
            ];
        }
        return null;
    }

    private function parseNMBDate($dateString)
    {
        try {
            return Carbon::createFromFormat('d M Y', $dateString)->format('Y-m-d');
        } catch (Exception $e) {
            return null;
        }
    }

    public function render()
    {
        // Ensure sessions are loaded
        if (!$this->sessions) {
            $this->loadSessions();
        }

        $activeSession = null;
        $bankTransactions = collect();
        $reconciliationSummary = null;

        if ($this->activeSessionId) {
            $activeSession = AnalysisSession::with('bankTransactions')->find($this->activeSessionId);
            if ($activeSession) {
                $bankTransactions = $activeSession->bankTransactions()
                    ->orderBy('transaction_date', 'desc')
                    ->get();
                $reconciliationSummary = $activeSession->reconciliation_summary;
            }
        }

        return view('livewire.reconciliation.reconciliation', array_merge(
            $this->permissions,
            [
                'sessions' => $this->sessions ?? collect(),
                'activeSession' => $activeSession,
                'bankTransactions' => $bankTransactions,
                'reconciliationSummary' => $reconciliationSummary,
                'activeSessionId' => $this->activeSessionId ?? null,
                'showData' => $this->showData ?? false,
                'reconciliationResults' => $this->reconciliationResults ?? null,
                'showReconciliationModal' => $this->showReconciliationModal ?? false,
                'availableTransactions' => $this->availableTransactions ?? collect(),
                'permissions' => $this->permissions
            ]
        ));
    }
}
