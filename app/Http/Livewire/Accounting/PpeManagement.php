<?php
namespace App\Http\Livewire\Accounting;

use App\Jobs\CalculatePpeDepreciation;
use App\Models\AccountsModel;
use App\Models\general_ledger;
use App\Models\PPE;
use App\Services\TransactionPostingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use App\Services\AccountCreationService;

class PpeManagement extends Component
{
    use WithPagination;

    // Navigation and UI state
    public $selectedMenuItem = 1;
    public $search = '';
    public $isEditMode = false;
    public $ppeId;

    // PPE form properties
    public $name, $purchase_price = 0.0, $purchase_date, $salvage_value = 0.0, $useful_life = 1, $quantity = 1;
    public $additions, $status = 'active', $location, $notes;
    public $categoryx;
    public $category = 'property_plant_and_equipment';
    public $asset_sub_category_code;
    public $cash_account;
    public $category_code;
    
    // Additional costs for proper capitalization
    public $legal_fees = 0.0, $registration_fees = 0.0, $renovation_costs = 0.0;
    public $transportation_costs = 0.0, $installation_costs = 0.0, $other_costs = 0.0;
    
    // Payment method and related accounts
    public $payment_method = 'cash';
    public $payment_account_number;
    public $payable_account_number;
    
    // Additional useful fields
    public $supplier_name, $invoice_number, $invoice_date, $additional_notes;

    // Disposal tracking fields
    public $disposal_date, $disposal_method = 'sold', $disposal_proceeds = 0.0, $disposal_notes;
    public $disposal_approval_status = 'pending', $disposal_rejection_reason;
    public $showDisposalForm = false;
    public $disposalAssetId = null;
    public $showRejectionModal = false;
    public $rejectionAssetId = null;

    // Computed fields
    public $initial_value = 0.0, $depreciation_rate = 0.0, $accumulated_depreciation = 0.0, $depreciation_for_year = 0.0, $closing_value = 0.0;

    // Table functionality
    public $selectedPpes = [];
    public $selectAll = false;
    public $sortBy = 'name';
    public $sortDirection = 'asc';
    public $statusFilter = '';

    // Category management
    public $showCategoryForm = false;
    public $categoryName = '';
    public $categoryDepreciationRate = '';
    public $editingCategoryId = null;

    // Reports and exports
    public $reportDateRange = '30';
    public $reportCategory = '';
    public $reportStatus = '';

    // Chart data
    public $chartLabels = [];
    public $chartData = [];

    // Account properties
    public $account_table_name = 'asset_accounts';
    public $next_code_no = 1701;
    public $category_code_of_account = 1700;
    public $cash_account_sub_code;
    public $narration;

    protected $rules = [
        'name' => 'required|string|max:255',
        'categoryx' => 'required|string|max:255',
        'purchase_price' => 'required|numeric|min:0',
        'purchase_date' => 'required|date',
        'salvage_value' => 'required|numeric|min:0',
        'useful_life' => 'required|numeric|min:1',
        'quantity' => 'required|numeric|min:1',
        'location' => 'required|string|max:255',
        'notes' => 'nullable|string|max:1000',
        'status' => 'required|in:active,disposed,under_repair,pending_disposal',
        // Additional costs validation
        'legal_fees' => 'nullable|numeric|min:0',
        'registration_fees' => 'nullable|numeric|min:0',
        'renovation_costs' => 'nullable|numeric|min:0',
        'transportation_costs' => 'nullable|numeric|min:0',
        'installation_costs' => 'nullable|numeric|min:0',
        'other_costs' => 'nullable|numeric|min:0',
        // Payment method validation
        'payment_method' => 'required|in:cash,credit,loan,lease',
        'payment_account_number' => 'nullable|string|max:255',
        'payable_account_number' => 'nullable|string|max:255',
        // Additional fields validation
        'supplier_name' => 'nullable|string|max:255',
        'invoice_number' => 'nullable|string|max:255',
        'invoice_date' => 'nullable|date',
        'additional_notes' => 'nullable|string|max:1000',
        // Disposal fields validation
        'disposal_date' => 'nullable|date',
        'disposal_method' => 'nullable|in:sold,scrapped,donated,lost,stolen,other',
        'disposal_proceeds' => 'nullable|numeric|min:0',
        'disposal_notes' => 'nullable|string|max:1000',
        'disposal_rejection_reason' => 'nullable|string|max:500',
    ];

    protected $listeners = [
        'refreshComponent' => '$refresh',
        'ppeUpdated' => 'refreshData',
        'bulkActionCompleted' => 'refreshData'
    ];

    public function mount()
    {
        $this->refreshData();
        $this->generateChartData();
    }

    public function selectMenu($menuItem)
    {
        $this->selectedMenuItem = $menuItem;
        $this->resetForm();
        $this->resetPage();

        //dd($this->selectedMenuItem);
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedSelectAll()
    {
        if ($this->selectAll) {
            $this->selectedPpes = PPE::query()->pluck('id')->toArray();
        } else {
            $this->selectedPpes = [];
        }
    }

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function refreshData()
    {
        $this->resetPage();
        $this->generateChartData();
    }

    public function getPpes()
    {
        $query = PPE::query();

        // Apply search filter
        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('category', 'like', '%' . $this->search . '%')
                  ->orWhere('location', 'like', '%' . $this->search . '%')
                  ->orWhere('notes', 'like', '%' . $this->search . '%');
            });
        }

        // Apply status filter
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        // Apply sorting
        $query->orderBy($this->sortBy, $this->sortDirection);

        return $query->paginate(15);
    }

    // Bulk Actions
    public function bulkDelete()
    {
        if (!Gate::allows('delete-ppe')) {
            $this->addError('permission', 'You do not have permission to delete PPE assets.');
            return;
        }

        $deletedCount = PPE::whereIn('id', $this->selectedPpes)->delete();
        $this->selectedPpes = [];
        $this->refreshData();
        $this->emit('showNotification', "Successfully deleted {$deletedCount} PPE assets", 'success');
    }

    public function bulkExport()
    {
        $assets = PPE::whereIn('id', $this->selectedPpes)->get();
        
        return response()->streamDownload(function () use ($assets) {
            $this->formatExportData($assets);
        }, 'ppe_assets_' . date('Y-m-d') . '.csv');
    }

    public function bulkDepreciation()
    {
        CalculatePpeDepreciation::dispatch($this->selectedPpes);
        $this->selectedPpes = [];
        $this->refreshData();
        $this->emit('showNotification', 'Depreciation calculation job has been dispatched!', 'success');
    }

    // Individual asset actions
    public function viewAsset($id)
    {
        $this->ppeId = $id;
        $this->selectedMenuItem = 2;
        $this->edit($id);
    }

    public function editAsset($id)
    {
        if (!Gate::allows('edit-ppe')) {
            $this->addError('permission', 'You do not have permission to edit PPE assets.');
            return;
        }
        
        $this->ppeId = $id;
        $this->selectedMenuItem = 2;
        $this->edit($id);
    }

    public function deleteAsset($id)
    {
        if (!Gate::allows('delete-ppe')) {
            $this->addError('permission', 'You do not have permission to delete PPE assets.');
            return;
        }

        $asset = PPE::find($id);
        if ($asset) {
            $asset->delete();
            $this->refreshData();
            $this->emit('showNotification', 'PPE asset deleted successfully', 'success');
        }
    }

    public function initiateDisposal($id)
    {
        $asset = PPE::find($id);
        if ($asset) {
            $asset->update(['status' => 'pending_disposal']);
            
            // Create approval record
            \App\Models\approvals::create([

                'process_name' => 'Dispose Asset',
                'process_description' => auth()->user()->name . ' has requested to dispose asset: ' . $asset->name,
                'approval_process_description' => 'has approved asset disposal',
                'process_code' => 'ASSET_DISP',
                'process_id' => $asset->id,
                'process_status' => 'PENDING',
                'user_id' => auth()->user()->id,
                'team_id' => 1,
                'approver_id' => 1,
                'approval_status' => 'PENDING',
                'edit_package' => null
            ]);
            
            $this->refreshData();
            $this->emit('showNotification', 'Disposal initiated successfully. Awaiting approval.', 'success');
        }
    }

    public function showDisposalForm($id)
    {
        $asset = PPE::find($id);
        if ($asset && ($asset->status === 'approved_for_disposal' || $asset->disposal_approval_status === 'approved')) {
            $this->disposalAssetId = $id;
            $this->disposal_date = now()->format('Y-m-d');
            $this->disposal_method = 'sold';
            $this->disposal_proceeds = 0.0;
            $this->disposal_notes = '';
            $this->showDisposalForm = true;
        } else {
            $this->emit('showNotification', 'Asset is not approved for disposal', 'error');
        }
    }

    public function completeDisposal()
    {
        $this->validate([
            'disposal_date' => 'required|date',
            'disposal_method' => 'required|in:sold,scrapped,donated,lost,stolen,other',
            'disposal_proceeds' => 'required|numeric|min:0',
            'disposal_notes' => 'nullable|string|max:1000'
        ]);

        $asset = PPE::find($this->disposalAssetId);
        if ($asset && ($asset->status === 'approved_for_disposal' || $asset->disposal_approval_status === 'approved')) {
            $asset->update([
                'status' => 'disposed',
                'disposal_date' => $this->disposal_date,
                'disposal_method' => $this->disposal_method,
                'disposal_proceeds' => $this->disposal_proceeds,
                'disposal_notes' => $this->disposal_notes,
                'disposal_approval_status' => 'completed',
                'disposal_approved_by' => auth()->id(),
                'disposal_approved_at' => now()
            ]);

            // Create accounting entry for disposal
            $this->createDisposalAccountingEntry($asset);

            $this->resetDisposalForm();
            $this->refreshData();
            $this->emit('showNotification', 'Disposal completed successfully', 'success');
        } else {
            $this->emit('showNotification', 'Asset is not approved for disposal', 'error');
        }
    }

    private function createDisposalAccountingEntry($asset)
    {
        try {
            $transactionService = new TransactionPostingService();
            
            // Calculate gain or loss
            $gainLoss = $asset->disposal_gain_loss;
            $netBookValue = $asset->closing_value;
            $proceeds = $asset->disposal_proceeds;

            // Create disposal transaction
            $transactionData = [
                'first_account' => $asset->account_number, // Credit PPE Asset Account
                'second_account' => '1001', // Debit Cash/Bank Account (adjust as needed)
                'amount' => $netBookValue,
                'narration' => "PPE Disposal: {$asset->name} - Net Book Value",
                'action' => 'ppe_disposal'
            ];

            $result = $transactionService->postTransaction($transactionData);
            
            if ($result['status'] !== 'success') {
                Log::error('PPE disposal transaction failed', [
                    'error' => $result['message'] ?? 'Unknown error',
                    'asset_id' => $asset->id
                ]);
            }

            // If there's a gain or loss, create additional entry
            if ($gainLoss != 0) {
                $gainLossAccount = $gainLoss > 0 ? '4001' : '5001'; // Adjust account numbers as needed
                $gainLossTransactionData = [
                    'first_account' => $gainLossAccount, // Gain/Loss Account
                    'second_account' => $asset->account_number, // PPE Asset Account
                    'amount' => abs($gainLoss),
                    'narration' => "PPE Disposal: {$asset->name} - " . ($gainLoss > 0 ? 'Gain' : 'Loss'),
                    'action' => 'ppe_disposal_gain_loss'
                ];

                $gainLossResult = $transactionService->postTransaction($gainLossTransactionData);
                
                if ($gainLossResult['status'] !== 'success') {
                    Log::error('PPE disposal gain/loss transaction failed', [
                        'error' => $gainLossResult['message'] ?? 'Unknown error',
                        'asset_id' => $asset->id
                    ]);
                }
            }

            Log::info('PPE disposal accounting entries created successfully', [
                'asset_id' => $asset->id,
                'asset_name' => $asset->name,
                'gain_loss' => $gainLoss
            ]);

        } catch (\Exception $e) {
            Log::error('Error creating PPE disposal accounting entries', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function resetDisposalForm()
    {
        $this->showDisposalForm = false;
        $this->disposalAssetId = null;
        $this->disposal_date = '';
        $this->disposal_method = 'sold';
        $this->disposal_proceeds = 0.0;
        $this->disposal_notes = '';
        $this->disposal_rejection_reason = '';
        $this->resetErrorBag();
        $this->resetValidation();
    }

    // Category Management
    public function toggleCategoryForm()
    {
        $this->showCategoryForm = !$this->showCategoryForm;
        $this->resetCategoryForm();
    }

    public function saveCategory()
    {
        $this->validate([
            'categoryName' => 'required|string|max:255',
            'categoryDescription' => 'nullable|string|max:500'
        ]);

        // Create new category
        PPE::create([
            'name' => $this->categoryName,
            'category' => $this->categoryName,
            'description' => $this->categoryDescription,
            'status' => 'active'
        ]);

        $this->resetCategoryForm();
        $this->refreshData();
        $this->emit('showNotification', 'Category created successfully', 'success');
    }

    public function editCategory($id)
    {
        $this->editingCategoryId = $id;
        // Load category data
        $this->showCategoryForm = true;
    }

    public function deleteCategory($id)
    {
        if (!Gate::allows('delete-category')) {
            $this->addError('permission', 'You do not have permission to delete categories.');
            return;
        }
        
        $category = PPE::find($id);
        if ($category) {
            $category->delete();
            $this->refreshData();
            $this->emit('showNotification', 'Category deleted successfully', 'success');
        }
    }

    public function resetCategoryForm()
    {
        $this->categoryName = '';
        $this->categoryDepreciationRate = '';
        $this->editingCategoryId = null;
    }

    // Reports and Exports
    public function generateReport()
    {
        $assets = PPE::when($this->statusFilter, function ($query) {
            return $query->where('status', $this->statusFilter);
        })->get();

        return response()->streamDownload(function () use ($assets) {
            $this->formatExportData($assets);
        }, 'ppe_report_' . date('Y-m-d') . '.csv');
    }

    public function exportExcel()
    {
        $assets = $this->generateReport();
        
        $this->dispatchBrowserEvent('downloadCSV', [
            'data' => $this->formatExportData($assets),
            'filename' => 'ppe_report_' . date('Y-m-d') . '.csv'
        ]);
    }

    public function exportPdf()
    {
        $assets = $this->generateReport();
        
        // Generate PDF logic here
        $this->emit('showNotification', 'PDF export feature coming soon', 'info');
    }

    private function formatExportData($assets)
    {
        $data = [];
        $data[] = ['Name', 'Category', 'Purchase Date', 'Initial Value', 'Accumulated Depreciation', 'Net Book Value', 'Status'];
        
        foreach ($assets as $asset) {
            $data[] = [
                $asset->name,
                $asset->category,
                $asset->purchase_date,
                $asset->initial_value,
                $asset->accumulated_depreciation,
                $asset->closing_value,
                $asset->status
            ];
        }
        
        return $data;
    }

    // Chart Data Generation
    public function generateChartData()
    {
        $months = [];
        $depreciation = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $months[] = $date->format('M Y');
            
            // Calculate depreciation for assets created up to this month
            // Using depreciation_for_year divided by 12 for monthly depreciation
            $assetsUpToDate = PPE::where('created_at', '<=', $date->endOfMonth())->get();
            $monthlyDep = 0;
            
            foreach ($assetsUpToDate as $asset) {
                // Calculate monthly depreciation from yearly depreciation
                $monthlyDep += ($asset->depreciation_for_year ?? 0) / 12;
            }
            
            $depreciation[] = round($monthlyDep, 2);
        }
        
        $this->chartLabels = $months;
        $this->chartData = $depreciation;
        
        $this->dispatchBrowserEvent('chart-updated', [
            'labels' => $this->chartLabels,
            'data' => $this->chartData
        ]);
    }

    // Computed Properties for Dashboard
    public function getTotalPpeValueProperty()
    {
        // Calculate total PPE value (initial value minus accumulated depreciation)
        $totalInitialValue = PPE::sum('initial_value') ?? 0;
        $totalAccumulatedDepreciation = PPE::sum('accumulated_depreciation') ?? 0;
        return $totalInitialValue - $totalAccumulatedDepreciation;
    }

    public function getTotalAccumulatedDepreciationProperty()
    {
        return PPE::sum('accumulated_depreciation');
    }

    public function getNetBookValueProperty()
    {
        return $this->totalPpeValue - $this->totalAccumulatedDepreciation;
    }

    public function getMonthlyDepreciationProperty()
    {
        // Calculate current month's depreciation from yearly depreciation
        $totalYearlyDepreciation = PPE::sum('depreciation_for_year') ?? 0;
        return round($totalYearlyDepreciation / 12, 2);
    }

    public function getRecentPpesProperty()
    {
        return PPE::orderBy('created_at', 'desc')->limit(5)->get();
    }

    public function getPendingDisposalsProperty()
    {
        return PPE::where('status', 'pending_disposal')->limit(5)->get();
    }

    public function getPendingApprovalDisposalsProperty()
    {
        return PPE::where('status', 'pending_disposal')
                  ->where('disposal_approval_status', 'pending')
                  ->limit(5)->get();
    }

    public function getApprovedForDisposalProperty()
    {
        return PPE::where('status', 'approved_for_disposal')
                  ->limit(5)->get();
    }

    public function getApprovedDisposalsProperty()
    {
        return PPE::where('disposal_approval_status', 'approved')
                  ->where('status', 'pending_disposal')
                  ->limit(5)->get();
    }

    public function getRejectedDisposalsProperty()
    {
        return PPE::where('disposal_approval_status', 'rejected')
                  ->limit(5)->get();
    }

    public function getCompletedDisposalsProperty()
    {
        return PPE::where('status', 'disposed')
                  ->whereNotNull('disposal_date')
                  ->orderBy('disposal_date', 'desc')
                  ->limit(5)->get();
    }

    public function getCategoriesProperty()
    {
        // Get category statistics from actual PPE data
        $categories = PPE::select('category', 
                                 DB::raw('COUNT(*) as asset_count'),
                                 DB::raw('SUM(initial_value) as total_value'),
                                 DB::raw('AVG(depreciation_rate) as avg_depreciation_rate'))
                        ->groupBy('category')
                        ->get()
                        ->map(function($cat, $index) {
                            return [
                                'id' => $index + 1,
                                'name' => ucfirst($cat->category),
                                'depreciation_rate' => round($cat->avg_depreciation_rate, 2),
                                'asset_count' => $cat->asset_count,
                                'total_value' => $cat->total_value
                            ];
                        })
                        ->toArray();

        // Add default categories if none exist
        if (empty($categories)) {
            return [
                ['id' => 1, 'name' => 'Buildings', 'depreciation_rate' => 2.5, 'asset_count' => 0, 'total_value' => 0],
                ['id' => 2, 'name' => 'Vehicles', 'depreciation_rate' => 20, 'asset_count' => 0, 'total_value' => 0],
                ['id' => 3, 'name' => 'Equipment', 'depreciation_rate' => 15, 'asset_count' => 0, 'total_value' => 0],
                ['id' => 4, 'name' => 'Furniture', 'depreciation_rate' => 10, 'asset_count' => 0, 'total_value' => 0],
                ['id' => 5, 'name' => 'Software', 'depreciation_rate' => 33.33, 'asset_count' => 0, 'total_value' => 0]
            ];
        }

        return $categories;
    }

    public function getPpeCategoriesProperty()
    {
        // Get PPE categories from accounts table based on institution's property_and_equipment_account
        $institution = \App\Models\institutions::find(1); // Assuming institution ID 1, adjust as needed
        $propertyAccount = $institution ? $institution->property_and_equipment_account : null;
        
        if ($propertyAccount) {
            return AccountsModel::where('parent_account_number', $propertyAccount)
                               ->where('account_use', 'internal')
                               ->get();
        }
        
        return collect();
    }

    public function getSampleReportProperty()
    {
        return PPE::limit(10)->get();
    }
    
    // Additional computed properties for dashboard
    public function getTotalAssetCountProperty()
    {
        return PPE::count();
    }
    
    public function getActiveAssetsCountProperty()
    {
        return PPE::where('status', 'active')->count();
    }
    
    public function getPendingDisposalCountProperty()
    {
        return PPE::where('status', 'pending_disposal')->count();
    }
    
    public function getPendingApprovalCountProperty()
    {
        return PPE::where('disposal_approval_status', 'pending')->count();
    }
    
    public function getApprovedDisposalCountProperty()
    {
        return PPE::where('disposal_approval_status', 'approved')->count();
    }
    
    public function getRejectedDisposalCountProperty()
    {
        return PPE::where('disposal_approval_status', 'rejected')->count();
    }
    
    public function getCompletedDisposalCountProperty()
    {
        return PPE::where('status', 'disposed')->count();
    }
    
    public function getAssetsForDisposalProperty()
    {
        return PPE::whereIn('status', ['pending_disposal', 'under_repair'])
                  ->orderBy('created_at', 'desc')
                  ->get();
    }

    // Existing methods (updated versions)
    public function updated($field)
    {
        $this->validateOnly($field);
        
        // Cast numeric fields to float
        $numericFields = [
            'purchase_price', 'salvage_value', 'useful_life', 'quantity',
            'legal_fees', 'registration_fees', 'renovation_costs',
            'transportation_costs', 'installation_costs', 'other_costs'
        ];
        
        if (in_array($field, $numericFields)) {
            $this->$field = (float)($this->$field ?? 0);
        }
        
        $this->calculateValues();
    }

    public function calculateValues()
    {
        // Calculate total capitalized cost including all additional costs
        $total_capitalized_cost = (float)$this->purchase_price + 
                                 (float)$this->legal_fees + 
                                 (float)$this->registration_fees + 
                                 (float)$this->renovation_costs + 
                                 (float)$this->transportation_costs + 
                                 (float)$this->installation_costs + 
                                 (float)$this->other_costs;
        
        $this->initial_value = $total_capitalized_cost * (int)$this->quantity;

        if ($this->useful_life > 0 && $this->salvage_value >= 0) {
            $this->depreciation_rate = (($total_capitalized_cost - $this->salvage_value) / $this->useful_life) * 100;
        }

        $years_in_use = max((date('Y') - date('Y', strtotime($this->purchase_date))), 0);

        if ($this->depreciation_rate > 0) {
            $this->accumulated_depreciation = ($this->depreciation_rate / 100) * $this->initial_value * $years_in_use;
        } else {
            $this->accumulated_depreciation = 0;
        }

        if ($this->useful_life > 0) {
            $this->depreciation_for_year = ($total_capitalized_cost - $this->salvage_value) / $this->useful_life;
        } else {
            $this->depreciation_for_year = 0;
        }

        $this->closing_value = $this->initial_value - $this->accumulated_depreciation;
    }

    public function store()
    {
        //dd($this->purchase_price);
        $this->validate();

        // Calculate values before storing
        $this->calculateValues();

        // Get the account name for the selected category
        $account = AccountsModel::where('account_number', $this->categoryx)->first();
        $category_name = $account ? $account->account_name : $this->categoryx;

        $ppe = PPE::create([
            'name' => ucwords($this->name),   //to uppercase 
            'category' => $category_name,
            'purchase_price' => $this->purchase_price,
            'purchase_date' => $this->purchase_date,
            'salvage_value' => $this->salvage_value,
            'useful_life' => $this->useful_life,
            'quantity' => $this->quantity,
            'initial_value' => $this->initial_value,
            'depreciation_rate' => $this->depreciation_rate,
            'accumulated_depreciation' => $this->accumulated_depreciation,
            'depreciation_for_year' => $this->depreciation_for_year,
            'depreciation_for_month' => round($this->depreciation_for_year / 12, 2),
            'closing_value' => $this->closing_value,
            'status' => $this->status,
            'location' => $this->location,
            'account_number' => $this->categoryx, // Use the selected account number
            'notes' => $this->notes,
            // Additional costs
            'legal_fees' => $this->legal_fees,
            'registration_fees' => $this->registration_fees,
            'renovation_costs' => $this->renovation_costs,
            'transportation_costs' => $this->transportation_costs,
            'installation_costs' => $this->installation_costs,
            'other_costs' => $this->other_costs,
            // Payment method and accounts
            'payment_method' => $this->payment_method,
            'payment_account_number' => $this->payment_account_number,
            'payable_account_number' => $this->payable_account_number,
            // Additional fields
            'supplier_name' => $this->supplier_name,
            'invoice_number' => $this->invoice_number,
            'invoice_date' => $this->invoice_date,
            'additional_notes' => $this->additional_notes,
        ]);


        // account creation service
        $accountService = new AccountCreationService();
        $ppeAccount = $accountService->createAccount([
            'account_use' => 'internal',
            'account_name' => $category_name.': '.$this->name,
            'type' => 'asset_account',
            'product_number' => '0000',
            'member_number' => '00000',
            'branch_number' => auth()->user()->branch
        ], $this->categoryx);



        if (!empty($ppeAccount)) {
            $totalAmount = $this->initial_value;
            
            // Post the transaction using TransactionPostingService
            $transactionService = new TransactionPostingService();
            $transactionData = [
                'first_account' => $ppeAccount->account_number, // Debit account 
                'second_account' => $this->categoryx, // Credit account 
                'amount' => $totalAmount,
                'narration' => 'PPE asset : ' . ucwords($this->name) . ' : ' . $this->categoryx,
                'action' => 'ppe_asset'
            ];

            Log::info('Posting savings deposit transaction', [
                'transaction_data' => $transactionData
            ]);

            $result = $transactionService->postTransaction($transactionData);
            
            if ($result['status'] !== 'success') {
                Log::error('Transaction posting failed', [
                    'error' => $result['message'] ?? 'Unknown error',
                    'transaction_data' => $transactionData
                ]);
                throw new \Exception('Failed to post transaction: ' . ($result['message'] ?? 'Unknown error'));
            }

            Log::info('Transaction posted successfully', [
                'transaction_reference' => $result['reference'] ?? null,
                'amount' => $totalAmount
            ]);

        }


        // loop through the additional costs and create accounts for them
        $additional_costs = ['legal_fees', 'registration_fees', 'renovation_costs', 'transportation_costs', 'installation_costs', 'other_costs'];
        foreach ($additional_costs as $cost) {
            $totalAmount = $this->$cost;
            if ($totalAmount > 0) {
            $transactionData = [
                'first_account' => $ppeAccount->account_number, // Debit account 
                'second_account' => $this->categoryx, // Credit account 
                'amount' => $totalAmount, // amount is the additional cost
                'narration' => 'PPE asset : ' . ucwords($this->name) . ' : ' . $this->categoryx . ' : ' . ucwords($cost),
                'action' => 'ppe_asset'
            ];

            $result = $transactionService->postTransaction($transactionData);

            if ($result['status'] !== 'success') {
                Log::error('Transaction posting failed', [
                    'error' => $result['message'] ?? 'Unknown error',
                    'transaction_data' => $transactionData
                ]);
                throw new \Exception('Failed to post transaction: ' . ($result['message'] ?? 'Unknown error'));
            }

            Log::info('Transaction posted successfully', [
                'transaction_reference' => $result['reference'] ?? null,
                    'amount' => $totalAmount
                ]);
            }
        }
        

      
        $this->resetForm();
        $this->refreshData();
        $this->selectedMenuItem = 3;
        
        session()->flash('message', 'PPE asset created successfully');
        $this->emit('formSubmitted');
        $this->emit('showNotification', 'PPE asset created successfully', 'success');
    }

    public function edit($id)
    {
        $ppe = PPE::find($id);
        if ($ppe) {
            $this->ppeId = $ppe->id;
            $this->name = $ppe->name;
            $this->categoryx = $ppe->account_number; // Use account_number instead of category
            $this->purchase_price = (float)$ppe->purchase_price;
            $this->purchase_date = $ppe->purchase_date;
            $this->salvage_value = (float)$ppe->salvage_value;
            $this->useful_life = (int)$ppe->useful_life;
            $this->quantity = (int)$ppe->quantity;
            $this->additions = $ppe->additions;
            $this->status = $ppe->status;
            $this->location = $ppe->location;
            $this->notes = $ppe->notes;
            
            // Load additional costs
            $this->legal_fees = (float)($ppe->legal_fees ?? 0.0);
            $this->registration_fees = (float)($ppe->registration_fees ?? 0.0);
            $this->renovation_costs = (float)($ppe->renovation_costs ?? 0.0);
            $this->transportation_costs = (float)($ppe->transportation_costs ?? 0.0);
            $this->installation_costs = (float)($ppe->installation_costs ?? 0.0);
            $this->other_costs = (float)($ppe->other_costs ?? 0.0);
            
            // Load payment method and related fields
            $this->payment_method = $ppe->payment_method ?? 'cash';
            $this->payment_account_number = $ppe->payment_account_number ?? '';
            $this->payable_account_number = $ppe->payable_account_number ?? '';
            
            // Load additional fields
            $this->supplier_name = $ppe->supplier_name ?? '';
            $this->invoice_number = $ppe->invoice_number ?? '';
            $this->invoice_date = $ppe->invoice_date ?? '';
            $this->additional_notes = $ppe->additional_notes ?? '';
            
            // Load disposal fields
            $this->disposal_date = $ppe->disposal_date ?? '';
            $this->disposal_method = $ppe->disposal_method ?? 'sold';
            $this->disposal_proceeds = (float)($ppe->disposal_proceeds ?? 0.0);
            $this->disposal_notes = $ppe->disposal_notes ?? '';
            $this->disposal_approval_status = $ppe->disposal_approval_status ?? 'pending';
            $this->disposal_rejection_reason = $ppe->disposal_rejection_reason ?? '';
            
            $this->isEditMode = true;
        }
    }

    public function update()
    {
        $this->validate();

        // Calculate values before updating
        $this->calculateValues();

        $ppe = PPE::find($this->ppeId);
        
        if ($ppe) {
            // Get the account name for the selected category
            $account = AccountsModel::where('account_number', $this->categoryx)->first();
            $category_name = $account ? $account->account_name : $this->categoryx;

            $ppe->update([
                'name' => ucwords($this->name), //to uppercase
                'category' => $category_name,
                'purchase_price' => $this->purchase_price,
                'purchase_date' => $this->purchase_date,
                'salvage_value' => $this->salvage_value,
                'useful_life' => $this->useful_life,
                'quantity' => $this->quantity,
                'initial_value' => $this->initial_value,
                'depreciation_rate' => $this->depreciation_rate,
                'accumulated_depreciation' => $this->accumulated_depreciation,
                'depreciation_for_year' => $this->depreciation_for_year,
                'depreciation_for_month' => round($this->depreciation_for_year / 12, 2),
                'closing_value' => $this->closing_value,
                'status' => $this->status,
                'location' => $this->location,
                'account_number' => $this->categoryx, // Use the selected account number
                'notes' => $this->notes,
            ]);
        }

        $this->resetForm();
        $this->refreshData();
        $this->selectedMenuItem = 3;
        
        session()->flash('message', 'PPE asset updated successfully');
        $this->emit('formSubmitted');
        $this->emit('showNotification', 'PPE asset updated successfully', 'success');
    }

    public function delete($id)
    {
        if (!Gate::allows('delete-ppe')) {
            $this->addError('permission', 'You do not have permission to delete PPE assets.');
            return;
        }

        $ppe = PPE::find($id);
        if ($ppe) {
            $ppe->delete();
            $this->refreshData();
            $this->emit('showNotification', 'PPE asset deleted successfully', 'success');
        }
    }

    public function resetForm()
    {
        $this->name = '';
        $this->categoryx = '';
        $this->purchase_price = 0.0;
        $this->purchase_date = '';
        $this->salvage_value = 0.0;
        $this->useful_life = 1;
        $this->quantity = 1;
        $this->additions = '';
        $this->status = 'active';
        $this->location = '';
        $this->notes = '';
        
        // Reset additional costs
        $this->legal_fees = 0.0;
        $this->registration_fees = 0.0;
        $this->renovation_costs = 0.0;
        $this->transportation_costs = 0.0;
        $this->installation_costs = 0.0;
        $this->other_costs = 0.0;
        
        // Reset payment method and related fields
        $this->payment_method = 'cash';
        $this->payment_account_number = '';
        $this->payable_account_number = '';
        
        // Reset additional fields
        $this->supplier_name = '';
        $this->invoice_number = '';
        $this->invoice_date = '';
        $this->additional_notes = '';
        
        // Reset disposal fields
        $this->disposal_date = '';
        $this->disposal_method = 'sold';
        $this->disposal_proceeds = 0.0;
        $this->disposal_notes = '';
        $this->disposal_approval_status = 'pending';
        $this->disposal_rejection_reason = '';
        $this->showDisposalForm = false;
        $this->disposalAssetId = null;
        $this->showRejectionModal = false;
        $this->rejectionAssetId = null;
        
        // Reset computed fields
        $this->initial_value = 0.0;
        $this->depreciation_rate = 0.0;
        $this->accumulated_depreciation = 0.0;
        $this->depreciation_for_year = 0.0;
        $this->closing_value = 0.0;
        
        $this->isEditMode = false;
        $this->ppeId = null;
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function runDepreciationJob()
    {
        CalculatePpeDepreciation::dispatch();
        $this->emit('showNotification', 'Depreciation calculation job has been dispatched!', 'success');
    }
    
    public function runDepreciation()
    {
        $this->runDepreciationJob();
    }

    // Helper methods
    public function createNewAccountNumber($major_category_code, $category_code, $sub_category_code, $parent_account)
    {
        $formattedAccountName = strtolower(trim(preg_replace('/[^a-zA-Z0-9\s]/', '', $this->name)));
        $formattedAccountName = str_replace(' ', '_', $formattedAccountName);
        $formattedAccountName = strtoupper($formattedAccountName);

        $account_number = $this->generate_account_number(auth()->user()->branch, $sub_category_code);

        AccountsModel::create([
            'account_use' => 'internal',
            'institution_number' => auth()->user()->institution_id,
            'branch_number' => auth()->user()->branch,
            'major_category_code' => $major_category_code,
            'category_code' => $category_code,
            'sub_category_code' => $sub_category_code,
            'account_name' => $formattedAccountName,
            'account_number' => $account_number,
            'notes' => $this->name,
            'account_level' => '3',
            'parent_account_number' => $parent_account,
            'type' => 'asset_account'
        ]);

        return $account_number;
    }

    function luhn_checksum($number)
    {
        $digits = str_split($number);
        $sum = 0;
        $alt = false;
        for ($i = count($digits) - 1; $i >= 0; $i--) {
            $n = $digits[$i];
            if ($alt) {
                $n *= 2;
                if ($n > 9) {
                    $n -= 9;
                }
            }
            $sum += $n;
            $alt = !$alt;
        }
        return $sum % 10;
    }

    public function generate_account_number($branch_code, $product_code): string
    {
        do {
            $unique_identifier = str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);
            $partial_account_number = $branch_code . $unique_identifier . $product_code;
            $checksum = (10 - $this->luhn_checksum($partial_account_number . '0')) % 10;
            $full_account_number = $partial_account_number . $checksum;
            $is_unique = !AccountsModel::where('account_number', $full_account_number)->exists();
        } while (!$is_unique);

        return $full_account_number;
    }


    public function render()
    {
        return view('livewire.accounting.ppe-management', [
            'ppes' => $this->getPpes(),
        ]);
    }
}
