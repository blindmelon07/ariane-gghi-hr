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
            // Only meaningful when cutoff_type = 'custom': which OtherDeduction
            // cutoff_schedule tags should apply, since a custom date range has
            // no inherent 1st-half/2nd-half identity. 'all' = both/1st/2nd.
            $table->enum('custom_deduction_scope', ['all', '1st', '2nd'])->default('all')->after('cutoff_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table) {
            $table->dropColumn('custom_deduction_scope');
        });
    }
};
