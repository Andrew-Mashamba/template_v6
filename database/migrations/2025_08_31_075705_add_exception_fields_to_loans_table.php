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
            // Add exception tracking fields
            $table->boolean('has_exceptions')->default(false)->after('rejection_reason')
                ->comment('Indicates if the loan has policy exceptions or violations');
            
            $table->string('exception_tracking_id', 100)->nullable()->after('has_exceptions')
                ->comment('Unique ID for tracking exception resolution');
            
            $table->json('exception_details')->nullable()->after('exception_tracking_id')
                ->comment('JSON details of all exceptions and violations');
            
            $table->timestamp('exception_resolved_at')->nullable()->after('exception_details')
                ->comment('Timestamp when exceptions were resolved');
            
            $table->bigInteger('exception_resolved_by')->nullable()->after('exception_resolved_at')
                ->comment('User ID who resolved the exceptions');
            
            // Add indexes for faster queries
            $table->index('has_exceptions');
            $table->index('exception_tracking_id');
            $table->index(['has_exceptions', 'status']);
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
            $table->dropIndex(['has_exceptions', 'status']);
            $table->dropIndex(['exception_tracking_id']);
            $table->dropIndex(['has_exceptions']);
            
            // Drop columns
            $table->dropColumn([
                'has_exceptions',
                'exception_tracking_id',
                'exception_details',
                'exception_resolved_at',
                'exception_resolved_by'
            ]);
        });
    }
};
