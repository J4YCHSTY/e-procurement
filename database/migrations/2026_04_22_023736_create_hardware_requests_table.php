<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hardware_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('master_employees')->onDelete('cascade');
            $table->date('request_date');
            $table->string('hardware_type');
            $table->string('hardware_recommendation')->nullable();
            $table->text('justification');
            $table->boolean('digital_signature')->default(false);
            $table->string('status')->default('WAITING_FOR_HEAD_APPROVAL');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hardware_requests');
    }
};
