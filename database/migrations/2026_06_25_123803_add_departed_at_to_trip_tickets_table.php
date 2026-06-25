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
        Schema::table('trip_tickets', function (Blueprint $table) {
            $table->timestamp('departed_at')->nullable()->after('approved_at');
            $table->foreignId('departed_by')->nullable()->constrained('users')->nullOnDelete()->after('departed_at');
        });
    }

    public function down(): void
    {
        Schema::table('trip_tickets', function (Blueprint $table) {
            $table->dropForeign(['departed_by']);
            $table->dropColumn(['departed_at', 'departed_by']);
        });
    }
};
