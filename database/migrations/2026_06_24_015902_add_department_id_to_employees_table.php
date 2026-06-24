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
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('last_name')
                  ->constrained('departments')->nullOnDelete();
        });

        // Seed departments from existing string values and link employees
        $existing = \DB::table('employees')
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->distinct()
            ->pluck('department');

        foreach ($existing as $name) {
            $dept = \DB::table('departments')->insertGetId([
                'name'       => $name,
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            \DB::table('employees')->where('department', $name)->update(['department_id' => $dept]);
        }
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
        });
    }
};
