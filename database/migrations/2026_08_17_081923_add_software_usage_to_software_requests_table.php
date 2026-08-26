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
        Schema::table('software_requests', function (Blueprint $table) {
            $table->string('software_usage')->nullable()->after('software_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('software_requests', function (Blueprint $table) {
            $table->dropcolumn('software_usage');
        });
    }
};
