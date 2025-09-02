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
            // Restructure loan fields
            $table->string('restructure_loan_id')->nullable()->after('top_up_processed_by');
            $table->decimal('restructure_amount', 15, 2)->nullable()->after('restructure_loan_id');
            $table->string('restructure_loan_account')->nullable()->after('restructure_amount');
            $table->timestamp('restructure_processed_at')->nullable()->after('restructure_loan_account');
            $table->integer('restructure_processed_by')->nullable()->after('restructure_processed_at');
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
                'restructure_loan_id',
                'restructure_amount',
                'restructure_loan_account',
                'restructure_processed_at',
                'restructure_processed_by'
            ]);
        });
    }
};