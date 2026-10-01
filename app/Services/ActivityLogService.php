<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogService
{
    /**
     * Sensitive fields that should never be stored in audit logs.
     */
    protected array $sensitiveKeys = [
        'password',
        'password_confirmation',
        'remember_token',
        'access_token',
        'secret',
        'token',
        'pin',
        'cvv',
        'card_number',
    ];

    /**
     * Filter out sensitive values from payload arrays.
     */
    protected function sanitize(?array $data): ?array
    {
        if (!$data) {
            return null;
        }

        $sanitized = [];
        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $this->sensitiveKeys, true)) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitize($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Log an auditable event for any model entity.
     */
    public function log(string $event, ?Model $model = null, ?array $oldValues = null, ?array $newValues = null): ActivityLog
    {
        $user = Auth::user();

        return ActivityLog::create([
            'company_id' => $model?->company_id ?? ($user?->company_id ?? null),
            'branch_id' => $model?->branch_id ?? ($user?->branch_id ?? null),
            'user_id' => $user?->id,
            'event' => $event,
            'auditable_type' => $model ? get_class($model) : 'System',
            'auditable_id' => $model?->id ?? 0,
            'old_values' => $this->sanitize($oldValues),
            'new_values' => $this->sanitize($newValues),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
        ]);
    }
}
