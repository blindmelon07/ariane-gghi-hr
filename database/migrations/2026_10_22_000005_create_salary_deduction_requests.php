<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Salary Deduction Form: an employee authorizes deductions; once HR
     * approves, each line becomes an OtherDeduction automatically. Deductions
     * with a total to pay (loans) are drawn down on each finalized payroll
     * and switch off when paid.
     */
    public function up(): void
    {
        // Particulars on the paper form that have no deduction type yet.
        foreach ([
            ['GSAC_SAVINGS', 'GSAC Savings', 'benefit'],
            ['GSAC_LOAN', 'GSAC Loan', 'loan'],
            ['GSAC_SHARE', 'GSAC Share Capital', 'benefit'],
            ['GSAC_DAMAYAN', 'GSAC Damayan Contribution', 'benefit'],
            ['OTHER_LOAN', 'Other Loan', 'loan'],
            ['OTHERS', 'Others', 'other'],
            ['SSS_LOAN', 'SSS Salary Loan', 'loan'],
            ['PAGIBIG_LOAN', 'Pag-IBIG Fund Loan', 'loan'],
            ['CASH_ADVANCE', 'Cash Advance', 'loan'],
        ] as [$code, $name, $category]) {
            if (! DB::table('deduction_types')->where('code', $code)->exists()) {
                DB::table('deduction_types')->insert([
                    'code' => $code, 'name' => $name, 'category' => $category, 'is_active' => true,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        Schema::table('other_deductions', function (Blueprint $table) {
            $table->date('starts_on')->nullable()->after('cutoff_schedule');          // first cut-off it applies to
            $table->boolean('auto_stop')->default(false)->after('remaining_balance'); // draw down remaining_balance, stop at 0
        });

        // One row per deduction per finalized payroll — the draw-down history,
        // and the guard against counting the same period twice.
        Schema::create('other_deduction_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('other_deduction_id')->constrained('other_deductions')->cascadeOnDelete();
            $table->foreignId('payroll_period_id')->constrained('payroll_periods')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->decimal('balance_after', 10, 2);
            $table->timestamps();

            $table->unique(['other_deduction_id', 'payroll_period_id'], 'deduction_payment_period_unique'); // default name exceeds MySQL's 64 chars
        });

        Schema::create('salary_deduction_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('attachment_path')->nullable(); // amortization table for loans
            $table->string('status', 20)->default('pending'); // pending, approved, rejected, cancelled
            $table->unsignedTinyInteger('approval_step')->nullable()->default(1);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
        });

        Schema::create('salary_deduction_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salary_deduction_request_id')->constrained('salary_deduction_requests')->cascadeOnDelete();
            $table->string('particular', 30);           // key of SalaryDeductionRequest::PARTICULARS
            $table->string('description')->nullable();  // for "Other loan" / "Others, please specify"
            $table->decimal('amount_per_cutoff', 10, 2);
            $table->decimal('total_amount', 10, 2)->nullable(); // set for loans; deduction stops when paid
            $table->enum('cutoff_schedule', ['both', '1st', '2nd'])->default('both');
            $table->date('starts_on');
            $table->foreignId('other_deduction_id')->nullable()->constrained('other_deductions')->nullOnDelete(); // created on approval
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_deduction_lines');
        Schema::dropIfExists('salary_deduction_requests');
        Schema::dropIfExists('other_deduction_payments');

        Schema::table('other_deductions', function (Blueprint $table) {
            $table->dropColumn(['starts_on', 'auto_stop']);
        });
    }
};
