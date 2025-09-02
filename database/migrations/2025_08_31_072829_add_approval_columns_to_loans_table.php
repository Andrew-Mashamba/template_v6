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
        Schema::table('loans', function (Blueprint $table) {
            // Add approval tracking columns
            $table->timestamp('approved_at')->nullable()->after('approval_stage_role_name')
                ->comment('Timestamp when the loan was approved');
            $table->bigInteger('approved_by')->nullable()->after('approved_at')
                ->comment('User ID who approved the loan');
            
            // Add index for faster queries on approval status
            $table->index(['approved_at', 'approved_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropIndex(['approved_at', 'approved_by']);
            $table->dropColumn(['approved_at', 'approved_by']);
        });
    }
};