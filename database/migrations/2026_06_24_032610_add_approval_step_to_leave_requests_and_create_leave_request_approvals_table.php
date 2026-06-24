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
        // 1 = waiting HR, 2 = waiting Medical Director, 3 = waiting CEO, null = completed
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->unsignedTinyInteger('approval_step')->nullable()->default(1)->after('status');
        });

        // Backfill existing pending requests to step 1
        \DB::table('leave_requests')->where('status', 'pending')->update(['approval_step' => 1]);
        // Completed requests have no pending step
        \DB::table('leave_requests')->whereIn('status', ['approved', 'rejected', 'cancelled'])->update(['approval_step' => null]);

        Schema::create('leave_request_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_request_id')->constrained('leave_requests')->cascadeOnDelete();
            $table->unsignedTinyInteger('step');          // 1, 2, or 3
            $table->string('role', 30);                   // hr_admin, approver, super_admin
            $table->string('label', 50);                  // HR, Medical Director, CEO
            $table->foreignId('approver_id')->constrained('users');
            $table->enum('action', ['approved', 'rejected']);
            $table->text('remarks')->nullable();
            $table->timestamp('acted_at');
            $table->timestamps();

            $table->unique(['leave_request_id', 'step']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_request_approvals');
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn('approval_step');
        });
    }
};
