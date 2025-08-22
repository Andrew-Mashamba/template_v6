<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Starting to recreate issued_shares table...\n";

try {
    // Drop the table if it exists
    Schema::dropIfExists('issued_shares');
    echo "Dropped existing issued_shares table\n";
    
    // Create the table
    Schema::create('issued_shares', function (Blueprint $table) {
        $table->id();         
        $table->string('reference_number')->unique();
        $table->unsignedBigInteger('share_id')->nullable();
        $table->string('member')->nullable();
        $table->string('product')->nullable();
        $table->string('account_number')->nullable();
        $table->decimal('price', 15, 2)->nullable();
        $table->string('branch')->nullable();
        $table->string('client_number')->nullable();
        $table->integer('number_of_shares')->nullable();
        $table->decimal('nominal_price', 15, 2)->nullable();
        $table->decimal('total_value', 15, 2)->nullable();
        $table->string('linked_savings_account')->nullable();
        $table->string('linked_share_account')->nullable();
        $table->string('status')->default('PENDING')->nullable();  
        $table->unsignedBigInteger('created_by')->nullable();
        $table->timestamps();
        $table->softDeletes();

        // Add indexes for better performance
        $table->index(['member', 'status']);
        $table->index('reference_number');
        $table->index('created_at');
    });
    
    echo "Successfully recreated issued_shares table with all columns and indexes!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 