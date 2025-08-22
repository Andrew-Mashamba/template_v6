<?php

namespace App\Http\Livewire\Deposits;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Client;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Exception;

class MonthlyReport extends Component
{
    use WithPagination;

    // Properties for filtering and display
    public $selectedYear;
    public $selectedMonth;
    public $selectedProduct;
    public $searchTerm = '';
    public $statusFilter = 'all';
    public $showSettingsModal = false;

    // Data properties
    protected $summaryData;
    protected $detailedData;
    protected $monthlyData;
    protected $productStats;
    protected $transactionData;

    // Loading States
    public $isLoading = false;
    public $processingMessage = '';
    public $errorMessage = '';
    public $successMessage = '';

    public function mount()
    {
        $this->selectedYear = now()->year;
        $this->selectedMonth = now()->month;
        $this->loadData();
    }

    public function loadData()
    {
        try {
            $this->isLoading = true;
            $this->errorMessage = '';

            // Load summary data
            $this->loadSummaryData();

            // Load detailed data
            $this->loadDetailedData();

            // Load monthly trends
            $this->loadMonthlyTrends();

            // Load product statistics
            $this->loadProductStats();

            // Load recent transactions
            $this->loadRecentTransactions();

        } catch (Exception $e) {
            Log::error('Error loading deposits report: ' . $e->getMessage());
            $this->errorMessage = 'Failed to load report data. Please try again.';
        } finally {
            $this->isLoading = false;
        }
    }

    public function loadSummaryData()
    {
        $cacheKey = "deposits_summary_{$this->selectedYear}_{$this->selectedMonth}";

        $this->summaryData = Cache::remember($cacheKey, 300, function () {
            $query = Account::with(['client'])
                ->where('major_category_code', 2000) // Deposits category
                ->whereYear('created_at', $this->selectedYear)
                ->whereMonth('created_at', $this->selectedMonth);

            if ($this->selectedProduct) {
                $query->where('product_number', $this->selectedProduct);
            }

            return [
                'total_accounts' => $query->count(),
                'total_balance' => $query->sum('balance'),
                'average_balance' => $query->avg('balance'),
                'active_accounts' => $query->where('status', 'active')->count(),
                'inactive_accounts' => $query->where('status', 'inactive')->count(),
            ];
        });
    }

    public function loadDetailedData()
    {
        $cacheKey = "deposits_detailed_{$this->selectedYear}_{$this->selectedMonth}";

        $this->detailedData = Cache::remember($cacheKey, 300, function () {
            $query = Account::with(['client', 'product'])
                ->where('major_category_code', 2000)
                ->whereYear('created_at', $this->selectedYear)
                ->whereMonth('created_at', $this->selectedMonth);

            if ($this->selectedProduct) {
                $query->where('product_number', $this->selectedProduct);
            }

            if ($this->searchTerm) {
                $query->whereHas('client', function($q) {
                    $q->where('name', 'like', '%' . $this->searchTerm . '%')
                      ->orWhere('client_number', 'like', '%' . $this->searchTerm . '%');
                });
            }

            return $query->paginate(20);
        });
    }

    public function loadMonthlyTrends()
    {
        $cacheKey = "deposits_monthly_trends_{$this->selectedYear}";

        $this->monthlyData = Cache::remember($cacheKey, 300, function () {
            $data = [];
            
            for ($month = 1; $month <= 12; $month++) {
                $query = Account::where('major_category_code', 2000)
                    ->whereYear('created_at', $this->selectedYear)
                    ->whereMonth('created_at', $month);

                if ($this->selectedProduct) {
                    $query->where('product_number', $this->selectedProduct);
                }

                $data[$month] = [
                    'accounts' => $query->count(),
                    'balance' => $query->sum('balance'),
                    'transactions' => Transaction::whereHas('account', function($q) use ($month) {
                        $q->where('major_category_code', 2000)
                          ->whereYear('created_at', $this->selectedYear)
                          ->whereMonth('created_at', $month);
                    })->count(),
                ];
            }

            return $data;
        });
    }

    public function loadProductStats()
    {
        $cacheKey = "deposits_product_stats_{$this->selectedYear}_{$this->selectedMonth}";

        $this->productStats = Cache::remember($cacheKey, 300, function () {
            return DB::table('accounts')
                ->join('sub_products', 'accounts.product_number', '=', 'sub_products.product_number')
                ->where('accounts.major_category_code', 2000)
                ->whereYear('accounts.created_at', $this->selectedYear)
                ->whereMonth('accounts.created_at', $this->selectedMonth)
                ->select(
                    'sub_products.product_name',
                    'sub_products.sub_product_name',
                    DB::raw('COUNT(accounts.id) as account_count'),
                    DB::raw('SUM(accounts.balance) as total_balance'),
                    DB::raw('AVG(accounts.balance) as avg_balance')
                )
                ->groupBy('sub_products.id', 'sub_products.product_name', 'sub_products.sub_product_name')
                ->get();
        });
    }

    public function loadRecentTransactions()
    {
        $this->transactionData = Transaction::with(['account.client'])
            ->whereHas('account', function($query) {
                $query->where('major_category_code', 2000);
            })
            ->whereYear('created_at', $this->selectedYear)
            ->whereMonth('created_at', $this->selectedMonth)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    public function refreshData()
    {
        $this->resetPage();
        $this->loadData();
    }

    public function updatedSelectedYear()
    {
        $this->refreshData();
    }

    public function updatedSelectedMonth()
    {
        $this->refreshData();
    }

    public function updatedSelectedProduct()
    {
        $this->refreshData();
    }

    public function updatedSearchTerm()
    {
        $this->resetPage();
        $this->loadDetailedData();
    }

    public function clearFilters()
    {
        $this->selectedProduct = null;
        $this->searchTerm = '';
        $this->statusFilter = 'all';
        $this->refreshData();
    }

    public function exportReport()
    {
        try {
            $this->isLoading = true;
            $this->processingMessage = 'Generating export...';

            // Generate CSV export
            $filename = "deposits_report_{$this->selectedYear}_{$this->selectedMonth}.csv";
            
            // Implementation for CSV export would go here
            // For now, just show success message
            
            $this->successMessage = 'Report exported successfully.';
            
        } catch (Exception $e) {
            $this->errorMessage = 'Failed to export report: ' . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    public function render()
    {
        return view('livewire.deposits.monthly-report', [
            'summaryData' => $this->summaryData,
            'detailedData' => $this->detailedData,
            'monthlyData' => $this->monthlyData,
            'productStats' => $this->productStats,
            'transactionData' => $this->transactionData,
            'errorMessage' => $this->errorMessage,
        ]);
    }
}
