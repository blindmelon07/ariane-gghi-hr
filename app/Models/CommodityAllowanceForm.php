<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Commodity Allowance Form. Regular employees are asked to fill it in
 * (banner + menu link) until they have one on file.
 */
class CommodityAllowanceForm extends Model
{
    public const CIVIL_STATUSES = [
        'single'  => 'Single',
        'married' => 'Married',
        'widowed' => 'Widow/er',
    ];

    public const MEMBERSHIPS = [
        'regular'    => 'Regular',
        'associate'  => 'Associate Member',
        'non_member' => 'Non-Member',
    ];

    protected $fillable = [
        'employee_id',
        'last_name',
        'first_name',
        'middle_name',
        'position',
        'address',
        'religion',
        'birthdate',
        'civil_status',
        'spouse_name',
        'spouse_birthdate',
        'gsac_membership',
        'passbook_number',
        'signature_name',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'birthdate'        => 'date',
            'spouse_birthdate' => 'date',
            'submitted_at'     => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** Only regular employees fill this form. */
    public static function appliesTo(?Employee $employee): bool
    {
        return $employee !== null && ($employee->employment_type ?? 'regular') === 'regular';
    }

    /** True when $employee should be prompted: regular and nothing on file yet. */
    public static function isDueFor(?Employee $employee): bool
    {
        return self::appliesTo($employee)
            && ! self::where('employee_id', $employee->id)->exists();
    }

    /** The logged-in user's employee record, if any. */
    public static function employeeForCurrentUser(): ?Employee
    {
        $code = auth()->user()?->employee_code;

        return $code ? Employee::where('emp_code', $code)->first() : null;
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }
}
