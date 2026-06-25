<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_ticket_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_ticket_id')->constrained('trip_tickets')->cascadeOnDelete();
            $table->unsignedTinyInteger('step');
            $table->string('role', 30);
            $table->string('label', 60);
            $table->foreignId('approver_id')->constrained('users')->cascadeOnDelete();
            $table->string('action', 20); // approved, rejected
            $table->text('remarks')->nullable();
            $table->timestamp('acted_at');
            $table->timestamps();

            $table->unique(['trip_ticket_id', 'step']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_ticket_approvals');
    }
};
