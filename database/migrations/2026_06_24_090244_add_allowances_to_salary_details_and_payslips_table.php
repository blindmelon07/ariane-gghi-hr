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
        // Allowances stored per semi-monthly period in salary setup
        Schema::table('salary_details', function (Blueprint $table) {
            $table->decimal('hazard_pay', 10, 2)->default(0)->after('hourly_rate');
            $table->decimal('rice_allowance', 10, 2)->default(0)->after('hazard_pay');
            $table->decimal('medical_allowance', 10, 2)->default(0)->after('rice_allowance');
            $table->decimal('commodity_allowance', 10, 2)->default(0)->after('medical_allowance');
            $table->decimal('other_allowance', 10, 2)->default(0)->after('commodity_allowance');
        });

        // Snapshot of allowances paid in each payslip
        Schema::table('payslips', function (Blueprint $table) {
            $table->decimal('hazard_pay', 10, 2)->default(0)->after('gross_pay');
            $table->decimal('rice_allowance', 10, 2)->default(0)->after('hazard_pay');
            $table->decimal('medical_allowance', 10, 2)->default(0)->after('rice_allowance');
            $table->decimal('commodity_allowance', 10, 2)->default(0)->after('medical_allowance');
            $table->decimal('other_allowance', 10, 2)->default(0)->after('commodity_allowance');
            $table->decimal('total_allowances', 10, 2)->default(0)->after('other_allowance');
        });
    }

    public function down(): void
    {
        Schema::table('salary_details', function (Blueprint $table) {
            $table->dropColumn(['hazard_pay', 'rice_allowance', 'medical_allowance', 'commodity_allowance', 'other_allowance']);
        });
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropColumn(['hazard_pay', 'rice_allowance', 'medical_allowance', 'commodity_allowance', 'other_allowance', 'total_allowances']);
        });
    }
};
