<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Commodity Allowance Form — filled once by each regular employee
     * (and updatable later); HR reviews the submissions.
     */
    public function up(): void
    {
        Schema::create('commodity_allowance_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->unique()->constrained('employees')->cascadeOnDelete();
            $table->string('last_name');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('position');
            $table->string('address');
            $table->string('religion')->nullable();
            $table->date('birthdate');
            $table->string('civil_status', 20);          // single, married, widowed
            $table->string('spouse_name')->nullable();
            $table->date('spouse_birthdate')->nullable();
            $table->string('gsac_membership', 20);       // regular, associate, non_member
            $table->string('passbook_number', 50)->nullable();
            $table->string('signature_name');            // typed name, acknowledged as the signature
            $table->timestamp('submitted_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commodity_allowance_forms');
    }
};
