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
            // Add closure tracking fields
            $table->timestamp('closure_date')->nullable()->after('loan_status')
                ->comment('Date when the loan was closed/fully paid');
            
            $table->string('closure_reason', 100)->nullable()->after('closure_date')
                ->comment('Reason for closure (FULLY_PAID, WRITTEN_OFF, RESTRUCTURED, etc)');
            
            $table->string('closed_by', 100)->nullable()->after('closure_reason')
                ->comment('User who closed the loan');
            
            $table->decimal('final_payment_amount', 15, 2)->nullable()->after('closed_by')
                ->comment('Final payment amount that closed the loan');
            
            $table->string('final_receipt_number', 50)->nullable()->after('final_payment_amount')
                ->comment('Receipt number of final payment');
            
            // Add index for performance
            $table->index('closure_date');
            $table->index(['status', 'closure_date']);
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
            // Drop indexes first
            $table->dropIndex(['status', 'closure_date']);
            $table->dropIndex(['closure_date']);
            
            // Drop columns
            $table->dropColumn([
                'closure_date',
                'closure_reason',
                'closed_by',
                'final_payment_amount',
                'final_receipt_number'
            ]);
        });
    }
};