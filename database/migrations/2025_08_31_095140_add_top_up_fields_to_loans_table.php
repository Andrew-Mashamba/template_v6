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
            // Top-up loan fields
            $table->string('top_up_loan_id')->nullable()->after('loan_type_2');
            $table->decimal('top_up_amount', 15, 2)->nullable()->after('top_up_loan_id');
            $table->decimal('top_up_penalty_amount', 15, 2)->nullable()->after('top_up_amount');
            $table->string('top_up_loan_account')->nullable()->after('top_up_penalty_amount');
            $table->timestamp('top_up_processed_at')->nullable()->after('top_up_loan_account');
            $table->integer('top_up_processed_by')->nullable()->after('top_up_processed_at');
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
            $table->dropColumn([
                'top_up_loan_id',
                'top_up_amount',
                'top_up_penalty_amount',
                'top_up_loan_account',
                'top_up_processed_at',
                'top_up_processed_by'
            ]);
        });
    }
};
