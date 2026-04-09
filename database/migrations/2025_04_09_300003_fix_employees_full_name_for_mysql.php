<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the SQLite-syntax stored column and recreate with MySQL CONCAT()
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('full_name');
        });

        DB::statement("ALTER TABLE employees ADD full_name VARCHAR(255) AS (CONCAT(first_name, ' ', last_name)) STORED AFTER last_name");
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('full_name');
        });

        DB::statement("ALTER TABLE employees ADD full_name VARCHAR(255) AS (first_name || ' ' || last_name) STORED AFTER last_name");
    }
};
