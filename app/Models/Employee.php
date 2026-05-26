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
        'position',
        'date_of_birth',
        'hire_date',
        'is_active',
        'biotime_id',
        'synced_at',
    ];

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
