<?php

namespace App\Http\Livewire\Subscriptions;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Traits\Livewire\WithModulePermissions;

class Subscriptions extends Component
{
    use WithModulePermissions;
    public $activeTab = 'overview';
    public $loading = false;
    public $services = [];
    public $billingHistory = [];
    public $currentPlan = [];
    public $showUpgradeModal = false;
    public $selectedService = null;
    public $totalMonthlyBill = 0;
    public $usageStats = [];

    protected $listeners = ['refreshComponent' => '$refresh'];

    public function mount()
    {
        // Initialize the permission system for this module
        $this->initializeWithModulePermissions();
        $this->loadServices();
        $this->loadBillingHistory();
        $this->calculateTotalBill();
        $this->loadUsageStats();
    }

    public function loadServices()
    {
        $this->services = [
            [
                'id' => 1,
                'name' => 'SMS Service',
                'description' => 'Send SMS notifications to members for transactions, alerts, and reminders',
                'type' => 'mandatory',
                'status' => 'active',
                'price' => 50000,
                'billing_cycle' => 'monthly',
                'features' => ['Transaction alerts', 'Payment reminders', 'Marketing messages', 'OTP verification'],
                'usage' => ['sent' => 12500, 'limit' => 20000, 'percentage' => 62.5]
            ],
            [
                'id' => 2,
                'name' => 'Email Service',
                'description' => 'Professional email communications for statements, notifications, and marketing',
                'type' => 'mandatory',
                'status' => 'active',
                'price' => 30000,
                'billing_cycle' => 'monthly',
                'features' => ['Transaction emails', 'Monthly statements', 'Marketing campaigns', 'Email templates'],
                'usage' => ['sent' => 8500, 'limit' => 50000, 'percentage' => 17]
            ],
            [
                'id' => 3,
                'name' => 'Control Number Payment',
                'description' => 'Generate control numbers for member payments through banks and mobile networks',
                'type' => 'mandatory',
                'status' => 'active',
                'price' => 100000,
                'billing_cycle' => 'monthly',
                'features' => ['Automated control numbers', 'Multi-bank integration', 'Real-time reconciliation', 'Payment tracking'],
                'usage' => ['generated' => 450, 'limit' => 1000, 'percentage' => 45]
            ],
            [
                'id' => 4,
                'name' => 'Pay by Link',
                'description' => 'Send payment links to members for easy online payments',
                'type' => 'mandatory',
                'status' => 'active',
                'price' => 75000,
                'billing_cycle' => 'monthly',
                'features' => ['Secure payment links', 'Multiple payment methods', 'Automatic receipts', 'Link expiration control'],
                'usage' => ['created' => 320, 'limit' => 500, 'percentage' => 64]
            ],
            [
                'id' => 5,
                'name' => 'Mobile Application',
                'description' => 'Branded mobile app for Android and iOS for member self-service',
                'type' => 'optional',
                'status' => 'active',
                'price' => 200000,
                'billing_cycle' => 'monthly',
                'features' => ['Account access', 'Loan applications', 'Transfer funds', 'Push notifications', 'Biometric login'],
                'usage' => ['downloads' => 850, 'active_users' => 620]
            ],
            [
                'id' => 6,
                'name' => 'Members Portal',
                'description' => 'Web-based self-service portal for members to access accounts online',
                'type' => 'optional',
                'status' => 'inactive',
                'price' => 150000,
                'billing_cycle' => 'monthly',
                'features' => ['Online account access', 'Loan applications', 'Document downloads', 'Support tickets'],
                'usage' => null
            ],
            [
                'id' => 7,
                'name' => 'Zona AI Assistant',
                'description' => 'AI-powered chatbot for 24/7 customer support and assistance',
                'type' => 'optional',
                'status' => 'inactive',
                'price' => 250000,
                'billing_cycle' => 'monthly',
                'features' => ['24/7 availability', 'Multi-language support', 'Loan eligibility check', 'FAQ responses', 'Escalation to human agents'],
                'usage' => null
            ],
            [
                'id' => 8,
                'name' => 'CRB Integration',
                'description' => 'Credit Reference Bureau integration for credit scoring and reporting',
                'type' => 'optional',
                'status' => 'inactive',
                'price' => 300000,
                'billing_cycle' => 'monthly',
                'features' => ['Credit score checking', 'Automated reporting', 'Member credit history', 'Risk assessment'],
                'usage' => null
            ]
        ];
    }

    public function loadBillingHistory()
    {
        $this->billingHistory = [
            ['date' => '2025-07-01', 'amount' => 455000, 'status' => 'paid', 'invoice' => 'INV-2025-07-001'],
            ['date' => '2025-06-01', 'amount' => 455000, 'status' => 'paid', 'invoice' => 'INV-2025-06-001'],
            ['date' => '2025-05-01', 'amount' => 455000, 'status' => 'paid', 'invoice' => 'INV-2025-05-001'],
            ['date' => '2025-04-01', 'amount' => 255000, 'status' => 'paid', 'invoice' => 'INV-2025-04-001'],
        ];
    }

    public function loadUsageStats()
    {
        $this->usageStats = [
            'sms_sent_today' => 450,
            'emails_sent_today' => 320,
            'payment_links_today' => 28,
            'control_numbers_today' => 15,
            'app_logins_today' => 185,
            'ai_queries_today' => 0,
            'crb_checks_today' => 0
        ];
    }

    public function calculateTotalBill()
    {
        $this->totalMonthlyBill = collect($this->services)
            ->where('status', 'active')
            ->sum('price');
    }

    public function toggleService($serviceId)
    {
        // Check permission to manage subscriptions
        if (!($this->permissions['canManage'] ?? false)) {
            session()->flash('error', 'You do not have permission to manage service subscriptions');
            return;
        }
        
        $service = collect($this->services)->firstWhere('id', $serviceId);
        
        if ($service && $service['type'] === 'optional') {
            $this->selectedService = $service;
            $this->showUpgradeModal = true;
        }
    }

    public function confirmServiceToggle()
    {
        // Check permission to manage subscriptions
        if (!($this->permissions['canManage'] ?? false)) {
            session()->flash('error', 'You do not have permission to manage service subscriptions');
            return;
        }
        
        if ($this->selectedService) {
            // Update service status
            $this->services = collect($this->services)->map(function ($service) {
                if ($service['id'] === $this->selectedService['id']) {
                    $service['status'] = $service['status'] === 'active' ? 'inactive' : 'active';
                }
                return $service;
            })->toArray();
            
            $this->calculateTotalBill();
            $this->showUpgradeModal = false;
            $this->selectedService = null;
            
            session()->flash('message', 'Service updated successfully!');
        }
    }

    public function cancelServiceToggle()
    {
        $this->showUpgradeModal = false;
        $this->selectedService = null;
    }

    public function downloadInvoice($invoiceNumber)
    {
        // Check permission to download invoices
        if (!($this->permissions['canExport'] ?? false)) {
            session()->flash('error', 'You do not have permission to download invoices');
            return;
        }
        
        // Logic to download invoice
        session()->flash('message', 'Invoice downloaded successfully!');
    }

    public function setActiveTab($tab)
    {
        // Check permissions based on the tab being accessed
        $requiredPermission = $this->getRequiredPermissionForTab($tab);
        $permissionKey = 'can' . ucfirst($requiredPermission);
        
        if (!($this->permissions[$permissionKey] ?? false)) {
            session()->flash('error', 'You do not have permission to access this subscriptions section');
            return;
        }
        
        $this->activeTab = $tab;
    }

    public function render()
    {
        return view('livewire.subscriptions.subscriptions', array_merge(
            $this->permissions,
            [
                'permissions' => $this->permissions
            ]
        ));
    }

    /**
     * Get the required permission for a specific subscriptions tab
     */
    private function getRequiredPermissionForTab($tab)
    {
        $tabPermissionMap = [
            'overview' => 'view',    // Services Overview
            'usage' => 'view',       // Usage Statistics
            'billing' => 'view',     // Billing History
        ];
        
        return $tabPermissionMap[$tab] ?? 'view';
    }

    /**
     * Override to specify the module name for permissions
     * 
     * @return string
     */
    protected function getModuleName(): string
    {
        return 'subscriptions';
    }
}
