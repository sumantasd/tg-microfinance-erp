<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'employee_id',
        'attendance_date',
        'clock_in',
        'clock_out',
        'working_duration_minutes',
        'working_duration_hours',
        'status',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'working_duration_minutes' => 'integer',
            'working_duration_hours' => 'float',
        ];
    }

    /**
     * Check if this attendance is currently active (checked in, not yet checked out).
     */
    public function isActive(): bool
    {
        return !empty($this->clock_in) && empty($this->clock_out);
    }

    /**
     * Check if this attendance shift has completed (both clock in and clock out recorded).
     */
    public function isCompleted(): bool
    {
        return !empty($this->clock_in) && !empty($this->clock_out);
    }

    /**
     * Convert a stored UTC clock time string to Indian Standard Time (IST) formatted string.
     */
    public function formatTimeIst(?string $timeString, string $format = 'h:i A'): ?string
    {
        if (empty($timeString)) {
            return null;
        }

        $date = $this->attendance_date?->format('Y-m-d') ?? now()->toDateString();
        $tz = config('app.business_timezone', 'Asia/Kolkata');

        return \Carbon\Carbon::parse("{$date} {$timeString}", 'UTC')
            ->setTimezone($tz)
            ->format($format);
    }

    /**
     * Authoritative IST formatted Check-In time.
     */
    public function getClockInFormattedAttribute(): ?string
    {
        return $this->formatTimeIst($this->clock_in);
    }

    /**
     * Authoritative IST formatted Check-Out time.
     */
    public function getClockOutFormattedAttribute(): ?string
    {
        return $this->formatTimeIst($this->clock_out);
    }

    /**
     * Formatted working duration (e.g., '8 hrs 24 mins') using actual elapsed timestamps.
     */
    public function getFormattedDurationAttribute(): ?string
    {
        if ($this->working_duration_minutes !== null) {
            $hours = intdiv($this->working_duration_minutes, 60);
            $mins = $this->working_duration_minutes % 60;
            return "{$hours} hrs {$mins} mins";
        }

        if (!empty($this->clock_in) && !empty($this->clock_out)) {
            $date = $this->attendance_date?->format('Y-m-d') ?? now()->toDateString();
            $in = \Carbon\Carbon::parse("{$date} {$this->clock_in}", 'UTC');
            $out = \Carbon\Carbon::parse("{$date} {$this->clock_out}", 'UTC');
            if ($out->lt($in)) {
                $out = $out->copy()->addDay();
            }
            $diffMins = max(0, $in->diffInMinutes($out));
            $hours = intdiv($diffMins, 60);
            $mins = $diffMins % 60;
            return "{$hours} hrs {$mins} mins";
        }

        return null;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
