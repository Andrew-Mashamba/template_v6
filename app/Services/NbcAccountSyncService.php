<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Services\Payments\InternalFundsTransferService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class NbcAccountSyncService
{
    protected InternalFundsTransferService $nbcService;
    
    public function __construct()
    {
        $this->nbcService = new InternalFundsTransferService();
    }

    /**
     * Sync a single NBC account balance
     *
     * @param string $accountNumber
     * @return array
     */
    public function syncAccount(string $accountNumber): array
    {
        try {
            // Find the account in database
            $account = BankAccount::where('account_number', $accountNumber)
                ->where(function($q) {
                    $q->where('bank_name', 'LIKE', '%NBC%')
                      ->orWhere('bank_name', 'LIKE', '%National Bank%');
                })
                ->first();

            if (!$account) {
                return [
                    'success' => false,
                    'error' => 'Account not found in database'
                ];
            }

            // Fetch real-time data from NBC
            $lookup = $this->nbcService->lookupAccount($accountNumber, 'source');
            
            if (!$lookup['success']) {
                return [
                    'success' => false,
                    'error' => $lookup['error'] ?? 'Failed to fetch from NBC API'
                ];
            }

            $oldBalance = $account->current_balance;
            $newBalance = $lookup['available_balance'] ?? $oldBalance;
            
            // Update the account
            $account->update([
                'current_balance' => $newBalance,
                'last_sync_at' => now(),
                'account_name' => $lookup['account_name'] ?? $account->account_name,
                'branch_name' => $lookup['branch_name'] ?? $account->branch_name
            ]);

            // Log the sync
            Log::channel('payments')->info('NBC Account Synced', [
                'account_number' => $accountNumber,
                'old_balance' => $oldBalance,
                'new_balance' => $newBalance,
                'change' => $newBalance - $oldBalance
            ]);

            return [
                'success' => true,
                'account_number' => $accountNumber,
                'old_balance' => $oldBalance,
                'new_balance' => $newBalance,
                'change' => $newBalance - $oldBalance,
                'synced_at' => now()->toIso8601String()
            ];

        } catch (\Exception $e) {
            Log::error('NBC Sync Error', [
                'account_number' => $accountNumber,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Sync all NBC accounts
     *
     * @param bool $force Force sync even if recently synced
     * @return array
     */
    public function syncAllAccounts(bool $force = false): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'accounts' => []
        ];

        $nbcAccounts = BankAccount::where('status', 'active')
            ->where(function($q) {
                $q->where('bank_name', 'LIKE', '%NBC%')
                  ->orWhere('bank_name', 'LIKE', '%National Bank%');
            })
            ->get();

        foreach ($nbcAccounts as $account) {
            // Check rate limiting
            if (!$force && $this->isRecentlySynced($account->id)) {
                $results['skipped']++;
                $results['accounts'][] = [
                    'account_number' => $account->account_number,
                    'status' => 'skipped',
                    'reason' => 'Recently synced'
                ];
                continue;
            }

            $syncResult = $this->syncAccount($account->account_number);
            
            if ($syncResult['success']) {
                $results['success']++;
            } else {
                $results['failed']++;
            }
            
            $results['accounts'][] = array_merge(
                ['account_number' => $account->account_number],
                $syncResult
            );

            // Mark as synced
            $this->markAsSynced($account->id);
            
            // Rate limiting - wait 100ms between API calls
            usleep(100000);
        }

        return $results;
    }

    /**
     * Check if account was recently synced
     *
     * @param int $accountId
     * @param int $minutes
     * @return bool
     */
    protected function isRecentlySynced(int $accountId, int $minutes = 5): bool
    {
        $lastSync = Cache::get("nbc_sync_{$accountId}_last");
        return $lastSync && Carbon::parse($lastSync)->diffInMinutes(now()) < $minutes;
    }

    /**
     * Mark account as synced
     *
     * @param int $accountId
     * @return void
     */
    protected function markAsSynced(int $accountId): void
    {
        Cache::put("nbc_sync_{$accountId}_last", now(), 300); // 5 minutes
    }

    /**
     * Sync after transaction (called by transaction services)
     *
     * @param array $accountNumbers Array of account numbers involved in transaction
     * @param int $delaySeconds Delay before syncing (to allow transaction to settle)
     * @return void
     */
    public function syncAfterTransaction(array $accountNumbers, int $delaySeconds = 2): void
    {
        // Queue the sync job with delay
        dispatch(function () use ($accountNumbers) {
            foreach ($accountNumbers as $accountNumber) {
                // Check if it's an NBC account
                $isNbc = BankAccount::where('account_number', $accountNumber)
                    ->where(function($q) {
                        $q->where('bank_name', 'LIKE', '%NBC%')
                          ->orWhere('bank_name', 'LIKE', '%National Bank%');
                    })
                    ->exists();

                if ($isNbc) {
                    $this->syncAccount($accountNumber);
                }
            }
        })->delay(now()->addSeconds($delaySeconds));
    }
}