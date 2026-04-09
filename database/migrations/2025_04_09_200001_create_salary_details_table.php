<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->unique()->constrained('employees')->cascadeOnDelete();
            $table->string('rate_type', 10)->default('monthly'); // monthly, daily
            $table->decimal('basic_salary', 10, 2)->default(0);
            $table->decimal('daily_rate', 10, 2)->default(0);
            $table->decimal('hourly_rate', 10, 2)->default(0);
            $table->date('effective_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_details');
    }
};
