<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // SQLite: recreate column natively — foreign key enforcement is off by default
            Schema::table('attendance_logs', function (Blueprint $table) {
                $table->unsignedBigInteger('employee_id')->nullable()->change();
            });
            return;
        }

        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->foreignId('employee_id')->nullable()->change();
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('attendance_logs', function (Blueprint $table) {
                $table->unsignedBigInteger('employee_id')->nullable(false)->change();
            });
            return;
        }

        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->foreignId('employee_id')->nullable(false)->change();
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
        });
    }
};
