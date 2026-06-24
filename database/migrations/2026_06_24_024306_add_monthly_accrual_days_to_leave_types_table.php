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
        Schema::table('leave_types', function (Blueprint $table) {
            // null = no monthly accrual; 2.5 = earns 2.5 days per month
            $table->decimal('monthly_accrual_days', 4, 1)->nullable()->default(null)->after('max_days_per_year');
        });

        // Seed VL and SL with 2.5 days per month
        \DB::table('leave_types')
            ->whereIn('code', ['VL', 'SL'])
            ->update(['monthly_accrual_days' => 2.5]);
    }

    public function down(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->dropColumn('monthly_accrual_days');
        });
    }
};
