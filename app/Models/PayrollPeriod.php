<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollPeriod extends Model
{
    protected $fillable = [
        'name',
        'cutoff_type',
        'payroll_group',
        'custom_deduction_scope',
        'start_date',
        'end_date',
        'status',
        'processed_at',
        'finalized_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date'   => 'date',
            'end_date'     => 'date',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * Limit an Employee query to the staff this period pays: Nursing department
     * for 'nursing', everyone else for 'business', no limit for 'all'.
     */
    public static function limitEmployeesToGroup(Builder $query, ?string $group): Builder
    {
        $isNursing = "LOWER(TRIM(COALESCE(department, ''))) = 'nursing'";

        return match ($group) {
            'nursing'  => $query->whereRaw($isNursing),
            'business' => $query->whereRaw("NOT ({$isNursing})"),
            default    => $query,
        };
    }

    public function groupLabel(): string
    {
        return match ($this->payroll_group) {
            'nursing'  => 'Nursing',
            'business' => 'Business',
            default    => 'All',
        };
    }

    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }
}
