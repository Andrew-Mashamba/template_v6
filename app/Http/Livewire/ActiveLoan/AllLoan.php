<?php

namespace App\Http\Livewire\ActiveLoan;

use Livewire\Component;
use App\Traits\Livewire\WithModulePermissions;

class AllLoan extends Component
{
    use WithModulePermissions;
    public $tab_id = 1;
    
    // Sub-tabs for each main section
    public $loanTab = 'summary';
    public $paymentTab = 'new';
    public $arrearsTab = 'days';
    public $portfolioTab = 'par';
    public $collectionTab = 'ongoing';
    public $collateralTab = 'list';
    
    protected $listeners = [
        "displayLoanReport" => "setView",
        "refreshData" => "refreshData"
    ];

    public function mount()
    {
        // Initialize the permission system for this module
        $this->initializeWithModulePermissions();
    }

    public function boot()
    {
        session()->put('tab_id', 1);
    }

    public function setView($id)
    {
        // Check permissions based on the section being accessed
        $requiredPermission = $this->getRequiredPermissionForSection($id);
        $permissionKey = 'can' . ucfirst($requiredPermission);
        
        if (!($this->permissions[$permissionKey] ?? false)) {
            session()->flash('error', 'You do not have permission to access this loan management section');
            return;
        }
        
        $this->tab_id = $id;
        session()->put('tab_id', $id);
    }

    // Sub-tab setters for each main section
    public function setLoanTab($tab)
    {
        $this->loanTab = $tab;
    }

    public function setPaymentTab($tab)
    {
        $this->paymentTab = $tab;
    }

    public function setArrearsTab($tab)
    {
        $this->arrearsTab = $tab;
    }

    public function setPortfolioTab($tab)
    {
        $this->portfolioTab = $tab;
    }

    public function setCollectionTab($tab)
    {
        $this->collectionTab = $tab;
    }

    public function setCollateralTab($tab)
    {
        $this->collateralTab = $tab;
    }

    public function refreshData()
    {
        // Refresh data method
        $this->emit('dataRefreshed');
    }

    public function render()
    {
        return view('livewire.active-loan.all-loan', array_merge(
            $this->permissions,
            [
                'permissions' => $this->permissions
            ]
        ));
    }

    /**
     * Get the required permission for a specific loan management section
     */
    private function getRequiredPermissionForSection($sectionId)
    {
        $sectionPermissionMap = [
            1 => 'view',      // Loan Accounts
            2 => 'create',    // Record Payment
            3 => 'view',      // Arrears Dashboard
            4 => 'view',      // Portfolio Quality
            5 => 'view',      // Collection Dashboard
            6 => 'view',      // Collateral Register
            7 => 'view',      // Loss Provisions
            8 => 'manage',    // Write-offs (requires manage permission)
            9 => 'manage',    // Restructuring (requires manage permission)
            10 => 'manage',   // Early Settlement (requires manage permission)
            11 => 'view',     // Guarantors
            12 => 'view',     // Loan Insurance
            13 => 'manage',   // Legal Actions (requires manage permission)
            14 => 'view',     // Performance Analytics
        ];
        
        return $sectionPermissionMap[$sectionId] ?? 'view';
    }

    /**
     * Override to specify the module name for permissions
     * 
     * @return string
     */
    protected function getModuleName(): string
    {
        return 'active-loan';
    }
}
