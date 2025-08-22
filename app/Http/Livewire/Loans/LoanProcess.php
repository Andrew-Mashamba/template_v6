<?php

namespace App\Http\Livewire\Loans;

use App\Models\ClientsModel;
use App\Models\LoansModel;
use App\Models\approvals;
use App\Services\LoanTabStateService;
use Illuminate\Support\Facades\Session;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class LoanProcess extends Component
{
    use WithFileUploads;
    
    public $activeTab = 'client';
    public $loan;
    public $client;
    public $loanStatus;
    public $progressData = [];
    public $isLoading = false;
    public $errorMessage = '';
    public $successMessage = '';
    public $approvalComment = '';
    public $showApprovalModal = false;
    public $policyAdherenceConfirmed = false;
    
    // Committee minutes handling
    public $committeeMinutesFile;
    public $committeeMinutesPath;

    // Tab configuration - removed 'completed' property since we'll get it from DB
    protected $tabs = [
        'client' => [
            'label' => 'Client Information',
            'icon' => 'user',
            'required' => true
        ],
        'assessment' => [
            'label' => 'Affordability',
            'icon' => 'calculator',
            'required' => true
        ],
        'guarantor' => [
            'label' => 'Guarantor And Pledged Collateral',
            'icon' => 'shield-check',
            'required' => true
        ],
        'addDocument' => [
            'label' => 'Documents',
            'icon' => 'document',
            'required' => true
        ],
  
    ];

    protected $listeners = [
        'refreshLoanProcess' => '$refresh',
        'tabCompleted' => 'markTabAsCompleted',
        'showError' => 'showErrorMessage',
        'showSuccess' => 'showSuccessMessage'
    ];

    protected $tabStateService;

    public function boot(LoanTabStateService $tabStateService)
    {
        $this->tabStateService = $tabStateService;
    }

    public function mount()
    {
        $this->loadLoanData();
        $this->calculateProgress();
        $this->restoreApprovalState();
        
        // Set default approval comment if not already set
        if (empty($this->approvalComment)) {
            $this->approvalComment = 'Approved';
        }
    }

    /**
     * Get the tab state service with fallback
     */
    protected function getTabStateService()
    {
        if (!$this->tabStateService) {
            try {
                $this->tabStateService = app(LoanTabStateService::class);
            } catch (\Exception $e) {
                Log::error('Error resolving LoanTabStateService: ' . $e->getMessage());
                return null;
            }
        }
        return $this->tabStateService;
    }

    public function loadLoanData()
    {
        try {
            $loanId = Session::get('currentloanID');
            $clientNumber = Session::get('currentloanClient');

            if ($loanId) {
                // Use fresh() to ensure we get the latest data from database
                $this->loan = LoansModel::find($loanId);
                if ($this->loan) {
                    $this->loan = $this->loan->fresh(); // Force refresh from database
                    $this->client = ClientsModel::where('client_number', $this->loan->client_number)->first();
                    $this->loanStatus = $this->loan->status ?? 'DRAFT';
                    
                    // Log current loan state for debugging
                    Log::info('Loan data loaded', [
                        'loan_id' => $this->loan->id,
                        'status' => $this->loan->status,
                        'approval_stage' => $this->loan->approval_stage,
                        'has_exceptions' => $this->loan->has_exceptions
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error loading loan data: ' . $e->getMessage());
            $this->errorMessage = 'Error loading loan data. Please try again.';
        }
    }

    /**
     * Get tab completion status directly from database
     * This checks both data-based completion and manually marked completion
     */
    public function isTabCompleted($tabName)
    {
        if (!$this->loan) {
            return false;
        }

        try {
            $service = $this->getTabStateService();
            if (!$service) {
                return false;
            }

            // Check if tab is manually marked as completed
            $manuallyCompleted = $service->isTabManuallyCompleted($this->loan->id, $tabName);
            
            // Check if tab is completed based on actual data
            $dataCompleted = $service->isTabCompleted($this->loan->id, $tabName);
            
            // Return true if either condition is met
            return $manuallyCompleted || $dataCompleted;
        } catch (\Exception $e) {
            Log::error('Error checking tab completion for ' . $tabName . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all tab completion statuses from database
     */
    public function getAllTabStatuses()
    {
        if (!$this->loan) {
            return [];
        }

        try {
            $service = $this->getTabStateService();
            if (!$service) {
                return [];
            }

            return $service->getAllTabStatus($this->loan->id);
        } catch (\Exception $e) {
            Log::error('Error getting all tab statuses: ' . $e->getMessage());
            return [];
        }
    }

    public function showTab($tabName)
    {
        // Allow Credit Score, Lending Framework, and Approvals tabs even if not in $tabs array
        $allowedExtraTabs = ['creditScore', 'lendingFramework', 'approvals'];
        
        if (isset($this->tabs[$tabName]) || in_array($tabName, $allowedExtraTabs)) {
            // Auto-mark client tab as completed when navigating away from it
            if ($this->activeTab === 'client' && $tabName !== 'client') {
                $this->markTabAsCompleted('client');
            }
            
            $this->activeTab = $tabName;
        }
    }

    public function markTabAsCompleted($tabName)
    {
        if (!isset($this->tabs[$tabName]) || !$this->loan) {
            return;
        }

        try {
            $service = $this->getTabStateService();
            if (!$service) {
                return;
            }

            // Get current completed tabs from database
            $completedTabs = $service->getCompletedTabs($this->loan->id);
            
            // Add the new completed tab if not already present
            if (!in_array($tabName, $completedTabs)) {
                $completedTabs[] = $tabName;
                
                Log::info('Marking tab as completed', [
                    'loanId' => $this->loan->id,
                    'tabName' => $tabName,
                    'completedTabs' => $completedTabs
                ]);
            }

            // Save updated completion status to database
            $service->saveTabCompletionStatus($this->loan->id, $completedTabs);
            
            $this->calculateProgress();
            $this->showSuccessMessage("Tab '{$this->tabs[$tabName]['label']}' marked as completed!");
            
        } catch (\Exception $e) {
            Log::error('Error marking tab as completed: ' . $e->getMessage());
            $this->showErrorMessage('Error marking tab as completed. Please try again.');
        }
    }

    public function calculateProgress()
    {
        if (!$this->loan) {
            $this->progressData = [
                'total' => count($this->tabs),
                'completed' => 0,
                'percentage' => 0
            ];
            return;
        }

        try {
            $service = $this->getTabStateService();
            if (!$service) {
                $this->progressData = [
                    'total' => count($this->tabs),
                    'completed' => 0,
                    'percentage' => 0
                ];
                return;
            }

            $completedTabs = $service->getCompletedTabs($this->loan->id);
            $totalTabs = count($this->tabs);
            $completedCount = count($completedTabs);

            $this->progressData = [
                'total' => $totalTabs,
                'completed' => $completedCount,
                'percentage' => $totalTabs > 0 ? round(($completedCount / $totalTabs) * 100, 1) : 0
            ];

            Log::info('Progress calculated', [
                'loanId' => $this->loan->id,
                'completedTabs' => $completedTabs,
                'totalTabs' => $totalTabs,
                'completedCount' => $completedCount,
                'percentage' => $this->progressData['percentage']
            ]);
        } catch (\Exception $e) {
            Log::error('Error calculating progress: ' . $e->getMessage());
            $this->progressData = [
                'total' => count($this->tabs),
                'completed' => 0,
                'percentage' => 0
            ];
        }
    }

    public function getTabStatus($tabName)
    {
        // Allow Credit Score and Lending Framework tabs
        $allowedExtraTabs = ['creditScore', 'lendingFramework'];
        
        if (!isset($this->tabs[$tabName]) && !in_array($tabName, $allowedExtraTabs)) {
            return 'inactive';
        }

        if ($this->activeTab === $tabName) {
            return 'active';
        }

        // Extra tabs don't have completion status
        if (in_array($tabName, $allowedExtraTabs)) {
            return 'incomplete';
        }

        // Get completion status directly from database for regular tabs
        return $this->isTabCompleted($tabName) ? 'completed' : 'incomplete';
    }

    public function areAllTabsCompleted()
    {
        if (!$this->loan) {
            return false;
        }

        try {
            $service = $this->getTabStateService();
            if (!$service) {
                return false;
            }
            return $service->areAllTabsCompleted($this->loan->id);
        } catch (\Exception $e) {
            Log::error('Error checking if all tabs are completed: ' . $e->getMessage());
            return false;
        }
    }

    public function getProgressPercentage()
    {
        return $this->progressData['percentage'] ?? 0;
    }

    public function hasCollaterals()
    {
        if (!$this->loan) {
            return false;
        }
        
        try {
            $collateralCount = DB::table('collaterals')
                ->where('loan_id', $this->loan->id)
                ->count();
                
            return $collateralCount > 0;
        } catch (\Exception $e) {
            Log::error('Error checking collaterals: ' . $e->getMessage());
            return false;
        }
    }

    public function sendForApproval()
    {
        try {
            if (!$this->areAllTabsCompleted()) {
                $this->showErrorMessage('All tabs must be completed before sending for approval.');
                return;
            }

            // Update loan status to pending approval
            $this->loan->update(['status' => 'PENDING_APPROVAL']);
            
            $this->showSuccessMessage('Loan application sent for approval successfully!');
            
            // Emit event to refresh the loan table
            $this->emit('refreshLoanTable');
            
        } catch (\Exception $e) {
            Log::error('Error sending for approval: ' . $e->getMessage());
            $this->showErrorMessage('Error sending for approval. Please try again.');
        }
    }

    public function showErrorMessage($message)
    {
        $this->errorMessage = $message;
        $this->dispatchBrowserEvent('show-error', ['message' => $message]);
    }

    public function showSuccessMessage($message)
    {
        $this->successMessage = $message;
        $this->dispatchBrowserEvent('show-success', ['message' => $message]);
    }

    public function close()
    {
        try {
            Session::forget(['currentloanID', 'currentloanClient', 'LoanStage']);
            $this->emit('viewLoanStages');
            $this->showSuccessMessage('Loan process closed successfully.');
        } catch (\Exception $e) {
            Log::error('Error closing loan process: ' . $e->getMessage());
            $this->showErrorMessage('Error closing loan process.');
        }
    }

    public function render()
    {
        // Calculate progress on every render to ensure fresh data
        $this->calculateProgress();
        
        return view('livewire.loans.loan-process', [
            'tabs' => $this->tabs,
            'progressData' => $this->progressData
        ]);
    }
    
    /**
     * Move loan to a specific stage
     */
    public function moveToStage($stage)
    {
        try {
            if (!$this->loan) {
                $this->showErrorMessage('No loan found to update.');
                return;
            }
            
            // Get current stage before moving
            $currentStage = $this->loan->approval_stage ?? 'Inputter';
            
            // Determine if this is a backward movement (Return to Sender)
            $isBackwardMovement = $this->isBackwardMovement($currentStage, $stage);
            
            // If moving backward and there's an approval record, reverse the updates
            if ($isBackwardMovement) {
                $this->reverseApprovalTableUpdates($currentStage, $stage);
            }
            
            // Update the loan's approval stage
            $this->loan->approval_stage = $stage;
            
            // Get and set the role names for the new stage
            $stageRoles = $this->getStageRoles($stage);
            $roleNames = count($stageRoles) > 1 ? 'Loan Committee' : implode(', ', $stageRoles);
            $this->loan->approval_stage_role_name = $roleNames;
            
            // Log the stage update for debugging
            Log::info('Updating loan approval stage', [
                'loan_id' => $this->loan->id,
                'old_stage' => $currentStage,
                'new_stage' => $stage,
                'role_names' => $roleNames,
                'is_backward' => $isBackwardMovement
            ]);
            
            // Update status based on stage movement
            if ($stage === 'Exception') {
                $this->loan->status = 'PENDING-EXCEPTIONS';
            } elseif ($stage === 'Approver' && $this->loan->status !== 'APPROVED' && $this->loan->status !== 'REJECTED') {
                $this->loan->status = 'PENDING_APPROVAL';
            } elseif ($this->loan->status === 'PENDING-EXCEPTIONS' && $stage !== 'Exception') {
                // Moving out of exception stage
                $this->loan->status = 'PENDING';
            } elseif ($isBackwardMovement) {
                // When moving backward, set status to RETURNED
                $this->loan->status = 'RETURNED';
            }
            
            $this->loan->save();
            
            // Refresh the loan data to ensure we have the latest state
            $this->loan->refresh();
            
            // Log the stage transition
            DB::table('loan_approval_logs')->insert([
                'loan_id' => $this->loan->id,
                'stage' => $stage,
                'action' => $isBackwardMovement ? 'RETURNED_TO_STAGE' : 'MOVED_TO_STAGE',
                'comment' => $this->approvalComment ?: ($isBackwardMovement ? 'Returned to previous stage' : 'Stage transition'),
                'performed_by' => auth()->user()->id ?? null,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            $this->approvalComment = '';
            $this->showSuccessMessage("Loan moved to {$stage} stage successfully.");
            $this->loadLoanData(); // Reload loan data to reflect changes
            $this->emit('refreshLoanProcess');
            
        } catch (\Exception $e) {
            Log::error('Error moving loan to stage: ' . $e->getMessage());
            $this->showErrorMessage('Error updating loan stage. Please try again.');
        }
    }
    
    /**
     * Clear exceptions and move to next stage
     */
    public function clearExceptions()
    {
        try {
            if (!$this->loan) {
                $this->showErrorMessage('No loan found to update.');
                return;
            }
            
            // Move from Exception to Inputter
            $this->loan->approval_stage = 'Inputter';
            $this->loan->approval_stage_role_name = 'Loan Officer'; // Set role name for Inputter stage
            $this->loan->status = 'PENDING';
            $this->loan->save();
            
            // Refresh the loan data to ensure we have the latest state
            $this->loan->refresh();
            
            // Log the action
            DB::table('loan_approval_logs')->insert([
                'loan_id' => $this->loan->id,
                'stage' => 'Inputter',
                'action' => 'EXCEPTIONS_CLEARED',
                'comment' => $this->approvalComment ?: 'Exceptions cleared, loan moved to approval workflow',
                'performed_by' => auth()->user()->id ?? null,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            $this->approvalComment = '';
            $this->showSuccessMessage('Exceptions cleared successfully. Loan moved to Inputter stage.');
            $this->loadLoanData(); // Reload loan data to reflect changes
            $this->emit('refreshLoanProcess');
            
        } catch (\Exception $e) {
            Log::error('Error clearing exceptions: ' . $e->getMessage());
            $this->showErrorMessage('Error clearing exceptions. Please try again.');
        }
    }
    
    /**
     * Return loan for correction
     */
    public function returnForCorrection()
    {
        try {
            if (!$this->loan) {
                $this->showErrorMessage('No loan found to update.');
                return;
            }
            
            $this->loan->status = 'RETURNED';
            $this->loan->save();
            
            // Log the action
            DB::table('loan_approval_logs')->insert([
                'loan_id' => $this->loan->id,
                'stage' => $this->loan->approval_stage,
                'action' => 'RETURNED_FOR_CORRECTION',
                'comment' => $this->approvalComment ?: 'Loan returned to applicant for corrections',
                'performed_by' => auth()->user()->id ?? null,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            $this->approvalComment = '';
            $this->showSuccessMessage('Loan returned to applicant for corrections.');
            $this->emit('refreshLoanProcess');
            
        } catch (\Exception $e) {
            Log::error('Error returning loan: ' . $e->getMessage());
            $this->showErrorMessage('Error returning loan. Please try again.');
        }
    }
    
    /**
     * Approve the loan
     */
    public function approveLoan()
    {
        try {
            if (!$this->loan) {
                $this->showErrorMessage('No loan found to approve.');
                return;
            }
            
            if ($this->loan->approval_stage !== 'Approver') {
                $this->showErrorMessage('Loan must be at Approver stage to be approved.');
                return;
            }

            // Update loan status to APPROVED and move to FINANCE stage
            $this->loan->status = 'APPROVED';
            $this->loan->approval_stage = 'FINANCE'; // Move to FINANCE stage after approval
            $this->loan->approval_stage_role_name = 'Finance'; // Set role name for FINANCE stage
            $this->loan->approved_at = now();
            $this->loan->approved_by = auth()->user()->id ?? null;
            $this->loan->save();
            
            // Log the action
            DB::table('loan_approval_logs')->insert([
                'loan_id' => $this->loan->id,
                'stage' => 'Approver',
                'action' => 'APPROVED',
                'comment' => $this->approvalComment ?: 'Loan approved',
                'performed_by' => auth()->user()->id ?? null,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // Refresh the loan to ensure we have the latest state
            $this->loan->refresh();
            
            $this->approvalComment = '';
            $this->showSuccessMessage('Loan approved successfully!');
            $this->loadLoanData(); // Reload loan data to reflect new status
            $this->emit('refreshLoanProcess');
            
        } catch (\Exception $e) {
            Log::error('Error approving loan: ' . $e->getMessage());
            $this->showErrorMessage('Error approving loan. Please try again.');
        }
    }
    
    /**
     * Reject the loan
     */
    public function rejectLoan()
    {
        try {
            if (!$this->loan) {
                $this->showErrorMessage('No loan found to reject.');
                return;
            }
            
            $this->loan->status = 'REJECTED';
            $this->loan->rejected_at = now();
            $this->loan->rejected_by = auth()->user()->id ?? null;
            $this->loan->rejection_reason = $this->approvalComment;
            $this->loan->save();
            
            // Log the action
            DB::table('loan_approval_logs')->insert([
                'loan_id' => $this->loan->id,
                'stage' => $this->loan->approval_stage,
                'action' => 'REJECTED',
                'comment' => $this->approvalComment ?: 'Loan rejected',
                'performed_by' => auth()->user()->id ?? null,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            $this->approvalComment = '';
            $this->showSuccessMessage('Loan rejected.');
            $this->loadLoanData(); // Reload loan data to reflect new status
            $this->emit('refreshLoanProcess');
            
        } catch (\Exception $e) {
            Log::error('Error rejecting loan: ' . $e->getMessage());
            $this->showErrorMessage('Error rejecting loan. Please try again.');
        }
    }
    
    /**
     * Decline the loan (same as reject)
     */
    public function declineLoan()
    {
        $this->rejectLoan();
    }
    
    /**
     * Show approval confirmation modal
     */
    public function showApprovalModal()
    {

       // dd('showApprovalModal');
        try {
            if (!$this->loan) {
                $this->showErrorMessage('No loan found.');
                return;
            }
            
            if ($this->loan->approval_stage !== 'Approver') {
                $this->showErrorMessage('Loan must be at Approver stage to be approved.');
                return;
            }
            
            // Pre-fill the approval comment
            $this->approvalComment = 'Approved';
            $this->policyAdherenceConfirmed = false;
            $this->showApprovalModal = true;
            
        } catch (\Exception $e) {
            Log::error('Error showing approval modal: ' . $e->getMessage());
            $this->showErrorMessage('Error showing approval modal. Please try again.');
        }
    }
    
    /**
     * Close approval confirmation modal
     */
    public function closeApprovalModal()
    {
        $this->showApprovalModal = false;
        $this->policyAdherenceConfirmed = false;
        $this->approvalComment = '';
    }
    
    /**
     * Confirm approval after policy adherence check
     */
    public function confirmApproval()
    {
        try {
            if (!$this->policyAdherenceConfirmed) {
                $this->showErrorMessage('You must confirm policy adherence before approving the loan.');
                return;
            }
            
            // Check if committee minutes are required
            if ($this->isLoanCommitteeStage()) {
                if (!$this->committeeMinutesPath) {
                    $this->showErrorMessage('Please upload committee minutes before approving.');
                    return;
                }
            }
            
            if (!$this->loan) {
                $this->showErrorMessage('No loan found to approve.');
                return;
            }
            
            if ($this->loan->approval_stage !== 'Approver') {
                $this->showErrorMessage('Loan must be at Approver stage to be approved.');
                return;
            }

            // Update loan status to APPROVED and move to FINANCE stage
            $this->loan->status = 'APPROVED';
            $this->loan->approval_stage = 'FINANCE'; // Move to FINANCE stage after approval
            $this->loan->approval_stage_role_name = 'Finance'; // Set role name for FINANCE stage
            $this->loan->approved_at = now();
            $this->loan->approved_by = auth()->user()->id ?? null;
            $this->loan->save();
            
            // Update the existing approval record to mark it as approved
            $this->updateApprovalRecordForFinalApproval();
            
            // Log the action with committee minutes reference
            DB::table('loan_approval_logs')->insert([
                'loan_id' => $this->loan->id,
                'stage' => 'Approver',
                'action' => 'APPROVED',
                'comment' => $this->approvalComment ?: 'Loan approved',
                'performed_by' => auth()->user()->id ?? null,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // Close modal and reset
            $this->closeApprovalModal();
            $this->showSuccessMessage('Loan approved successfully!');
            $this->loadLoanData(); // Reload loan data to reflect new status
            $this->emit('refreshLoanProcess');
            
        } catch (\Exception $e) {
            Log::error('Error confirming approval: ' . $e->getMessage());
            $this->showErrorMessage('Error approving loan. Please try again.');
        }
    }
    
    /**
     * Validate and move to next stage (with committee minutes check)
     */
    public function validateAndMoveToStage($stage)
    {
        try {
            // Check if policy adherence is confirmed
            if (!$this->policyAdherenceConfirmed) {
                $this->showErrorMessage('Please confirm policy adherence before proceeding.');
                return;
            }
            
            // Check if committee minutes are required
            if ($this->isLoanCommitteeStage()) {
                if (!$this->committeeMinutesPath) {
                    $this->showErrorMessage('Please upload committee minutes before proceeding.');
                    return;
                }
            }
            
            // Save state before moving
            $this->persistApprovalState();
            
            // Get current stage before moving
            $currentStage = $this->loan->approval_stage ?? 'Inputter';
            
            // Handle approval record creation and updates based on stage transitions
            if ($currentStage === 'Inputter' && $stage === 'First Checker') {
                // Create new approval record when moving from Inputter to First Checker
                $nextStageRoles = $this->getStageRoles($stage);
                $nextRoleName = count($nextStageRoles) > 1 ? 'Loan Committee' : implode(', ', $nextStageRoles);
                
                // Create approval record for the approval workflow
                $this->createApprovalRecord($stage, $nextRoleName);
                
                Log::info('Created approval record for loan moving from Inputter to First Checker', [
                    'loan_id' => $this->loan->id,
                    'current_stage' => $currentStage,
                    'next_stage' => $stage,
                    'next_role_name' => $nextRoleName,
                    'purpose' => 'This initiates the formal approval workflow in the approvals table'
                ]);
            } elseif (in_array($currentStage, ['First Checker', 'Second Checker', 'Third Checker']) || 
                     ($currentStage === 'Approver' && $this->loan->status !== 'APPROVED')) {
                // Update existing approval record for subsequent stages
                $this->updateApprovalRecord($currentStage, $stage);
                
                Log::info('Updated approval record for stage transition', [
                    'loan_id' => $this->loan->id,
                    'current_stage' => $currentStage,
                    'next_stage' => $stage,
                    'purpose' => 'Updating existing approval record with checker/approver action'
                ]);
            } else {
                Log::info('Stage transition without approval record action', [
                    'loan_id' => $this->loan->id,
                    'current_stage' => $currentStage,
                    'next_stage' => $stage,
                    'reason' => 'No approval record action needed for this transition'
                ]);
            }
            
            // Proceed with stage movement
            $this->moveToStage($stage);
            
        } catch (\Exception $e) {
            Log::error('Error validating and moving stage: ' . $e->getMessage());
            $this->showErrorMessage('Error processing approval. Please try again.');
        }
    }
    
    /**
     * Check if current stage requires loan committee (multiple roles)
     */
    protected function isLoanCommitteeStage()
    {
        if (!$this->loan) {
            return false;
        }
        
        $currentStage = $this->loan->approval_stage ?? 'Inputter';
        $roles = $this->getStageRoles($currentStage);
        return count($roles) > 1;
    }
    
    /**
     * Get roles for a specific stage
     */
    protected function getStageRoles($stage)
    {
        $loanProcessConfig = DB::table('process_code_configs')
            ->where('process_code', 'LOAN_APP')
            ->where('is_active', true)
            ->first();
            
        if (!$loanProcessConfig) {
            return [];
        }
        
        $roleNames = [];
        $roleIds = [];
        
        // Get role IDs based on stage
        switch($stage) {
            case 'First Checker':
                $roleIds = json_decode($loanProcessConfig->first_checker_roles ?? '[]', true);
                break;
            case 'Second Checker':
                $roleIds = json_decode($loanProcessConfig->second_checker_roles ?? '[]', true);
                break;
            case 'Approver':
                $roleIds = json_decode($loanProcessConfig->approver_roles ?? '[]', true);
                break;
            case 'Inputter':
            case 'Exception':
                return ['Loan Officer'];
            default:
                return [];
        }
        
        // Get role names
        if (!empty($roleIds)) {
            $names = DB::table('roles')
                ->whereIn('id', $roleIds)
                ->pluck('name')
                ->toArray();
            return $names;
        }
        
        return [];
    }
    
    /**
     * Handle committee minutes file upload
     */
    public function updatedCommitteeMinutesFile()
    {
        try {
            $this->validate([
                'committeeMinutesFile' => 'required|file|mimes:pdf,doc,docx|max:10240', // 10MB max
            ]);
            
            if ($this->committeeMinutesFile) {
                // Store the file
                $filename = 'committee_minutes_' . $this->loan->id . '_' . time() . '.' . $this->committeeMinutesFile->extension();
                $path = $this->committeeMinutesFile->storeAs('loan_documents/committee_minutes', $filename, 'public');
                
                $this->committeeMinutesPath = $path;
                
                // Save to session for persistence
                Session::put('loan_' . $this->loan->id . '_committee_minutes', $path);
                
                $this->showSuccessMessage('Committee minutes uploaded successfully.');
            }
        } catch (\Exception $e) {
            Log::error('Error uploading committee minutes: ' . $e->getMessage());
            $this->showErrorMessage('Error uploading committee minutes. Please try again.');
        }
    }
    
    /**
     * Remove committee minutes
     */
    public function removeCommitteeMinutes()
    {
        try {
            if ($this->committeeMinutesPath) {
                // Delete the file
                if (Storage::disk('public')->exists($this->committeeMinutesPath)) {
                    Storage::disk('public')->delete($this->committeeMinutesPath);
                }
                
                $this->committeeMinutesPath = null;
                $this->committeeMinutesFile = null;
                
                // Remove from session
                Session::forget('loan_' . $this->loan->id . '_committee_minutes');
                
                $this->showSuccessMessage('Committee minutes removed.');
            }
        } catch (\Exception $e) {
            Log::error('Error removing committee minutes: ' . $e->getMessage());
            $this->showErrorMessage('Error removing committee minutes.');
        }
    }
    
    /**
     * Persist approval state to session
     */
    protected function persistApprovalState()
    {
        if ($this->loan) {
            $sessionKey = 'loan_' . $this->loan->id . '_approval_state';
            Session::put($sessionKey, [
                'policyAdherenceConfirmed' => $this->policyAdherenceConfirmed,
                'committeeMinutesPath' => $this->committeeMinutesPath,
                'approvalComment' => $this->approvalComment
            ]);
        }
    }
    
    /**
     * Restore approval state from session
     */
    protected function restoreApprovalState()
    {
        if ($this->loan) {
            $sessionKey = 'loan_' . $this->loan->id . '_approval_state';
            $state = Session::get($sessionKey, []);
            
            $this->policyAdherenceConfirmed = $state['policyAdherenceConfirmed'] ?? false;
            $this->committeeMinutesPath = $state['committeeMinutesPath'] ?? null;
            $this->approvalComment = $state['approvalComment'] ?? 'Approved';
            
            // Also check for committee minutes separately
            $minutesKey = 'loan_' . $this->loan->id . '_committee_minutes';
            if (Session::has($minutesKey)) {
                $this->committeeMinutesPath = Session::get($minutesKey);
            }
        }
    }
    
    /**
     * Check if the stage movement is backward (Return to Sender)
     */
    protected function isBackwardMovement($currentStage, $targetStage)
    {
        // Define stage hierarchy
        $stageHierarchy = [
            'Exception' => 0,
            'Inputter' => 1,
            'First Checker' => 2,
            'Second Checker' => 3,
            'Third Checker' => 4,
            'Approver' => 5
        ];
        
        $currentLevel = $stageHierarchy[$currentStage] ?? 0;
        $targetLevel = $stageHierarchy[$targetStage] ?? 0;
        
        return $targetLevel < $currentLevel;
    }
    
    /**
     * Reverse approval table updates when moving backward
     */
    protected function reverseApprovalTableUpdates($currentStage, $targetStage)
    {
        try {
            if (!$this->loan) {
                return;
            }
            
            // Find the existing approval record for this loan
            $approval = approvals::where('process_code', 'LOAN_APP')
                ->where('process_id', $this->loan->id)
                ->whereIn('process_status', ['PENDING', 'APPROVED'])
                ->orderBy('created_at', 'desc')
                ->first();
            
            if (!$approval) {
                Log::info('No approval record found to reverse', [
                    'loan_id' => $this->loan->id,
                    'current_stage' => $currentStage,
                    'target_stage' => $targetStage
                ]);
                return;
            }
            
            // Prepare reversal updates based on current stage
            $updateData = [];
            
            switch ($currentStage) {
                case 'Approver':
                    // Reverting from Approver stage
                    if ($approval->approval_status === 'APPROVED') {
                        $updateData['approver_id'] = null;
                        $updateData['approval_status'] = 'PENDING';
                        $updateData['process_status'] = 'PENDING';
                        $updateData['approved_at'] = null;
                    }
                    
                    // Update next role based on target stage
                    if ($targetStage === 'Second Checker') {
                        $nextStageRoles = $this->getStageRoles('Second Checker');
                        $updateData['next_role_name'] = count($nextStageRoles) > 1 ? 
                            'Loan Committee (' . implode(', ', $nextStageRoles) . ')' : 
                            implode(', ', $nextStageRoles);
                        
                        // Also clear third/second checker if returning to Second Checker
                        if ($approval->second_checker_status === 'APPROVED') {
                            $updateData['second_checker_id'] = null;
                            $updateData['second_checker_status'] = 'PENDING';
                            $updateData['checker_level'] = 1;
                        }
                    } elseif ($targetStage === 'First Checker') {
                        // Clear first checker approval but keep the record
                        $updateData['first_checker_id'] = null;
                        $updateData['first_checker_status'] = 'PENDING';
                        $updateData['checker_level'] = 1;
                        
                        $nextStageRoles = $this->getStageRoles('First Checker');
                        $updateData['next_role_name'] = count($nextStageRoles) > 1 ? 
                            'Loan Committee (' . implode(', ', $nextStageRoles) . ')' : 
                            implode(', ', $nextStageRoles);
                    } elseif ($targetStage === 'Inputter') {
                        // If returning all the way to Inputter, delete the approval record
                        $approval->delete();
                        Log::info('Deleted approval record as loan returned to Inputter', [
                            'approval_id' => $approval->id,
                            'loan_id' => $this->loan->id
                        ]);
                        return;
                    }
                    break;
                    
                case 'Third Checker':
                    // Reverting from Third Checker stage
                    $updateData['checker_level'] = 2;
                    
                    if ($targetStage === 'Second Checker') {
                        // Clear second checker approval
                        $updateData['second_checker_id'] = null;
                        $updateData['second_checker_status'] = 'PENDING';
                        
                        $nextStageRoles = $this->getStageRoles('Second Checker');
                        $updateData['next_role_name'] = count($nextStageRoles) > 1 ? 
                            'Loan Committee (' . implode(', ', $nextStageRoles) . ')' : 
                            implode(', ', $nextStageRoles);
                    } elseif ($targetStage === 'First Checker') {
                        // Clear first and second checker approvals but keep the record
                        $updateData['first_checker_id'] = null;
                        $updateData['first_checker_status'] = 'PENDING';
                        $updateData['second_checker_id'] = null;
                        $updateData['second_checker_status'] = 'PENDING';
                        $updateData['checker_level'] = 1;
                        
                        $nextStageRoles = $this->getStageRoles('First Checker');
                        $updateData['next_role_name'] = count($nextStageRoles) > 1 ? 
                            'Loan Committee (' . implode(', ', $nextStageRoles) . ')' : 
                            implode(', ', $nextStageRoles);
                    } elseif ($targetStage === 'Inputter') {
                        // Delete approval record if returning to Inputter
                        $approval->delete();
                        Log::info('Deleted approval record as loan returned to Inputter', [
                            'approval_id' => $approval->id,
                            'loan_id' => $this->loan->id
                        ]);
                        return;
                    }
                    break;
                    
                case 'Second Checker':
                    // Reverting from Second Checker stage
                    if ($targetStage === 'First Checker') {
                        // Clear first checker approval but keep the record
                        $updateData['first_checker_id'] = null;
                        $updateData['first_checker_status'] = 'PENDING';
                        $updateData['checker_level'] = 1;
                        
                        $nextStageRoles = $this->getStageRoles('First Checker');
                        $updateData['next_role_name'] = count($nextStageRoles) > 1 ? 
                            'Loan Committee (' . implode(', ', $nextStageRoles) . ')' : 
                            implode(', ', $nextStageRoles);
                    } elseif ($targetStage === 'Inputter') {
                        // Delete the approval record as it was created at Inputter -> First Checker transition
                        $approval->delete();
                        Log::info('Deleted approval record as loan returned to Inputter', [
                            'approval_id' => $approval->id,
                            'loan_id' => $this->loan->id
                        ]);
                        return;
                    }
                    break;
                    
                case 'First Checker':
                    // Reverting from First Checker stage
                    if ($targetStage === 'Inputter') {
                        // Delete the approval record as it was created at Inputter -> First Checker transition
                        $approval->delete();
                        Log::info('Deleted approval record as loan returned to Inputter', [
                            'approval_id' => $approval->id,
                            'loan_id' => $this->loan->id
                        ]);
                        return;
                    }
                    break;
            }
            
            // Add return comment
            $returnComment = $this->approvalComment ?: 'Returned to ' . $targetStage . ' for review';
            $updateData['comments'] = $returnComment;
            $updateData['updated_at'] = now();
            
            // Apply the updates
            if (!empty($updateData)) {
                $approval->update($updateData);
                
                Log::info('Reversed approval table updates', [
                    'approval_id' => $approval->id,
                    'loan_id' => $this->loan->id,
                    'from_stage' => $currentStage,
                    'to_stage' => $targetStage,
                    'updates' => array_keys($updateData)
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Error reversing approval table updates: ' . $e->getMessage(), [
                'loan_id' => $this->loan->id ?? null,
                'current_stage' => $currentStage,
                'target_stage' => $targetStage,
                'trace' => $e->getTraceAsString()
            ]);
            // Don't throw - let the process continue
        }
    }
    
    /**
     * Update approval record for final approval
     */
    protected function updateApprovalRecordForFinalApproval()
    {
        try {
            if (!$this->loan) {
                return;
            }
            
            // Find the existing approval record for this loan
            $approval = approvals::where('process_code', 'LOAN_APP')
                ->where('process_id', $this->loan->id)
                ->where('process_status', 'PENDING')
                ->orderBy('created_at', 'desc')
                ->first();
            
            if (!$approval) {
                Log::warning('No pending approval record found for final approval', [
                    'loan_id' => $this->loan->id
                ]);
                return;
            }
            
            // Update for final approval
            $updateData = [
                'approver_id' => auth()->user()->id,
                'approval_status' => 'APPROVED',
                'process_status' => 'APPROVED',
                'approved_at' => now(),
                'comments' => $this->approvalComment ?: 'Loan approved',
                'committee_minutes_path' => $this->committeeMinutesPath,
                'policy_adherence_confirmed' => $this->policyAdherenceConfirmed,
                'next_role_name' => null, // No next role after final approval
                'updated_at' => now()
            ];
            
            // Update the approval record
            $approval->update($updateData);
            
            Log::info('Updated approval record for final approval', [
                'approval_id' => $approval->id,
                'loan_id' => $this->loan->id,
                'approved_by' => auth()->user()->id
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error updating approval record for final approval: ' . $e->getMessage(), [
                'loan_id' => $this->loan->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            // Don't throw - let the process continue
        }
    }
    
    /**
     * Update existing approval record when checkers/approvers act
     */
    protected function updateApprovalRecord($currentStage, $nextStage)
    {
        try {
            if (!$this->loan) {
                return;
            }
            
            // Find the existing approval record for this loan
            $approval = approvals::where('process_code', 'LOAN_APP')
                ->where('process_id', $this->loan->id)
                ->where('process_status', 'PENDING')
                ->orderBy('created_at', 'desc')
                ->first();
            
            if (!$approval) {
                Log::warning('No pending approval record found for loan', [
                    'loan_id' => $this->loan->id,
                    'current_stage' => $currentStage
                ]);
                return;
            }
            
            // Get the process configuration
            $config = DB::table('process_code_configs')
                ->where('process_code', 'LOAN_APP')
                ->where('is_active', true)
                ->first();
            
            if (!$config) {
                Log::error('Process configuration not found for LOAN_APP');
                return;
            }
            
            // Update based on current stage
            $updateData = [];
            $user = auth()->user();
            
            switch ($currentStage) {
                case 'First Checker':
                    // First checker is acting
                    if ($nextStage === 'Second Checker' || $nextStage === 'Approver') {
                        $updateData['first_checker_id'] = $user->id;
                        $updateData['first_checker_status'] = 'APPROVED';
                        $updateData['checker_level'] = $nextStage === 'Second Checker' ? 2 : 1;
                        
                        // Update next role name for the next stage
                        $nextStageRoles = $this->getStageRoles($nextStage);
                        $updateData['next_role_name'] = count($nextStageRoles) > 1 ? 
                            'Loan Committee (' . implode(', ', $nextStageRoles) . ')' : 
                            implode(', ', $nextStageRoles);
                    }
                    break;
                    
                case 'Second Checker':
                    // Second checker is acting
                    if ($nextStage === 'Third Checker' || $nextStage === 'Approver') {
                        $updateData['second_checker_id'] = $user->id;
                        $updateData['second_checker_status'] = 'APPROVED';
                        $updateData['checker_level'] = $nextStage === 'Third Checker' ? 3 : 2;
                        
                        // Update next role name for the next stage
                        $nextStageRoles = $this->getStageRoles($nextStage);
                        $updateData['next_role_name'] = count($nextStageRoles) > 1 ? 
                            'Loan Committee (' . implode(', ', $nextStageRoles) . ')' : 
                            implode(', ', $nextStageRoles);
                    }
                    break;
                    
                case 'Third Checker':
                    // Third checker is acting (if exists in workflow)
                    if ($nextStage === 'Approver') {
                        // Assuming third checker maps to additional checker fields
                        // You might need to add third_checker fields to approvals table
                        $updateData['checker_level'] = 3;
                        
                        // Update next role name for approver stage
                        $nextStageRoles = $this->getStageRoles($nextStage);
                        $updateData['next_role_name'] = count($nextStageRoles) > 1 ? 
                            'Loan Committee (' . implode(', ', $nextStageRoles) . ')' : 
                            implode(', ', $nextStageRoles);
                    }
                    break;
                    
                case 'Approver':
                    // Final approver is acting
                    $updateData['approver_id'] = $user->id;
                    $updateData['approval_status'] = 'APPROVED';
                    $updateData['process_status'] = 'APPROVED';
                    $updateData['approved_at'] = now();
                    $updateData['next_role_name'] = null; // No next role after approval
                    break;
            }
            
            // Add common fields
            $updateData['comments'] = $this->approvalComment ?: 'Stage approved';
            $updateData['updated_at'] = now();
            
            // Add committee minutes if this is a loan committee stage
            if ($this->isLoanCommitteeStage() && $this->committeeMinutesPath) {
                $updateData['committee_minutes_path'] = $this->committeeMinutesPath;
            }
            
            // Update policy adherence confirmation
            $updateData['policy_adherence_confirmed'] = $this->policyAdherenceConfirmed;
            
            // Update the approval record
            $approval->update($updateData);
            
            Log::info('Updated approval record for stage action', [
                'approval_id' => $approval->id,
                'loan_id' => $this->loan->id,
                'current_stage' => $currentStage,
                'next_stage' => $nextStage,
                'updates' => array_keys($updateData)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error updating approval record: ' . $e->getMessage(), [
                'loan_id' => $this->loan->id ?? null,
                'current_stage' => $currentStage,
                'trace' => $e->getTraceAsString()
            ]);
            // Don't throw - let the process continue
        }
    }
    
    /**
     * Create approval record for tracking
     */
    protected function createApprovalRecord($nextStage, $nextRoleName)
    {
        try {
            if (!$this->loan) {
                return;
            }
            
            // Prepare loan data for approval
            $loanData = [
                'loan_id' => $this->loan->id,
                'client_number' => $this->loan->client_number,
                'loan_amount' => $this->loan->loan_amount,
                'loan_type' => $this->loan->loan_type,
                'repayment_period' => $this->loan->repayment_period,
                'interest_rate' => $this->loan->interest_rate,
                'purpose' => $this->loan->purpose,
                'current_stage' => $this->loan->approval_stage,
                'next_stage' => $nextStage,
                'policy_confirmed' => $this->policyAdherenceConfirmed,
                'committee_minutes' => $this->committeeMinutesPath,
                'approval_comment' => $this->approvalComment
            ];
            
            $editPackage = json_encode($loanData);
            
            approvals::create([
                'process_name' => 'approve_loan',
                'process_description' => Auth::user()->name . ' has processed loan ' . $this->loan->id . ' at stage ' . $this->loan->approval_stage,
                'approval_process_description' => 'Loan approval workflow - moving to ' . $nextStage,
                'process_code' => 'LOAN_APP',
                'process_id' => $this->loan->id,
                'process_status' => 'PENDING',
                'user_id' => auth()->user()->id,
                'next_role_name' => $nextRoleName,
                'committee_minutes_path' => $this->committeeMinutesPath,
                'policy_adherence_confirmed' => $this->policyAdherenceConfirmed,
                'comments' => $this->approvalComment,
                'approver_id' => null,
                'approval_status' => 'PENDING',
                'edit_package' => $editPackage
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error creating approval record: ' . $e->getMessage());
            // Don't throw - let the process continue
        }
    }
    
    /**
     * Updated mount to persist state on tab changes
     */
    public function updatedActiveTab()
    {
        $this->persistApprovalState();
    }
    
    /**
     * Create final approval record
     */
    protected function createFinalApprovalRecord()
    {
        try {
            if (!$this->loan) {
                return;
            }
            
            // Prepare loan data for final approval
            $loanData = [
                'loan_id' => $this->loan->id,
                'client_number' => $this->loan->client_number,
                'loan_amount' => $this->loan->loan_amount,
                'loan_type' => $this->loan->loan_type,
                'repayment_period' => $this->loan->repayment_period,
                'interest_rate' => $this->loan->interest_rate,
                'purpose' => $this->loan->purpose,
                'approval_stage' => 'APPROVED',
                'approved_by' => auth()->user()->id,
                'approved_at' => now(),
                'policy_confirmed' => $this->policyAdherenceConfirmed,
                'committee_minutes' => $this->committeeMinutesPath,
                'approval_comment' => $this->approvalComment
            ];
            
            $editPackage = json_encode($loanData);
            
            approvals::create([
                'process_name' => 'approve_loan',
                'process_description' => Auth::user()->name . ' has approved loan ' . $this->loan->id,
                'approval_process_description' => 'Loan fully approved and ready for disbursement',
                'process_code' => 'LOAN_APP',
                'process_id' => $this->loan->id,
                'process_status' => 'APPROVED',
                'user_id' => auth()->user()->id,
                'next_role_name' => 'Disbursement Officer',
                'committee_minutes_path' => $this->committeeMinutesPath,
                'policy_adherence_confirmed' => $this->policyAdherenceConfirmed,
                'comments' => $this->approvalComment,
                'approver_id' => auth()->user()->id,
                'approval_status' => 'APPROVED',
                'approved_at' => now(),
                'edit_package' => $editPackage
            ]);
            
            // Clear session state after approval
            Session::forget('loan_' . $this->loan->id . '_approval_state');
            Session::forget('loan_' . $this->loan->id . '_committee_minutes');
            
        } catch (\Exception $e) {
            Log::error('Error creating final approval record: ' . $e->getMessage());
            // Don't throw - let the process continue
        }
    }
}
