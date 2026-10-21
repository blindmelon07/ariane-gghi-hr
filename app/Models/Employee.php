<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    protected $fillable = [
        'user_id',
        'emp_code',
        'first_name',
        'last_name',
        'cell_number',
        'department',
        'department_id',
        'position',
        'position_id',
        'date_of_birth',
        'hire_date',
        'regularization_date',
        'is_active',
        'employment_type',
        'weekday_off',
        'biotime_id',
        'synced_at',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $employee) {
            if ($employee->isDirty('department_id')) {
                $employee->department = $employee->department_id
                    ? Department::where('id', $employee->department_id)->value('name')
                    : null;
            }
            if ($employee->isDirty('position_id')) {
                $employee->position = $employee->position_id
                    ? Position::where('id', $employee->position_id)->value('name')
                    : null;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'hire_date'      => 'date',
            'regularization_date' => 'date',
            'date_of_birth'  => 'date',
            'is_active'  => 'boolean',
            'synced_at'  => 'datetime',
        ];
    }

    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function leaveCredits(): HasMany
    {
        return $this->hasMany(LeaveCredit::class);
    }

    public function commodityAllowanceForm(): HasOne
    {
        return $this->hasOne(CommodityAllowanceForm::class);
    }

    public function salaryDetail(): HasOne
    {
        return $this->hasOne(SalaryDetail::class);
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function otherDeductions(): HasMany
    {
        return $this->hasMany(OtherDeduction::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dept(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function pos(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    /**
     * Order by the surname actually shown in the UI.
     *
     * Rows imported from BioTime can arrive as "Last,First[,Middle]" packed into
     * first_name with last_name left empty — fullName() unpacks those for display,
     * but a plain orderBy('last_name') sorts them on an empty string and dumps them
     * at the top of the list, out of order with everyone else. This mirrors the
     * accessor's rule in SQL so the list order matches what the user reads.
     *
     * INSTR/SUBSTR/COALESCE are available on both MySQL and SQLite.
     */
    public function scopeOrderByFullName(Builder $query, string $direction = 'asc'): Builder
    {
        $dir = strtolower($direction) === 'desc' ? 'desc' : 'asc';

        return $query
            ->orderByRaw(
                "CASE
                    WHEN COALESCE(last_name, '') = '' AND INSTR(first_name, ',') > 0
                        THEN SUBSTR(first_name, 1, INSTR(first_name, ',') - 1)
                    WHEN COALESCE(last_name, '') = ''
                        THEN first_name
                    ELSE last_name
                 END {$dir}"
            )
            ->orderBy('first_name', $dir);
    }

    /**
     * Employees who have been regular for at least one year as of $asOf —
     * only they earn monthly paid-leave credits (HR policy).
     */
    public function scopeEligibleForLeaveAccrual(Builder $query, \DateTimeInterface $asOf): Builder
    {
        return $query->whereNotNull('regularization_date')
            ->whereDate('regularization_date', '<=', \Illuminate\Support\Carbon::instance($asOf)->subYear());
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: function () {
                $first = trim($this->first_name ?? '');
                $last  = trim($this->last_name ?? '');

                // Handle "Last,First" or "Last,First,Middle" stored in first_name with empty last_name
                if ($last === '' && str_contains($first, ',')) {
                    $parts = array_map('trim', explode(',', $first));
                    $surname = array_shift($parts);
                    $first = implode(' ', $parts) . ' ' . $surname;
                } else {
                    $first = $first . ($last !== '' ? ' ' . $last : '');
                }

                return ucwords(strtolower(trim($first)));
            },
        );
    }
}
