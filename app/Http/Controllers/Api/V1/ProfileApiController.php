<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Employee;
use App\Models\Media;
use App\Services\SystemBrandingService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileApiController extends Controller
{
    use ApiResponse;

    /**
     * Get authenticated user profile with roles, permissions, employee & branch scope.
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user()->load(['company', 'branch']);
        $employee = Employee::with(['department', 'designation'])->where('user_id', $user->id)->first();

        $data = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar_url' => $user->avatar ? asset('storage/' . $user->avatar) : null,
                'status' => $user->status,
                'company' => $user->company,
                'branch' => $user->branch,
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ],
            'employee' => $employee,
        ];

        return $this->successResponse($data, 'Profile retrieved successfully');
    }

    /**
     * Update user profile information.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $user->update($validated);

        return $this->successResponse($user->fresh(['company', 'branch']), 'Profile updated successfully');
    }

    /**
     * Change user password.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return $this->errorResponse('Current password does not match.', 422);
        }

        $user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        return $this->successResponse(null, 'Password updated successfully');
    }

    /**
     * Get mobile app configuration and branding settings.
     */
    public function appConfig(): JsonResponse
    {
        $branding = SystemBrandingService::getBranding();

        $config = [
            'app_name' => $branding->system_name,
            'company_name' => $branding->company_name,
            'legal_name' => $branding->legal_name,
            'tagline' => $branding->tagline,
            'email' => $branding->email,
            'phone' => $branding->phone,
            'website' => $branding->website,
            'address' => $branding->full_address,
            'logo_url' => $branding->logo_url,
            'logo_icon_url' => $branding->logo_icon_url,
            'currency_symbol' => '₹',
            'currency_code' => 'INR',
            'version' => '1.0.0',
        ];

        return $this->successResponse($config, 'App configuration retrieved successfully');
    }

    /**
     * Get user-scoped activity logs.
     */
    public function auditLogs(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!class_exists(ActivityLog::class)) {
            return $this->successResponse([], 'Activity logs model not present');
        }

        $query = ActivityLog::with('user');

        if (!$user->isSuperAdmin()) {
            if ($user->isCompanyAdmin()) {
                $query->where('company_id', $user->company_id);
            } elseif ($user->branch_id) {
                $query->where('branch_id', $user->branch_id);
            } else {
                $query->where('user_id', $user->id);
            }
        }

        $logs = $query->latest()->paginate($request->input('per_page', 20));

        return $this->successResponse($logs, 'Audit logs retrieved successfully');
    }

    /**
     * Media library listing.
     */
    public function media(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!class_exists(Media::class)) {
            return $this->successResponse([], 'Media model not present');
        }

        $query = Media::query();

        if (!$user->isSuperAdmin() && $user->company_id) {
            $query->where('company_id', $user->company_id);
        }

        $mediaList = $query->latest()->paginate($request->input('per_page', 20));

        return $this->successResponse($mediaList, 'Media list retrieved successfully');
    }
}
