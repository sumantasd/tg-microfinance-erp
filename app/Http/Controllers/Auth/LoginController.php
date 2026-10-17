<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * Display the staff login view.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }

        $branding = \App\Services\SystemBrandingService::getBranding();

        return view('auth.login', compact('branding'));
    }

    /**
     * Handle an incoming authentication request.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $loginInput = trim($request->input('email'));
        $user = null;

        if (filter_var($loginInput, FILTER_VALIDATE_EMAIL)) {
            // 1. Email authentication path
            $user = User::where('email', $loginInput)->first();
        } else {
            // 2. Reject Employee Code as login identifier
            // Employee codes (e.g. GLF-EMP-2026-0001 or any alphanumeric staff code with letters) are strictly forbidden
            if (preg_match('/[a-zA-Z]/', $loginInput)) {
                return back()->withErrors([
                    'email' => 'Employee codes and usernames cannot be used to log in. Please use your registered email address or mobile number.',
                ])->onlyInput('email');
            }

            // 3. Mobile Number authentication path
            $normalizedMobile = preg_replace('/[^\d+]/', '', $loginInput);
            $digitsOnly = preg_replace('/\D/', '', $loginInput);

            // Valid mobile number length (10 to 15 digits)
            if (strlen($digitsOnly) < 10 || strlen($digitsOnly) > 15) {
                return back()->withErrors([
                    'email' => 'Please provide a valid 10-digit registered mobile number or email address.',
                ])->onlyInput('email');
            }

            // Query matching user accounts by normalized or raw mobile number
            $matchedUsers = User::where(function ($query) use ($loginInput, $normalizedMobile, $digitsOnly) {
                $query->where('mobile_number', $loginInput)
                      ->orWhere('mobile_number', $normalizedMobile);
                if (strlen($digitsOnly) >= 10) {
                    $last10 = substr($digitsOnly, -10);
                    $query->orWhere('mobile_number', 'like', "%{$last10}");
                }
            })->get();

            // Prevent ambiguous or duplicate identifier matches from authenticating the wrong account
            if ($matchedUsers->count() > 1) {
                return back()->withErrors([
                    'email' => 'Multiple accounts found with this mobile number. Please log in using your registered email address.',
                ])->onlyInput('email');
            }

            $user = $matchedUsers->first();
        }

        // Check if user was found
        if (!$user) {
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        // Check if user status is active
        if (isset($user->status) && $user->status !== 'active') {
            return back()->withErrors([
                'email' => "Your staff account status is currently set to " . strtoupper($user->status) . ". Please contact your System Administrator.",
            ])->onlyInput('email');
        }

        $remember = $request->boolean('remember');

        $credentials = [
            'email' => $user->email,
            'password' => $request->password,
        ];

        if (Auth::attempt($credentials, $remember)) {
            /** @var User $authenticatedUser */
            $authenticatedUser = Auth::user();
            if (isset($authenticatedUser->last_login_at)) {
                $authenticatedUser->update([
                    'last_login_at' => now(),
                    'last_login_ip' => $request->ip(),
                ]);
            }

            $request->session()->regenerate();

            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
