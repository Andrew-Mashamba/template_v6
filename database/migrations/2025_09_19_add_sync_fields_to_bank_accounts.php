<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('bank_accounts', 'last_sync_at')) {
                $table->timestamp('last_sync_at')->nullable()->after('updated_at');
            }
            if (!Schema::hasColumn('bank_accounts', 'sync_enabled')) {
                $table->boolean('sync_enabled')->default(true)->after('status');
            }
            if (!Schema::hasColumn('bank_accounts', 'sync_frequency')) {
                $table->integer('sync_frequency')->default(15)->after('sync_enabled')
                    ->comment('Sync frequency in minutes');
            }
            
            // Add index for faster queries
            $table->index(['bank_name', 'status', 'sync_enabled']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropIndex(['bank_name', 'status', 'sync_enabled']);
            $table->dropColumn(['last_sync_at', 'sync_enabled', 'sync_frequency']);
        });
    }
};