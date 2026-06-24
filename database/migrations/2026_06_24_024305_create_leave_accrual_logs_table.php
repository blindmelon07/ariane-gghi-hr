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
        Schema::create('leave_accrual_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');   // 1–12
            $table->decimal('accrual_days', 4, 1);  // 2.5
            $table->unsignedInteger('employee_count')->default(0);
            $table->enum('triggered_by', ['auto', 'manual'])->default('auto');
            $table->timestamps();

            $table->unique(['year', 'month']);       // one accrual per month
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_accrual_logs');
    }
};
