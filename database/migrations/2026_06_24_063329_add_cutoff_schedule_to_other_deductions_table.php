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
        Schema::table('other_deductions', function (Blueprint $table) {
            // 'both' = every cutoff, '1st' = first cutoff only, '2nd' = second cutoff only
            $table->enum('cutoff_schedule', ['both', '1st', '2nd'])->default('both')->after('amount_per_cutoff');
        });
    }

    public function down(): void
    {
        Schema::table('other_deductions', function (Blueprint $table) {
            $table->dropColumn('cutoff_schedule');
        });
    }
};
