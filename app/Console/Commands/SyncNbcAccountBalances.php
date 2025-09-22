<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BankAccount;
use App\Services\Payments\InternalFundsTransferService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class SyncNbcAccountBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nbc:sync-balances 
                            {--force : Force sync even if recently synced}
                            {--account= : Sync specific account number only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync NBC bank account balances with real-time NBC API data';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('===== NBC ACCOUNT BALANCE SYNC STARTED =====');
        $this->newLine();

        $startTime = microtime(true);
        $syncCount = 0;
        $errorCount = 0;
        $service = new InternalFundsTransferService();

        try {
            // Get NBC accounts from database
            $query = BankAccount::whereIn('status', ['active', 'ACTIVE'])
                ->where(function($q) {
                    $q->where('bank_name', 'LIKE', '%NBC%')
                      ->orWhere('bank_name', 'LIKE', '%National Bank%');
                });

            // If specific account requested
            if ($accountNumber = $this->option('account')) {
                $query->where('account_number', $accountNumber);
            }

            $nbcAccounts = $query->get();

            if ($nbcAccounts->isEmpty()) {
                $this->warn('No NBC accounts found to sync.');
                return Command::SUCCESS;
            }

            $this->info("Found {$nbcAccounts->count()} NBC account(s) to sync");
            $this->newLine();

            $progressBar = $this->output->createProgressBar($nbcAccounts->count());
            $progressBar->start();

            foreach ($nbcAccounts as $account) {
                try {
                    // Check if recently synced (unless forced)
                    if (!$this->option('force')) {
                        $lastSync = Cache::get("nbc_sync_{$account->id}_last");
                        if ($lastSync && Carbon::parse($lastSync)->diffInMinutes(now()) < 5) {
                            $this->line("\nSkipping {$account->account_number} - synced recently");
                            $progressBar->advance();
                            continue;
                        }
                    }

                    // Fetch real-time balance from NBC API
                    $lookup = $service->lookupAccount($account->account_number, 'source');
                    
                    if ($lookup['success']) {
                        $oldBalance = $account->current_balance;
                        $newBalance = $lookup['available_balance'] ?? $oldBalance;
                        
                        // Update account information
                        $updates = [
                            'current_balance' => $newBalance
                        ];
                        
                        // Add last_sync_at if column exists
                        if (Schema::hasColumn('bank_accounts', 'last_sync_at')) {
                            $updates['last_sync_at'] = now();
                        }

                        // Update account name if NBC provides better data
                        if (!empty($lookup['account_name']) && 
                            $lookup['account_name'] !== 'NBC Account' &&
                            $lookup['account_name'] !== $account->account_name) {
                            $updates['account_name'] = $lookup['account_name'];
                        }

                        // Update branch if provided
                        if (!empty($lookup['branch_name']) && $lookup['branch_name'] !== 'NBC Branch') {
                            $updates['branch_name'] = $lookup['branch_name'];
                        }

                        $account->update($updates);

                        // Cache the sync time
                        Cache::put("nbc_sync_{$account->id}_last", now(), 300); // 5 minutes

                        // Log significant balance changes
                        if (abs($newBalance - $oldBalance) > 0.01) {
                            Log::channel('payments')->info('NBC Balance Sync - Balance Changed', [
                                'account_id' => $account->id,
                                'account_number' => $account->account_number,
                                'old_balance' => $oldBalance,
                                'new_balance' => $newBalance,
                                'difference' => $newBalance - $oldBalance,
                                'sync_time' => now()->toIso8601String()
                            ]);

                            $this->line('');
                            $this->info("✓ {$account->account_number}: Balance updated from " . 
                                       number_format($oldBalance, 2) . " to " . 
                                       number_format($newBalance, 2));
                        }

                        $syncCount++;
                    } else {
                        $errorCount++;
                        $this->line('');
                        $this->error("✗ Failed to sync {$account->account_number}: " . 
                                    ($lookup['error'] ?? 'Unknown error'));
                        
                        Log::channel('payments')->error('NBC Balance Sync Failed', [
                            'account_id' => $account->id,
                            'account_number' => $account->account_number,
                            'error' => $lookup['error'] ?? 'Unknown error'
                        ]);
                    }
                } catch (\Exception $e) {
                    $errorCount++;
                    $this->line('');
                    $this->error("✗ Exception for {$account->account_number}: " . $e->getMessage());
                    
                    Log::channel('payments')->error('NBC Balance Sync Exception', [
                        'account_id' => $account->id,
                        'account_number' => $account->account_number,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine(2);

            // Summary
            $duration = round(microtime(true) - $startTime, 2);
            $this->info('===== SYNC COMPLETED =====');
            $this->info("Accounts synced: $syncCount");
            if ($errorCount > 0) {
                $this->warn("Errors encountered: $errorCount");
            }
            $this->info("Duration: {$duration} seconds");
            
            // Store sync statistics
            Cache::put('nbc_last_sync_stats', [
                'time' => now()->toIso8601String(),
                'synced' => $syncCount,
                'errors' => $errorCount,
                'duration' => $duration
            ], 3600);

            return $errorCount > 0 ? Command::FAILURE : Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Fatal error: ' . $e->getMessage());
            Log::channel('payments')->error('NBC Sync Fatal Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }
}