<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->string('department', 100)->nullable();
            $table->string('destination_from', 150);
            $table->string('destination_to', 150);
            $table->dateTime('departure_datetime');
            $table->dateTime('return_datetime')->nullable();
            $table->text('passengers')->nullable();
            $table->text('purpose');
            $table->string('status', 20)->default('pending'); // pending, approved, rejected, cancelled, completed
            $table->unsignedTinyInteger('approval_step')->nullable()->default(1);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
            $table->index(['approval_step', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_tickets');
    }
};
