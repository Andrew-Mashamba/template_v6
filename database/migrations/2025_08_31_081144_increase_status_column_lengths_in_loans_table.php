<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('loans', function (Blueprint $table) {
            // Increase size of status columns to accommodate longer status values
            $table->string('status', 50)->change()
                ->comment('Loan status (can include exceptions)');
            
            $table->string('loan_status', 50)->change()
                ->comment('Current loan status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('loans', function (Blueprint $table) {
            // Revert back to original sizes
            $table->string('status', 20)->change();
            $table->string('loan_status', 20)->change();
        });
    }
};