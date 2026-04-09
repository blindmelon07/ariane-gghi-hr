<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->string('name');                     // e.g. "Admin Morning", "Nursing Day Shift"
            $table->string('department');                // e.g. "Admin", "Nursing"
            $table->time('time_in');
            $table->time('time_out');
            $table->time('break_start')->nullable();    // lunch break start
            $table->time('break_end')->nullable();      // lunch break end
            $table->time('time_in_2')->nullable();      // split shift 2nd period in
            $table->time('time_out_2')->nullable();     // split shift 2nd period out
            $table->boolean('is_night_shift')->default(false);
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('employee_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('schedule_id')->constrained()->cascadeOnDelete();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();   // null = indefinite
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'effective_from', 'effective_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_schedules');
        Schema::dropIfExists('schedules');
    }
};
