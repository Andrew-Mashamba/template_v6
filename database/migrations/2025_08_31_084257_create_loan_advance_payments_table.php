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
        Schema::create('loan_advance_payments', function (Blueprint $table) {
            $table->id();
            $table->string('loan_id', 50)->index()->comment('Loan ID reference');
            $table->string('client_number', 50)->nullable()->index()->comment('Client/Member number');
            $table->decimal('amount', 15, 2)->comment('Advance payment amount');
            $table->decimal('amount_used', 15, 2)->default(0)->comment('Amount already used from advance');
            $table->decimal('amount_available', 15, 2)->default(0)->comment('Amount still available');
            
            // Source of advance payment
            $table->string('source_payment_id', 50)->nullable()->comment('Source payment receipt number');
            $table->date('payment_date')->nullable()->comment('Date advance payment was made');
            
            // Usage tracking
            $table->enum('status', ['AVAILABLE', 'PARTIALLY_USED', 'FULLY_USED', 'REFUNDED', 'EXPIRED'])
                ->default('AVAILABLE')->comment('Status of advance payment');
            $table->json('usage_history')->nullable()->comment('History of how advance was used');
            $table->date('last_used_date')->nullable()->comment('Last date advance was applied');
            
            // Refund information
            $table->boolean('is_refunded')->default(false)->comment('Whether advance was refunded');
            $table->decimal('refund_amount', 15, 2)->nullable()->comment('Amount refunded');
            $table->date('refund_date')->nullable()->comment('Date of refund');
            $table->string('refund_reference', 100)->nullable()->comment('Refund reference number');
            $table->text('refund_reason')->nullable()->comment('Reason for refund');
            $table->string('refunded_by', 100)->nullable()->comment('User who processed refund');
            
            // System fields
            $table->bigInteger('branch_id')->nullable()->comment('Branch where advance was made');
            $table->string('created_by', 100)->nullable()->comment('User who recorded the advance');
            $table->text('notes')->nullable()->comment('Additional notes');
            $table->json('metadata')->nullable()->comment('Additional metadata');
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['loan_id', 'status']);
            $table->index(['client_number', 'status']);
            $table->index('payment_date');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('loan_advance_payments');
    }
};