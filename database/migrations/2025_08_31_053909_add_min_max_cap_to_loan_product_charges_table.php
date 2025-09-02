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
        Schema::table('loan_product_charges', function (Blueprint $table) {
            // Add min_cap and max_cap columns for charge/insurance limits
            $table->decimal('min_cap', 20, 2)->nullable()->after('account_id')
                ->comment('Minimum cap amount for the charge/insurance');
            $table->decimal('max_cap', 20, 2)->nullable()->after('min_cap')
                ->comment('Maximum cap amount for the charge/insurance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_product_charges', function (Blueprint $table) {
            $table->dropColumn(['min_cap', 'max_cap']);
        });
    }
};