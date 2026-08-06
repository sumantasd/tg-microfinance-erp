<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogService
{
    /**
     * Log an auditable event for any model entity.
     */
    public function log(string $event, Model $model, ?array $oldValues = null, ?array $newValues = null): ActivityLog
    {
        $user = Auth::user();

        return ActivityLog::create([
            'company_id' => $model->company_id ?? ($user->company_id ?? null),
            'branch_id' => $model->branch_id ?? ($user->branch_id ?? null),
            'user_id' => $user?->id,
            'event' => $event,
            'auditable_type' => get_class($model),
            'auditable_id' => $model->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
        ]);
    }
}
