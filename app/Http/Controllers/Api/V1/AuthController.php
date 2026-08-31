<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Mobile login endpoint.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
            'device_name' => 'nullable|string|max:255',
        ]);

        $loginInput = $credentials['email'];

        // Allow login by email or mobile number or employee_id
        $user = User::where('email', $loginInput)
            ->orWhere('mobile_number', $loginInput)
            ->orWhere('employee_id', $loginInput)
            ->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return $this->errorResponse('Invalid login credentials', 401);
        }

        if (!$user->isActive()) {
            return $this->errorResponse('Your user account is inactive. Please contact system administrator.', 403);
        }

        // Generate Sanctum access token
        $deviceName = $credentials['device_name'] ?? 'Mobile App';
        $token = $user->createToken($deviceName)->plainTextToken;

        // Update login metadata
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        return $this->successResponse([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'mobile_number' => $user->mobile_number,
                'employee_id' => $user->employee_id,
                'company_id' => $user->company_id,
                'branch_id' => $user->branch_id,
                'branch_name' => $user->branch?->name,
                'status' => $user->status,
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ],
        ], 'Authenticated successfully');
    }

    /**
     * Logout & revoke current token.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user) {
            if ($user->currentAccessToken()) {
                $user->currentAccessToken()->delete();
            } else {
                $user->tokens()->delete();
            }
        }

        return $this->successResponse(null, 'Logged out successfully');
    }

    /**
     * Get authenticated user profile.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->successResponse([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'mobile_number' => $user->mobile_number,
            'employee_id' => $user->employee_id,
            'company_id' => $user->company_id,
            'company_name' => $user->company?->name,
            'branch_id' => $user->branch_id,
            'branch_name' => $user->branch?->name,
            'status' => $user->status,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ], 'Authenticated user profile retrieved');
    }
}
