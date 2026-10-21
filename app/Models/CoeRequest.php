<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** COE Request Form: pending → released (by HR) → received. */
class CoeRequest extends Model
{
    public const STATUS_LABELS = [
        'pending'   => 'Pending',
        'released'  => 'Ready for pickup',
        'received'  => 'Received',
        'rejected'  => 'Declined',
        'cancelled' => 'Cancelled',
    ];

    protected $fillable = [
        'employee_id',
        'department',
        'designation',
        'date_hired',
        'date_separated',
        'reason',
        'additional_details',
        'signature_name',
        'status',
        'released_by',
        'released_at',
        'received_by_name',
        'received_at',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'date_hired'     => 'date',
            'date_separated' => 'date',
            'released_at'    => 'date',
            'received_at'    => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function releaser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }
}
