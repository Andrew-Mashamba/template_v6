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
            // Add source column to track where the loan application originated from
            $table->string('source', 50)->default('OFFICE')->after('arrears_in_amount')
                ->comment('Source of loan application: OFFICE, MOBILE, WEB, API');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};