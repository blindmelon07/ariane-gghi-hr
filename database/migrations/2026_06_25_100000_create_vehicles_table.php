<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('plate_number', 20)->unique();
            $table->string('make', 60);
            $table->string('model', 60);
            $table->enum('vehicle_type', ['sedan', 'van', 'SUV', 'pickup', 'ambulance', 'truck'])->default('van');
            $table->unsignedSmallInteger('capacity')->default(4);
            $table->unsignedSmallInteger('year')->nullable();
            $table->enum('status', ['available', 'in_use', 'under_maintenance'])->default('available');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
