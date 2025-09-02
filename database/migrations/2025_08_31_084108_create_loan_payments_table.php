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
        Schema::create('loan_payments', function (Blueprint $table) {
            $table->id();
            $table->string('loan_id', 50)->index()->comment('Loan ID reference');
            $table->string('client_number', 50)->index()->comment('Client/Member number');
            $table->string('receipt_number', 50)->unique()->comment('Unique payment receipt number');
            $table->date('payment_date')->index()->comment('Date of payment');
            $table->decimal('amount', 15, 2)->comment('Total payment amount');
            
            // Payment breakdown
            $table->decimal('principal_paid', 15, 2)->default(0)->comment('Amount applied to principal');
            $table->decimal('interest_paid', 15, 2)->default(0)->comment('Amount applied to interest');
            $table->decimal('penalty_paid', 15, 2)->default(0)->comment('Amount applied to penalties');
            $table->decimal('fees_paid', 15, 2)->default(0)->comment('Amount applied to fees');
            $table->decimal('overpayment', 15, 2)->default(0)->comment('Overpayment amount');
            
            // Payment method details
            $table->enum('payment_method', ['CASH', 'BANK', 'MOBILE', 'INTERNAL', 'SALARY', 'OTHER'])
                ->default('CASH')->comment('Payment method used');
            $table->string('reference_number', 100)->nullable()->comment('Payment reference number');
            $table->string('bank_name', 100)->nullable()->comment('Bank name for bank transfers');
            $table->string('mobile_provider', 50)->nullable()->comment('Mobile money provider');
            $table->string('mobile_number', 20)->nullable()->comment('Mobile number used');
            $table->string('source_account', 50)->nullable()->comment('Source account for internal transfers');
            
            // Status and tracking
            $table->enum('status', ['PENDING', 'COMPLETED', 'REVERSED', 'FAILED'])
                ->default('COMPLETED')->comment('Payment status');
            $table->text('narration')->nullable()->comment('Payment description/notes');
            $table->string('processed_by', 100)->nullable()->comment('User who processed the payment');
            $table->string('reversed_by', 100)->nullable()->comment('User who reversed the payment');
            $table->timestamp('reversed_at')->nullable()->comment('Reversal timestamp');
            $table->text('reversal_reason')->nullable()->comment('Reason for reversal');
            
            // Balances after payment
            $table->decimal('outstanding_principal', 15, 2)->nullable()->comment('Principal balance after payment');
            $table->decimal('outstanding_interest', 15, 2)->nullable()->comment('Interest balance after payment');
            $table->decimal('outstanding_penalty', 15, 2)->nullable()->comment('Penalty balance after payment');
            $table->decimal('outstanding_total', 15, 2)->nullable()->comment('Total balance after payment');
            
            // System fields
            $table->bigInteger('branch_id')->nullable()->comment('Branch where payment was made');
            $table->string('payment_channel', 50)->nullable()->comment('Payment channel (TELLER, ATM, ONLINE, etc)');
            $table->string('session_id', 100)->nullable()->comment('Session ID for tracking');
            $table->json('metadata')->nullable()->comment('Additional payment metadata');
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['loan_id', 'payment_date']);
            $table->index(['client_number', 'payment_date']);
            $table->index(['status', 'payment_date']);
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
        Schema::dropIfExists('loan_payments');
    }
};