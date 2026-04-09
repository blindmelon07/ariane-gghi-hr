<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('payroll_period_id')->constrained('payroll_periods')->cascadeOnDelete();
            $table->unsignedInteger('working_days')->default(0);
            $table->decimal('days_present', 5, 2)->default(0);
            $table->decimal('days_absent', 5, 2)->default(0);
            $table->decimal('basic_pay', 10, 2)->default(0);
            $table->decimal('overtime_hours', 5, 2)->default(0);
            $table->decimal('overtime_pay', 10, 2)->default(0);
            $table->unsignedInteger('late_minutes')->default(0);
            $table->decimal('late_deduction', 10, 2)->default(0);
            $table->unsignedInteger('undertime_minutes')->default(0);
            $table->decimal('undertime_deduction', 10, 2)->default(0);
            $table->decimal('gross_pay', 10, 2)->default(0);
            $table->decimal('sss_deduction', 10, 2)->default(0);
            $table->decimal('philhealth_deduction', 10, 2)->default(0);
            $table->decimal('pagibig_deduction', 10, 2)->default(0);
            $table->decimal('tax_deduction', 10, 2)->default(0);
            $table->decimal('other_deductions', 10, 2)->default(0);
            $table->decimal('total_deductions', 10, 2)->default(0);
            $table->decimal('net_pay', 10, 2)->default(0);
            $table->string('status', 20)->default('draft'); // draft, finalized
            $table->timestamps();

            $table->unique(['employee_id', 'payroll_period_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};
