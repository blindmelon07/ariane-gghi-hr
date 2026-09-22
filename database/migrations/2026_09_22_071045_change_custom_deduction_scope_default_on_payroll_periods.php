<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'both' as an explicit scope value (both-tagged deductions only —
        // the safe default for a custom range with no inherent 1st/2nd-half
        // identity) and make it the default, replacing 'all'. Dropping and
        // re-adding (rather than a raw MODIFY COLUMN) keeps this portable
        // across MySQL and the SQLite test database.
        $existing = DB::table('payroll_periods')->pluck('custom_deduction_scope', 'id');

        Schema::table('payroll_periods', function (Blueprint $table) {
            $table->dropColumn('custom_deduction_scope');
        });

        Schema::table('payroll_periods', function (Blueprint $table) {
            $table->enum('custom_deduction_scope', ['both', '1st', '2nd', 'all'])->default('both')->after('cutoff_type');
        });

        foreach ($existing as $id => $value) {
            DB::table('payroll_periods')->where('id', $id)->update([
                'custom_deduction_scope' => $value === 'all' ? 'both' : $value,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table) {
            $table->dropColumn('custom_deduction_scope');
        });

        Schema::table('payroll_periods', function (Blueprint $table) {
            $table->enum('custom_deduction_scope', ['all', '1st', '2nd'])->default('all')->after('cutoff_type');
        });
    }
};
