<?php

namespace App\Http\Middleware;

use App\Services\DeveloperAuthService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureDeveloperAuthorized
{
    public function __construct(
        protected DeveloperAuthService $developerAuthService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // 1. Must be authenticated
        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('admin.login');
        }

        // 2. Must be Super Admin
        if (!$user->hasRole('Super Admin')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Forbidden: Only Super Administrators can access Developer Data Management.',
                ], 403);
            }
            abort(403, 'Forbidden: Only Super Administrators can access Developer Data Management.');
        }

        // 3. Must satisfy Developer Secret Authorization
        if (!$this->developerAuthService->isAuthorized($request)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Developer authorization required or expired. Please enter the developer passphrase.',
                    'code' => 'DEVELOPER_AUTH_REQUIRED',
                ], 403);
            }

            return redirect()
                ->route('admin.system.developer-data.index')
                ->with('error', 'Developer authorization required or session expired. Please re-enter your developer passphrase.');
        }

        return $next($request);
    }
}

