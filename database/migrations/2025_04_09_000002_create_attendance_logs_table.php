<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('emp_code');
            $table->dateTime('punch_time');
            $table->date('punch_date');
            $table->tinyInteger('punch_state')->default(0); // 0=in, 1=out
            $table->tinyInteger('verify_type')->default(0);
            $table->string('terminal_sn')->nullable();
            $table->boolean('is_processed')->default(false);
            $table->timestamps();

            $table->index(['employee_id', 'punch_date']);
            $table->unique(['emp_code', 'punch_time']); // prevent duplicate punches
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};
