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
        Schema::table('payroll_periods', function (Blueprint $table) {
            // Which staff a period pays: 'nursing' = Nursing department only,
            // 'business' = everyone else. Periods created before the split are
            // 'all' so their existing payslips stay visible.
            $table->enum('payroll_group', ['all', 'business', 'nursing'])->default('all')->after('cutoff_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table) {
            $table->dropColumn('payroll_group');
        });
    }
};
