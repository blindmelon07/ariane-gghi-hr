<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Digital version of the paper gate pass: an employee asks to step out of
     * the premises, HR approves, and the guard checks the approved pass.
     */
    public function up(): void
    {
        Schema::create('gate_passes', function (Blueprint $table) {
            $table->id();
            $table->string('series_no', 20)->nullable()->unique(); // GP-2026-00001, set right after insert
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('date');
            $table->time('time_out');
            $table->time('time_of_arrival'); // expected return
            $table->string('destination');
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
        Schema::dropIfExists('gate_passes');
    }
};
