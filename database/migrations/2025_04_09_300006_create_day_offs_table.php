<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('day_offs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->enum('type', ['rest_day', 'holiday', 'special', 'other'])->default('rest_day');
            $table->string('description')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->unsignedTinyInteger('recurring_day_of_week')->nullable()->comment('0=Sun,6=Sat');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['employee_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('day_offs');
    }
};
