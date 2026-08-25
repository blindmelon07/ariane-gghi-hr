<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Day-by-day duty roster for Nursing staff, built by the head nurse.
     * One row per employee per date:
     *   - schedule_id set   → that shift template applies on that date
     *   - schedule_id null  → explicitly marked OFF that date
     *   - no row at all     → not yet scheduled
     */
    public function up(): void
    {
        Schema::create('nurse_duty_rosters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('schedule_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['employee_id', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nurse_duty_rosters');
    }
};
