<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceLog extends Model
{
    protected $fillable = [
        'employee_id',
        'emp_code',
        'punch_time',
        'punch_date',
        'punch_state',
        'verify_type',
        'terminal_sn',
        'is_processed',
    ];

    protected function casts(): array
    {
        return [
            'punch_time'   => 'datetime',
            'punch_date'   => 'date',
            'punch_state'  => 'integer',
            'verify_type'  => 'integer',
            'is_processed' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
