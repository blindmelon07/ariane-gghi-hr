<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('day_off_swaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('original_date'); // rest day being given up (becomes a working day)
            $table->date('new_date');      // date that becomes the rest day instead
            $table->text('reason');
            $table->string('status', 20)->default('pending'); // pending, approved, rejected, cancelled
            $table->unsignedTinyInteger('approval_step')->nullable()->default(1);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'status', 'original_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('day_off_swaps');
    }
};
