<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite (used in tests) stores enums as TEXT and ignores MODIFY COLUMN
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('employee','hr_admin','manager','approver','super_admin','security_guard') NOT NULL DEFAULT 'employee'");
        }

        Schema::table('trip_tickets', function (Blueprint $table) {
            $table->text('return_remarks')->nullable()->after('completed_by');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('employee','hr_admin','manager','approver','super_admin') NOT NULL DEFAULT 'employee'");
        }

        Schema::table('trip_tickets', function (Blueprint $table) {
            $table->dropColumn('return_remarks');
        });
    }
};
