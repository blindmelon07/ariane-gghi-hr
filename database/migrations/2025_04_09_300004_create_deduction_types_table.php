<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deduction_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->enum('category', ['government', 'loan', 'benefit', 'other'])->default('other');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('other_deductions', function (Blueprint $table) {
            $table->foreignId('deduction_type_id')->nullable()->after('employee_id')
                  ->constrained('deduction_types')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('other_deductions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('deduction_type_id');
        });
        Schema::dropIfExists('deduction_types');
    }
};
