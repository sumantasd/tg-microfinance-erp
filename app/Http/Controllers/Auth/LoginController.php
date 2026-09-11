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
        $loginField = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = User::where($loginField, $loginInput)->first();
        if (!$user && $loginField === 'username') {
            $user = User::where('email', $loginInput)->first();
        }

        // Check if user status is active
        if ($user && isset($user->status) && $user->status !== 'active') {
            return back()->withErrors([
                'email' => "Your staff account status is currently set to " . strtoupper($user->status) . ". Please contact your System Administrator.",
            ])->onlyInput('email');
        }

        $remember = $request->boolean('remember');

        $credentials = [
            'email' => $user?->email ?? $loginInput,
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
