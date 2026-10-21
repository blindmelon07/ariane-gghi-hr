<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Gate pass for patient transport (ambulance trips). Filed by drivers and
     * institutional workers, approved by HR before departure; the guard on
     * duty records the actual departure and arrival times.
     */
    public function up(): void
    {
        Schema::create('patient_transports', function (Blueprint $table) {
            $table->id();
            $table->string('series_no', 20)->nullable()->unique(); // PT-2026-00001, set right after insert
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete(); // who filed
            $table->date('date');
            $table->string('patient_name');
            $table->string('transported_to');
            $table->string('plate_number', 30);
            $table->string('driver_name');
            $table->text('medical_staff'); // one name per line
            $table->string('status', 20)->default('pending'); // pending, approved, completed, rejected, cancelled
            $table->unsignedTinyInteger('approval_step')->nullable()->default(1);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamp('departed_at')->nullable();
            $table->foreignId('departed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('arrived_at')->nullable();
            $table->foreignId('arrived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_transports');
    }
};
