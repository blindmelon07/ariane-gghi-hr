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
        Schema::table('employees', function (Blueprint $table) {
            // Weekday rest day besides Sunday (0-6, null = Sunday only / Probationary)
            // 1 = Monday, 6 = Saturday (default for regular)
            $table->tinyInteger('weekday_off')->unsigned()->nullable()->after('employment_type');
        });

        // Backfill: regular → Saturday off, probationary → null
        \DB::table('employees')->where('employment_type', 'regular')->update(['weekday_off' => 6]);
        \DB::table('employees')->where('employment_type', 'probationary')->orWhereNull('employment_type')->update(['weekday_off' => null]);
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('weekday_off');
        });
    }
};
