<?php

namespace App\Models;

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
        'department',
        'department_id',
        'position',
        'position_id',
        'date_of_birth',
        'hire_date',
        'is_active',
        'employment_type',
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
