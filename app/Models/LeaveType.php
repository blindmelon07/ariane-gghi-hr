<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    protected $fillable = [
        'code',
        'name',
        'max_days_per_year',
        'is_paid',
        'requires_approval',
    ];

    protected function casts(): array
    {
        return [
            'max_days_per_year' => 'integer',
            'is_paid'           => 'boolean',
            'requires_approval' => 'boolean',
        ];
    }

    public function leaveCredits(): HasMany
    {
        return $this->hasMany(LeaveCredit::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
