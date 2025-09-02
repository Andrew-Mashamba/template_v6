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
        Schema::table('share_transfers', function (Blueprint $table) {
            $table->boolean('is_cross_product_transfer')->default(false)->after('transfer_reason');
            $table->decimal('equivalent_shares', 15, 2)->nullable()->after('is_cross_product_transfer');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('share_transfers', function (Blueprint $table) {
            $table->dropColumn(['is_cross_product_transfer', 'equivalent_shares']);
        });
    }
};
