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
        Schema::create('software_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('master_employees')->onDelete('cascade');
            $table->date('request_date');
            $table->string('software_name');
            $table->string('software_type');
            $table->integer('license_count');
            $table->integer('duration_months')->nullable();
            $table->decimal('estimated_cost', 15, 2)->nullable();
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
        Schema::dropIfExists('software_requests');
    }
};
