<?php

namespace App\Services;

use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class DeveloperAuthService
{
    public const SESSION_AUTHORIZED_UNTIL = 'developer_authorized_until';
    public const SESSION_AUTH_TOKEN = 'developer_auth_token';

    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    /**
     * Check if developer authorization secret hash is configured in the environment.
     */
    public function isConfigured(): bool
    {
        $hash = config('developer.secret_hash');
        return !empty($hash) && is_string($hash);
    }

    /**
     * Determine if the developer rate limit is exceeded for the request.
     */
    public function isRateLimited(Request $request): bool
    {
        $key = $this->getRateLimitKey($request);
        $maxAttempts = config('developer.rate_limit_max_attempts', 5);

        return RateLimiter::tooManyAttempts($key, $maxAttempts);
    }

    /**
     * Seconds remaining before rate limit decay resets.
     */
    public function getRateLimitRemainingSeconds(Request $request): int
    {
        $key = $this->getRateLimitKey($request);
        return RateLimiter::availableIn($key);
    }

    /**
     * Increment rate limiter on failed attempt.
     */
    public function recordFailedAttempt(Request $request): void
    {
        $key = $this->getRateLimitKey($request);
        $decaySeconds = config('developer.rate_limit_decay_seconds', 300);

        RateLimiter::hit($key, $decaySeconds);

        $user = Auth::user();
        Log::warning('Developer authorization failed attempt.', [
            'ip' => $request->ip(),
            'user_id' => $user?->id,
            'user_email' => $user?->email,
        ]);

        $this->activityLogService->log(
            event: 'developer_auth_failed',
            model: $user,
            oldValues: null,
            newValues: ['ip' => $request->ip(), 'reason' => 'invalid_passphrase']
        );
    }

    /**
     * Clear rate limiter on successful authorization.
     */
    public function clearRateLimit(Request $request): void
    {
        $key = $this->getRateLimitKey($request);
        RateLimiter::clear($key);
    }

    /**
     * Verify the supplied passphrase against the configured secure hash.
     */
    public function verifyCredentials(string $passphrase, Request $request): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        if ($this->isRateLimited($request)) {
            return false;
        }

        $hash = config('developer.secret_hash');
        $valid = Hash::check($passphrase, $hash);

        if (!$valid) {
            $this->recordFailedAttempt($request);
            return false;
        }

        $this->clearRateLimit($request);
        $this->authorizeSession($request);

        $user = Auth::user();
        Log::info('Developer authorization granted successfully.', [
            'ip' => $request->ip(),
            'user_id' => $user?->id,
        ]);

        $this->activityLogService->log(
            event: 'developer_auth_success',
            model: $user,
            oldValues: null,
            newValues: [
                'ip' => $request->ip(),
                'expires_at' => now()->addMinutes(config('developer.session_lifetime_minutes', 15))->toIso8601String(),
            ]
        );

        return true;
    }

    /**
     * Check if the current user request has an active, valid developer authorization session.
     */
    public function isAuthorized(Request $request): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        $sessionUntil = $request->session()->get(self::SESSION_AUTHORIZED_UNTIL);
        $sessionToken = $request->session()->get(self::SESSION_AUTH_TOKEN);

        if (empty($sessionUntil) || empty($sessionToken)) {
            return false;
        }

        if (now()->timestamp > (int) $sessionUntil) {
            $this->revokeSession($request);
            return false;
        }

        return true;
    }

    /**
     * Grant a short-lived developer authorization session.
     */
    public function authorizeSession(Request $request): void
    {
        $lifetimeMinutes = config('developer.session_lifetime_minutes', 15);
        $expiresAt = now()->addMinutes($lifetimeMinutes)->timestamp;
        $token = Str::random(40);

        $request->session()->put(self::SESSION_AUTHORIZED_UNTIL, $expiresAt);
        $request->session()->put(self::SESSION_AUTH_TOKEN, $token);
    }

    /**
     * Revoke developer authorization session.
     */
    public function revokeSession(Request $request): void
    {
        $hadSession = $request->session()->has(self::SESSION_AUTHORIZED_UNTIL);

        $request->session()->forget([
            self::SESSION_AUTHORIZED_UNTIL,
            self::SESSION_AUTH_TOKEN,
        ]);

        if ($hadSession) {
            $user = Auth::user();
            Log::info('Developer authorization revoked.', [
                'ip' => $request->ip(),
                'user_id' => $user?->id,
            ]);

            $this->activityLogService->log(
                event: 'developer_auth_revoked',
                model: $user,
                oldValues: null,
                newValues: ['ip' => $request->ip()]
            );
        }
    }

    /**
     * Get remaining authorization seconds for the current session.
     */
    public function getRemainingSeconds(Request $request): int
    {
        $sessionUntil = $request->session()->get(self::SESSION_AUTHORIZED_UNTIL);

        if (empty($sessionUntil)) {
            return 0;
        }

        $remaining = (int) $sessionUntil - now()->timestamp;
        return max(0, $remaining);
    }

    /**
     * Build rate limiter key unique to client IP and user ID.
     */
    protected function getRateLimitKey(Request $request): string
    {
        $userId = Auth::id() ?? 'guest';
        return 'developer_auth:' . $request->ip() . ':' . $userId;
    }
}

