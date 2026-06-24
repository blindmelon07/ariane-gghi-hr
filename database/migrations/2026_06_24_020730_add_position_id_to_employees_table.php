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
        if (Schema::hasColumn('employees', 'position_id')) {
            return; // already exists on local dev
        }

        Schema::table('employees', function (Blueprint $table) {
            // Add plain column first — FK added separately below once positions table exists
            $table->unsignedBigInteger('position_id')->nullable()->after('department_id');
        });

        // Add FK only when positions table exists (it may run after this migration on some servers)
        if (Schema::hasTable('positions')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->foreign('position_id')->references('id')->on('positions')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('employees', 'position_id')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            try {
                $table->dropForeign(['position_id']);
            } catch (\Throwable) {
            }
            $table->dropColumn('position_id');
        });
    }
};
