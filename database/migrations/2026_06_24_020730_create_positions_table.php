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
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['name', 'department_id']);
        });

        // If add_position_id_to_employees ran first (same-timestamp ordering issue),
        // the column exists but without a FK — wire it now.
        if (Schema::hasColumn('employees', 'position_id')) {
            // Check FK not already present before adding
            try {
                Schema::table('employees', function (Blueprint $table) {
                    $table->foreign('position_id')->references('id')->on('positions')->nullOnDelete();
                });
            } catch (\Throwable) {
                // FK already exists — safe to ignore
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};
