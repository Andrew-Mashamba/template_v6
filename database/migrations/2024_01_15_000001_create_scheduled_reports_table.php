<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('scheduled_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_type');
            $table->json('report_config');
            $table->unsignedBigInteger('user_id');
            $table->enum('status', ['scheduled', 'processing', 'completed', 'failed'])->default('scheduled');
            $table->enum('frequency', ['once', 'daily', 'weekly', 'monthly', 'quarterly', 'annually'])->default('once');
            $table->timestamp('scheduled_at');
            $table->timestamp('generated_at')->nullable();
            $table->string('file_path')->nullable();
            $table->json('email_recipients')->nullable();
            $table->string('email_subject')->nullable();
            $table->text('email_message')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['status', 'scheduled_at']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('scheduled_reports');
    }
};
