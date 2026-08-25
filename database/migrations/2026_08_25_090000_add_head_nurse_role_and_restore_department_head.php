<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Adds the new 'head_nurse' role.
     *
     * Also restores 'department_head' to the enum: the 2026_06_25_121436
     * migration rebuilt this ENUM for 'security_guard' but dropped
     * 'department_head' in the process, even though the Spatie role,
     * LeaveService/TripTicketService approval chains, and the sidebar all
     * still reference it. No live user currently holds that role, so this
     * has been a silent landmine rather than a visible bug.
     */
    public function up(): void
    {
        // MySQL-only: SQLite (used in tests) stores enums as TEXT.
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('employee','hr_admin','manager','approver','super_admin','security_guard','department_head','head_nurse') NOT NULL DEFAULT 'employee'");
        }

        Role::firstOrCreate(['name' => 'head_nurse', 'guard_name' => 'web']);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('employee','hr_admin','manager','approver','super_admin','security_guard') NOT NULL DEFAULT 'employee'");
        }

        Role::where('name', 'head_nurse')->delete();
    }
};
