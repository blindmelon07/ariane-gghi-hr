<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Overtime Request Form (rev. Jan 2025): one form covers several dates,
     * each with a From/To time, and goes Dept Head → HR → CEO/MD.
     * The per-date rows stay in overtime_requests — payroll and the overtime
     * report keep reading them — and take their status from the form.
     */
    public function up(): void
    {
        Schema::create('overtime_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->text('purpose'); // purpose and accomplishment
            $table->time('schedule_from')->nullable(); // regular working schedule at filing
            $table->time('schedule_to')->nullable();
            $table->string('status', 20)->default('pending'); // pending, approved, rejected, cancelled
            $table->unsignedTinyInteger('approval_step')->nullable()->default(1);
            $table->foreignId('head_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('head_approved_at')->nullable();
            $table->foreignId('hr_verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('hr_verified_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete(); // final (CEO / MD)
            $table->timestamp('approved_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
        });

        Schema::table('overtime_requests', function (Blueprint $table) {
            $table->foreignId('overtime_form_id')->nullable()->after('id')->constrained('overtime_forms')->cascadeOnDelete();
            $table->time('time_from')->nullable()->after('date');
            $table->time('time_to')->nullable()->after('time_from');
        });

        // Wrap each existing single-date request in its own form so every
        // request shows up in the new screens. Pending ones restart at step 1.
        foreach (DB::table('overtime_requests')->orderBy('id')->get() as $ot) {
            $formId = DB::table('overtime_forms')->insertGetId([
                'employee_id'   => $ot->employee_id,
                'purpose'       => $ot->reason,
                'status'        => $ot->status,
                'approval_step' => $ot->status === 'pending' ? 1 : null,
                'approved_by'   => $ot->approved_by,
                'approved_at'   => $ot->approved_at,
                'remarks'       => $ot->remarks,
                'created_at'    => $ot->created_at,
                'updated_at'    => $ot->updated_at,
            ]);

            DB::table('overtime_requests')->where('id', $ot->id)->update(['overtime_form_id' => $formId]);
        }
    }

    public function down(): void
    {
        Schema::table('overtime_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('overtime_form_id');
            $table->dropColumn(['time_from', 'time_to']);
        });

        Schema::dropIfExists('overtime_forms');
    }
};
