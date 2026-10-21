<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Two more employee requests that follow the leave approval chain:
     * a temporary shift for specific dates, and a planned early time-out.
     */
    public function up(): void
    {
        Schema::create('shift_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->foreignId('schedule_id')->constrained('schedules')->cascadeOnDelete(); // shift worked on those dates
            $table->text('reason');
            $table->string('status', 20)->default('pending'); // pending, approved, rejected, cancelled
            $table->unsignedTinyInteger('approval_step')->nullable()->default(1);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'status', 'start_date']);
        });

        Schema::create('undertime_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('date');
            $table->time('time_out'); // planned early time-out
            $table->text('reason');
            $table->string('status', 20)->default('pending'); // pending, approved, rejected, cancelled
            $table->unsignedTinyInteger('approval_step')->nullable()->default(1);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'status', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('undertime_requests');
        Schema::dropIfExists('shift_change_requests');
    }
};
