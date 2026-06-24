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
        Schema::table('payslips', function (Blueprint $table) {
            // Absent deduction = daysAbsent × 8 hrs × hourly_rate (hours-based approach)
            $table->decimal('absent_deduction', 10, 2)->default(0)->after('days_absent');
        });
    }

    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropColumn('absent_deduction');
        });
    }
};
