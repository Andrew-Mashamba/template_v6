<?php

namespace App\Http\Livewire\MembersPortal;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Models\ClientsModel;
use App\Models\User;
use App\Models\WebPortalUser;
use App\Notifications\MemberPortalCredentialsNotification;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class PortalUserManagement extends Component
{
    use WithPagination, WithFileUploads;

    // Search and filter properties
    public $search = '';
    public $filterStatus = 'all';
    public $perPage = 10;

    // Modal properties
    public $showEnablePortalModal = false;
    public $showCredentialsModal = false;
    public $showBulkEnableModal = false;

    // Selected member for portal access
    public $selectedMember = null;
    public $selectedMembers = [];

    // Portal access data
    public $portalData = [
        'auto_generate_password' => true,
        'custom_password' => '',
        'send_credentials_email' => true,
        'send_credentials_sms' => false,
        'portal_permissions' => [
            'view_accounts' => true,
            'view_transactions' => true,
            'view_loans' => true,
            'view_shares' => true,
            'download_statements' => true,
            'update_profile' => false,
        ]
    ];

    // Generated credentials
    public $generatedCredentials = null;

    // Statistics
    public $stats = [
        'total_members' => 0,
        'portal_enabled' => 0,
        'active_users' => 0,
        'pending_activations' => 0
    ];

    protected $listeners = [
        'refreshPortalUsers' => '$refresh',
        'memberSelected' => 'enablePortalAccess'
    ];

    public function mount()
    {
        $this->loadStatistics();
    }

    public function render()
    {
        $members = $this->getMembers();
        return view('livewire.members-portal.portal-user-management', [
            'members' => $members
        ]);
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterStatus()
    {
        $this->resetPage();
    }

    private function getMembers()
    {
        $query = ClientsModel::query()
            ->leftJoin('web_portal_users', 'clients.id', '=', 'web_portal_users.client_id')
            ->select('clients.*', 'web_portal_users.is_active as portal_active', 
                    'web_portal_users.last_login_at as portal_last_login',
                    'web_portal_users.is_locked as portal_locked',
                    'web_portal_users.id as portal_user_id')
            ->where(function ($q) {
                if ($this->search) {
                    $q->where('clients.client_number', 'like', '%' . $this->search . '%')
                      ->orWhere('clients.first_name', 'like', '%' . $this->search . '%')
                      ->orWhere('clients.last_name', 'like', '%' . $this->search . '%')
                      ->orWhere('clients.email', 'like', '%' . $this->search . '%')
                      ->orWhere('clients.mobile_phone_number', 'like', '%' . $this->search . '%');
                }
            });

        // Apply status filter
        switch ($this->filterStatus) {
            case 'portal_enabled':
                $query->whereNotNull('web_portal_users.id')
                      ->where('web_portal_users.is_active', true);
                break;
            case 'portal_disabled':
                $query->whereNull('web_portal_users.id');
                break;
            case 'recently_registered':
                $query->where('web_portal_users.portal_registered_at', '>=', now()->subDays(7));
                break;
            case 'never_logged_in':
                $query->whereNotNull('web_portal_users.id')
                      ->where('web_portal_users.is_active', true)
                      ->whereNull('web_portal_users.last_login_at');
                break;
        }

        return $query->orderBy('created_at', 'desc')
                    ->paginate($this->perPage);
    }

    public function loadStatistics()
    {
        $this->stats = [
            'total_members' => ClientsModel::count(),
            'portal_enabled' => WebPortalUser::active()->count(),
            'active_users' => WebPortalUser::active()
                                        ->whereNotNull('last_login_at')
                                        ->count(),
            'pending_activations' => WebPortalUser::active()
                                                ->whereNull('last_login_at')
                                                ->count()
        ];
    }

    public function enablePortalAccess($memberId)
    {
        $this->selectedMember = ClientsModel::find($memberId);
        
        if (!$this->selectedMember) {
            $this->dispatchBrowserEvent('error', [
                'message' => 'Member not found.'
            ]);
            return;
        }

        // Check if portal user already exists
        $existingPortalUser = WebPortalUser::where('client_id', $this->selectedMember->id)->first();
        if ($existingPortalUser) {
            $this->dispatchBrowserEvent('error', [
                'message' => 'Portal access is already enabled for this member.'
            ]);
            return;
        }

        $this->showEnablePortalModal = true;
    }

    public function processPortalAccess()
    {
        $this->validate([
            'portalData.custom_password' => $this->portalData['auto_generate_password'] ? '' : 'required|min:8',
        ], [
            'portalData.custom_password.required' => 'Please enter a password or enable auto-generate.',
            'portalData.custom_password.min' => 'Password must be at least 8 characters.',
        ]);

        if (!$this->selectedMember) {
            return;
        }

        // Generate or use custom password
        $password = $this->portalData['auto_generate_password'] 
            ? $this->generateSecurePassword() 
            : $this->portalData['custom_password'];

        // Create WebPortalUser record
        $portalUser = WebPortalUser::create([
            'client_id' => $this->selectedMember->id,
            'client_number' => $this->selectedMember->client_number,
            'username' => $this->selectedMember->client_number, // Default to member number
            'email' => $this->selectedMember->email,
            'phone' => $this->selectedMember->mobile_phone_number,
            'password_hash' => $password, // Will be hashed automatically by mutator
            'is_active' => true,
            'permissions' => $this->portalData['portal_permissions'],
            'portal_registered_at' => now(),
            'registered_by' => auth()->id(),
            'created_by' => auth()->id(),
        ]);

        // Store credentials for display
        $this->generatedCredentials = [
            'member_number' => $this->selectedMember->client_number,
            'email' => $this->selectedMember->email,
            'phone' => $this->selectedMember->mobile_phone_number,
            'password' => $password,
            'portal_url' => route('members.portal')
        ];

        // Send credentials
        if ($this->portalData['send_credentials_email']) {
            $this->sendCredentialsEmail();
        }

        if ($this->portalData['send_credentials_sms']) {
            $this->sendCredentialsSMS();
        }

        $this->showEnablePortalModal = false;
        $this->showCredentialsModal = true;
        $this->loadStatistics();

        $this->dispatchBrowserEvent('portal-access-enabled', [
            'message' => 'Portal access enabled successfully for ' . $this->selectedMember->getFullNameAttribute()
        ]);
    }





    public function bulkEnablePortalAccess()
    {
        if (empty($this->selectedMembers)) {
            $this->dispatchBrowserEvent('error', [
                'message' => 'Please select at least one member.'
            ]);
            return;
        }

        $this->showBulkEnableModal = true;
    }

    public function processBulkPortalAccess()
    {
        if (empty($this->selectedMembers)) {
            return;
        }

        $processed = 0;
        $errors = 0;

        foreach ($this->selectedMembers as $memberId) {
            $member = ClientsModel::find($memberId);
            
            if ($member && !$member->portal_access_enabled) {
                $password = $this->generateSecurePassword();
                
                $member->update([
                    'portal_access_enabled' => true,
                    'password_hash' => Hash::make($password),
                    'portal_registered_at' => now(),
                    'portal_permissions' => json_encode($this->portalData['portal_permissions'])
                ]);

                // Send credentials
                if ($this->portalData['send_credentials_email']) {
                    $this->selectedMember = $member;
                    $this->generatedCredentials = [
                        'member_number' => $member->client_number,
                        'email' => $member->email,
                        'phone' => $member->mobile_phone_number,
                        'password' => $password,
                        'portal_url' => route('members.portal')
                    ];
                    $this->sendCredentialsEmail();
                }

                $processed++;
            } else {
                $errors++;
            }
        }

        $this->selectedMembers = [];
        $this->showBulkEnableModal = false;
        $this->loadStatistics();

        $this->dispatchBrowserEvent('bulk-process-complete', [
            'message' => "Processed: {$processed}, Errors: {$errors}"
        ]);
    }

    private function generateSecurePassword()
    {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*';

        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $symbols[random_int(0, strlen($symbols) - 1)];

        $allChars = $uppercase . $lowercase . $numbers . $symbols;
        for ($i = 4; $i < 12; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }

        return str_shuffle($password);
    }

    private function sendCredentialsEmail()
    {
        if (!$this->selectedMember || !$this->generatedCredentials) {
            return;
        }

        try {
            Mail::send('emails.member-portal-credentials', [
                'member' => $this->selectedMember,
                'credentials' => $this->generatedCredentials
            ], function ($message) {
                $message->to($this->selectedMember->email, $this->selectedMember->getFullNameAttribute())
                        ->subject('Your SACCO Members Portal Access Credentials');
            });
        } catch (\Exception $e) {
            Log::error('Failed to send portal credentials email: ' . $e->getMessage());
        }
    }

    private function sendCredentialsSMS()
    {
        // SMS implementation would go here
        // This is a placeholder for SMS service integration
        Log::info('SMS credentials sent to: ' . $this->selectedMember->mobile_phone_number);
    }

    public function closeModals()
    {
        $this->showEnablePortalModal = false;
        $this->showCredentialsModal = false;
        $this->showBulkEnableModal = false;
        $this->selectedMember = null;
        $this->generatedCredentials = null;
        $this->portalData['custom_password'] = '';
    }

    public function toggleMemberSelection($memberId)
    {
        if (in_array($memberId, $this->selectedMembers)) {
            $this->selectedMembers = array_diff($this->selectedMembers, [$memberId]);
        } else {
            $this->selectedMembers[] = $memberId;
        }
    }

    public function selectAllMembers()
    {
        $members = $this->getMembers();
        $this->selectedMembers = $members->pluck('id')->toArray();
    }

    public function deselectAllMembers()
    {
        $this->selectedMembers = [];
    }

    public function unlockPortalAccess($memberId)
    {
        $member = ClientsModel::findOrFail($memberId);
        $portalUser = WebPortalUser::where('client_id', $memberId)->first();

        if (!$portalUser) {
            $this->dispatchBrowserEvent('error', [
                'message' => 'Portal user not found.'
            ]);
            return;
        }

        $portalUser->unlockAccount();

        $this->dispatchBrowserEvent('success', [
            'message' => 'Portal access unlocked for ' . $member->getFullNameAttribute()
        ]);

        $this->loadStatistics();
    }

    public function resetMemberPassword($memberId)
    {
        $member = ClientsModel::findOrFail($memberId);
        $portalUser = WebPortalUser::where('client_id', $memberId)->first();

        if (!$portalUser) {
            $this->dispatchBrowserEvent('error', [
                'message' => 'Portal user not found.'
            ]);
            return;
        }

        // Generate new password
        $newPassword = $this->generateSecurePassword();
        
        // Update password and force change on next login
        $portalUser->update([
            'password_hash' => $newPassword, // Will be hashed by mutator
            'force_password_change' => true,
            'updated_by' => auth()->id(),
        ]);

        // Unlock account if it was locked
        if ($portalUser->isAccountLocked()) {
            $portalUser->unlockAccount();
        }

        // Store credentials for display
        $this->generatedCredentials = [
            'member_number' => $member->client_number,
            'email' => $member->email,
            'phone' => $member->mobile_phone_number,
            'password' => $newPassword,
            'portal_url' => route('members.portal')
        ];

        // Send new credentials via email
        try {
            $member->notify(new MemberPortalCredentialsNotification([
                'member_name' => $member->getFullNameAttribute(),
                'member_number' => $member->client_number,
                'username' => $portalUser->username,
                'password' => $newPassword,
                'portal_url' => route('members.portal'),
                'is_password_reset' => true
            ]));
        } catch (\Exception $e) {
            Log::error('Failed to send password reset email', [
                'member_id' => $memberId,
                'error' => $e->getMessage()
            ]);
        }

        $this->showCredentialsModal = true;

        $this->dispatchBrowserEvent('success', [
            'message' => 'Password reset successfully for ' . $member->getFullNameAttribute()
        ]);
    }

    public function disablePortalAccess($memberId)
    {
        $member = ClientsModel::findOrFail($memberId);
        $portalUser = WebPortalUser::where('client_id', $memberId)->first();

        if (!$portalUser) {
            $this->dispatchBrowserEvent('error', [
                'message' => 'Portal user not found.'
            ]);
            return;
        }

        // Deactivate the portal user
        $portalUser->update([
            'is_active' => false,
            'updated_by' => auth()->id(),
        ]);

        // End all active sessions
        $portalUser->endAllSessions();

        $this->dispatchBrowserEvent('success', [
            'message' => 'Portal access disabled for ' . $member->getFullNameAttribute()
        ]);

        $this->loadStatistics();
    }
}
