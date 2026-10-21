<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Leave is no longer capped by credits: monthly credits only start one year
     * after regularization, and any leave beyond the balance is filed unpaid.
     * paid_days records how much of each request is covered by credits.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->date('regularization_date')->nullable()->after('hire_date');
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->decimal('paid_days', 4, 1)->nullable()->after('total_days');
        });

        // Before this rule every approved leave was fully paid.
        DB::table('leave_requests')->update(['paid_days' => DB::raw('total_days')]);
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn('paid_days');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('regularization_date');
        });
    }
};
