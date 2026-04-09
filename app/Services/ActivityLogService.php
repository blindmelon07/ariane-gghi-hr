<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class ActivityLogService
{
    public static function log(string $action, string $description, ?Model $subject = null): void
    {
        ActivityLog::create([
            'user_id'      => auth()->id(),
            'action'       => $action,
            'description'  => $description,
            'subject_type' => $subject ? $subject->getMorphClass() : null,
            'subject_id'   => $subject?->getKey(),
            'ip_address'   => request()->ip(),
            'created_at'   => now(),
        ]);
    }
}
