<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveCredit extends Model
{
    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'year',
        'total_credits',
        'used_credits',
    ];

    protected function casts(): array
    {
        return [
            'year'              => 'integer',
            'total_credits'     => 'decimal:1',
            'used_credits'      => 'decimal:1',
            'remaining_credits' => 'decimal:1',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }
}
