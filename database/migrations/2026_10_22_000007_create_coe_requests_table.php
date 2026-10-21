<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * COE (Certificate of Employment) Request Form. No approval chain:
     * the employee requests, HR releases the COE and records who received it.
     */
    public function up(): void
    {
        Schema::create('coe_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('department')->nullable();
            $table->string('designation')->nullable();
            $table->date('date_hired')->nullable();
            $table->date('date_separated')->nullable();
            $table->text('reason');
            $table->text('additional_details')->nullable(); // details to be indicated in the COE
            $table->string('signature_name');
            $table->string('status', 20)->default('pending'); // pending, released, received, rejected, cancelled
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('released_at')->nullable();
            $table->string('received_by_name')->nullable();
            $table->date('received_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coe_requests');
    }
};
