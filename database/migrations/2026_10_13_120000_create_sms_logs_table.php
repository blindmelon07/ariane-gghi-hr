<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->string('recipient', 20);
            $table->text('content');                       // passwords are masked before storing
            $table->string('type', 30);                    // leave, overtime, payslip, announcement, custom, attendance, credentials
            $table->string('context_key')->nullable();     // de-duplication key, e.g. payslip:21, late:2026-10-13
            $table->string('status', 20)->default('pending'); // pending, queued (accepted by UniSMS), failed, skipped
            $table->string('reference_id')->nullable();    // UniSMS message reference
            $table->text('error')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['type', 'context_key']);
            $table->index('reference_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};
