<?php

namespace App\Http\Livewire\Savings;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Account;
use App\Models\general_ledger;
use App\Models\MandatorySavingsTracking;
use App\Models\MandatorySavingsNotification;
use App\Models\MandatorySavingsSettings;
use App\Services\MandatorySavingsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Exception;

class MonthlyReport extends Component
{
    use WithPagination;

    // Properties for filters
    public $selectedMonth;
    public $selectedYear;
    public $selectedProduct;
    public $selectedStatus = '';
    public $searchTerm = '';
    public $showArrearsOnly = false;
    public $activeTab = 'overview'; // overview, mandatory, transactions, products

    // Properties for data
    public $monthlyData;
    protected $productData;
    protected $productStats;
    protected $transactionData;
    public $summaryData;
    protected $mandatorySavingsData;
    public $mandatorySavingsStats;

    // Properties for mandatory savings management
    public $monthlyAmount;
    public $dueDay;
    public $gracePeriodDays;
    public $enableNotifications;
    public $firstReminderDays;
    public $secondReminderDays;
    public $finalReminderDays;

    // Loading States
    public $isLoading = false;
    public $isProcessing = false;
    public $processingMessage = '';

    // Messages
    public $errorMessage = '';
    public $successMessage = '';

    // Properties for modals
    public $showSettingsModal = false;
    public $showArrearsModal = false;
    protected $selectedMember = null;

    protected $paginationTheme = 'bootstrap';

    protected function rules()
    {
        return [
            'selectedMonth' => 'required|integer|between:1,12',
            'selectedYear' => 'required|integer|min:2000|max:' . (date('Y') + 1),
            'selectedProduct' => 'nullable|exists:sub_products,sub_product_id',
            'monthlyAmount' => 'required|numeric|min:0',
            'dueDay' => 'required|integer|min:1|max:31',
            'gracePeriodDays' => 'required|integer|min:0|max:30',
            'firstReminderDays' => 'required|integer|min:1|max:30',
            'secondReminderDays' => 'required|integer|min:1|max:30',
            'finalReminderDays' => 'required|integer|min:1|max:30',
        ];
    }

    public function mount()
    {
        $this->selectedMonth = Carbon::now()->month;
        $this->selectedYear = Carbon::now()->year;
        
        // Initialize data properties to prevent null errors
        $this->productData = collect([]);
        $this->productStats = collect([]);
        $this->transactionData = collect([]);
        $this->mandatorySavingsData = null;
        $this->monthlyData = [];
        $this->summaryData = [];
        
        $this->loadSettings();
        $this->loadData();
    }

    public function loadSettings()
    {
        $settings = MandatorySavingsSettings::forInstitution('1');
        if ($settings) {
            $this->monthlyAmount = $settings->monthly_amount;
            $this->dueDay = $settings->due_day;
            $this->gracePeriodDays = $settings->grace_period_days;
            $this->enableNotifications = $settings->enable_notifications;
            $this->firstReminderDays = $settings->first_reminder_days;
            $this->secondReminderDays = $settings->second_reminder_days;
            $this->finalReminderDays = $settings->final_reminder_days;
        }
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
        if (in_array($propertyName, ['selectedMonth', 'selectedYear', 'selectedProduct'])) {
            $this->loadData();
        }
        if (in_array($propertyName, ['selectedStatus', 'searchTerm', 'showArrearsOnly'])) {
            $this->resetPage();
        }
    }

    public function loadData()
    {
        try {
            $this->isLoading = true;

            // Generate cache key based on filters
            $cacheKey = "monthly_report_{$this->selectedYear}_{$this->selectedMonth}" . ($this->selectedProduct ? "_{$this->selectedProduct}" : '');

            // Load regular savings data with caching
            $this->monthlyData = Cache::remember($cacheKey, 300, function () {
                $query = general_ledger::with(['account.client', 'account.shareProduct'])
                    ->whereHas('account', function ($query) {
                        $query->where('major_category_code', 2000);
                        if ($this->selectedProduct) {
                            $query->where('product_number', $this->selectedProduct);
                        }
                    })
                    ->whereYear('created_at', $this->selectedYear)
                    ->whereMonth('created_at', $this->selectedMonth);

                return [
                    'transactions' => $query->get(),
                    'summary' => [
                        'total_deposits' => $query->sum('credit'),
                        'total_withdrawals' => $query->sum('debit'),
                        'net_change' => $query->sum('credit') - $query->sum('debit'),
                        'transaction_count' => $query->count()
                    ]
                ];
            });

            // Extract data
            $this->transactionData = $this->monthlyData['transactions'];
            $this->summaryData = $this->monthlyData['summary'];

            // Load product-specific data
            $this->productData = Cache::remember("product_data_{$cacheKey}", 300, function () {
                return DB::table('sub_products')
                    ->where('product_type', 2000)
                    ->where('status', 'ACTIVE')
                    ->select(
                        'sub_product_id as id',
                        'product_name'
                    )
                    ->get();
            });

            // Load product statistics for the products tab
            $this->productStats = Cache::remember("product_stats_{$cacheKey}", 300, function () {
                return DB::table('accounts')
                    ->join('sub_products', 'accounts.product_number', '=', 'sub_products.sub_product_id')
                    ->where('accounts.major_category_code', 2000)
                    ->when($this->selectedProduct, function ($query) {
                        return $query->where('accounts.product_number', $this->selectedProduct);
                    })
                    ->select(
                        'sub_products.product_name',
                        DB::raw('COUNT(DISTINCT accounts.id) as account_count'),
                        DB::raw('SUM(accounts.balance) as total_balance')
                    )
                    ->groupBy('sub_products.product_name')
                    ->get();
            });

            // Load mandatory savings data
            $this->loadMandatorySavingsData();

        } catch (Exception $e) {
            Log::error('Error loading monthly report data: ' . $e->getMessage());
            $this->errorMessage = 'Failed to load monthly report data. Please try again.';
            
            // Initialize empty collections on error
            $this->productData = collect([]);
            $this->productStats = collect([]);
            $this->transactionData = collect([]);
            $this->mandatorySavingsData = null;
            $this->monthlyData = [];
            $this->summaryData = [];
        } finally {
            $this->isLoading = false;
        }
    }

    public function loadMandatorySavingsData()
    {
        try {
            $service = new MandatorySavingsService();
            
            // Get mandatory savings statistics
            $this->mandatorySavingsStats = $service->getSummaryStatistics($this->selectedYear, $this->selectedMonth);
            
            // Get tracking records for the selected period
            $query = MandatorySavingsTracking::with(['client', 'account'])
                ->where('year', $this->selectedYear)
                ->where('month', $this->selectedMonth);

            // Apply filters
            if ($this->selectedStatus) {
                $query->where('status', $this->selectedStatus);
            }

            if ($this->searchTerm) {
                $query->whereHas('client', function ($q) {
                    $q->where('first_name', 'like', '%' . $this->searchTerm . '%')
                      ->orWhere('last_name', 'like', '%' . $this->searchTerm . '%')
                      ->orWhere('client_number', 'like', '%' . $this->searchTerm . '%');
                });
            }

            if ($this->showArrearsOnly) {
                $query->where('balance', '>', 0);
            }

            $this->mandatorySavingsData = $query->paginate(20);

        } catch (Exception $e) {
            Log::error('Error loading mandatory savings data: ' . $e->getMessage());
            $this->errorMessage = 'Failed to load mandatory savings data.';
        }
    }

    public function refreshData()
    {
        try {
            $this->isProcessing = true;
            
            // Clear cache
            $cacheKey = "monthly_report_{$this->selectedYear}_{$this->selectedMonth}" . ($this->selectedProduct ? "_{$this->selectedProduct}" : '');
            Cache::forget($cacheKey);
            Cache::forget("product_data_{$cacheKey}");
            Cache::forget("product_stats_{$cacheKey}");
            
            // Reload data
            $this->loadData();
            
            $this->successMessage = 'Data refreshed successfully.';
        } catch (Exception $e) {
            Log::error('Error refreshing monthly report data: ' . $e->getMessage());
            $this->errorMessage = 'Failed to refresh data. Please try again.';
        } finally {
            $this->isProcessing = false;
        }
    }

    // Mandatory Savings Management Methods
    public function generateTrackingRecords()
    {
        try {
            $this->isLoading = true;
            $this->processingMessage = 'Generating tracking records...';

            $service = new MandatorySavingsService();
            $result = $service->generateTrackingRecords($this->selectedYear, $this->selectedMonth);

            $this->successMessage = "Successfully generated {$result['created']} new records and updated {$result['updated']} existing records for {$result['total_members']} members.";
            $this->loadMandatorySavingsData();

        } catch (Exception $e) {
            $this->errorMessage = 'Error generating tracking records: ' . $e->getMessage();
            Log::error('Error generating tracking records: ' . $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    public function updateFromPayments()
    {
        try {
            $this->isLoading = true;

            $service = new MandatorySavingsService();
            $result = $service->updateTrackingFromPayments($this->selectedYear, $this->selectedMonth);

            $this->successMessage = "Successfully updated {$result['updated_records']} records from {$result['total_payments']} payments.";
            $this->loadMandatorySavingsData();

        } catch (Exception $e) {
            $this->errorMessage = 'Error updating from payments: ' . $e->getMessage();
            Log::error('Error updating from payments: ' . $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    public function generateNotifications()
    {
        try {
            $this->isLoading = true;

            $service = new MandatorySavingsService();
            $result = $service->generateNotifications($this->selectedYear, $this->selectedMonth);

            $this->successMessage = "Successfully generated {$result['notifications_created']} notifications for {$result['members_notified']} members.";

        } catch (Exception $e) {
            $this->errorMessage = 'Error generating notifications: ' . $e->getMessage();
            Log::error('Error generating notifications: ' . $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    public function processOverdueRecords()
    {
        try {
            $this->isLoading = true;

            $service = new MandatorySavingsService();
            $result = $service->processOverdueRecords();

            $this->successMessage = "Successfully processed {$result['updated_records']} overdue records.";
            $this->loadMandatorySavingsData();

        } catch (Exception $e) {
            $this->errorMessage = 'Error processing overdue records: ' . $e->getMessage();
            Log::error('Error processing overdue records: ' . $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    public function showArrearsReport()
    {
        try {
            $this->isLoading = true;

            $service = new MandatorySavingsService();
            $arrearsData = $service->calculateArrears();

            $this->selectedMember = null;
            $this->showArrearsModal = true;

        } catch (Exception $e) {
            $this->errorMessage = 'Error calculating arrears: ' . $e->getMessage();
            Log::error('Error calculating arrears: ' . $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    public function showSettings()
    {
        $this->showSettingsModal = true;
    }

    public function saveSettings()
    {
        $this->validate();

        try {
            $settings = MandatorySavingsSettings::forInstitution('1');
            
            if (!$settings) {
                $settings = new MandatorySavingsSettings();
                $settings->institution_id = '1';
            }

            $settings->update([
                'monthly_amount' => $this->monthlyAmount,
                'due_day' => $this->dueDay,
                'grace_period_days' => $this->gracePeriodDays,
                'enable_notifications' => $this->enableNotifications,
                'first_reminder_days' => $this->firstReminderDays,
                'second_reminder_days' => $this->secondReminderDays,
                'final_reminder_days' => $this->finalReminderDays,
            ]);

            $this->successMessage = 'Settings saved successfully.';
            $this->showSettingsModal = false;

        } catch (Exception $e) {
            $this->errorMessage = 'Error saving settings: ' . $e->getMessage();
            Log::error('Error saving settings: ' . $e->getMessage());
        }
    }

    public function viewMemberDetails($clientNumber)
    {
        $this->selectedMember = \App\Models\ClientsModel::where('client_number', $clientNumber)->first();
        if ($this->selectedMember) {
            $this->showArrearsModal = true;
        }
    }

    public function clearMessages()
    {
        $this->errorMessage = '';
        $this->successMessage = '';
    }

    public function getMandatorySavingsData()
    {
        return $this->mandatorySavingsData;
    }

    public function getProductData()
    {
        return $this->productData;
    }

    public function getProductStats()
    {
        return $this->productStats;
    }

    public function getTransactionData()
    {
        return $this->transactionData;
    }

    public function getSelectedMember()
    {
        return $this->selectedMember;
    }

    public function render()
    {
        return view('livewire.savings.monthly-report', [
            'mandatorySavingsData' => $this->mandatorySavingsData ?? null,
            'productData' => $this->productData ?? collect([]),
            'productStats' => $this->productStats ?? collect([]),
            'transactionData' => $this->transactionData ?? collect([]),
            'selectedMember' => $this->selectedMember ?? null
        ]);
    }
}
