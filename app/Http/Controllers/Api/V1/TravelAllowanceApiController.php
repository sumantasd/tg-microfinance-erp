<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TravelAllowanceClaim;
use App\Services\LocationTrackingService;
use App\Services\TravelAllowanceService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TravelAllowanceApiController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected TravelAllowanceService $taService,
        protected LocationTrackingService $locationTrackingService
    ) {}

    /**
     * Get staff's own TA claims.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $claims = TravelAllowanceClaim::where('user_id', $user->id)
            ->latest('travel_date')
            ->paginate($request->input('per_page', 20));

        return $this->successResponse([
            'claims' => $claims->items(),
            'current_page' => $claims->currentPage(),
            'last_page' => $claims->lastPage(),
            'total' => $claims->total(),
        ], 'Travel allowance claims retrieved');
    }

    /**
     * Submit new TA claim.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'travel_date' => 'required|date|before_or_equal:today',
            'from_location' => 'required|string|max:255',
            'to_location' => 'required|string|max:255',
            'transport_mode' => 'required|string|in:bike,car,bus,train,auto,other',
            'distance_km' => 'required|numeric|min:0.1',
            'rate_per_km' => 'nullable|numeric|min:0',
            'amount' => 'nullable|numeric|min:1',
            'purpose' => 'required|string|max:1000',
        ]);

        try {
            $claim = $this->taService->submitClaim($request->user(), $validated);

            return $this->successResponse([
                'claim_id' => $claim->id,
                'claim_number' => $claim->claim_number,
                'travel_date' => $claim->travel_date,
                'distance_km' => $claim->distance_km,
                'verified_distance_km' => $claim->verified_distance_km,
                'rate_per_km' => $claim->rate_per_km,
                'amount' => $claim->amount,
                'status' => $claim->status,
            ], 'Travel allowance claim submitted successfully', 201);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Get verified GPS distance for a given travel date to prepopulate claim form.
     */
    public function eligibleDistance(Request $request): JsonResponse
    {
        $date = $request->input('date', now()->toDateString());
        $verifiedDistanceKm = $this->locationTrackingService->calculateVerifiedDistanceKm($request->user()->id, $date);
        $policy = $this->taService->getPolicyConfig($request->user()->company_id);
        $calcBike = $this->taService->calculateClaimAmount($verifiedDistanceKm, 'bike', $request->user()->company_id);
        $calcCar = $this->taService->calculateClaimAmount($verifiedDistanceKm, 'car', $request->user()->company_id);

        return $this->successResponse([
            'date' => $date,
            'verified_distance_km' => $verifiedDistanceKm,
            'policy' => $policy,
            'suggested_claims' => [
                'bike' => $calcBike,
                'car' => $calcCar,
            ],
        ], 'Verified GPS distance and suggested TA calculation retrieved');
    }

    /**
     * Approve TA Claim.
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $claim = TravelAllowanceClaim::find($id);

        if (!$claim) {
            return $this->notFoundResponse('TA claim not found');
        }

        if (!$user->canAccessCompany($claim->company_id) || !$user->canAccessBranch($claim->branch_id)) {
            return $this->forbiddenResponse('Unauthorized access to approve claim in another branch');
        }

        try {
            $approved = $this->taService->approveClaim($claim, $user);

            return $this->successResponse($approved, 'Travel allowance claim approved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Reject TA Claim.
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $claim = TravelAllowanceClaim::find($id);

        if (!$claim) {
            return $this->notFoundResponse('TA claim not found');
        }

        if (!$user->canAccessCompany($claim->company_id) || !$user->canAccessBranch($claim->branch_id)) {
            return $this->forbiddenResponse('Unauthorized access to reject claim in another branch');
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        try {
            $rejected = $this->taService->rejectClaim($claim, $user, $validated['rejection_reason']);

            return $this->successResponse($rejected, 'Travel allowance claim rejected successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Pay approved TA Claim.
     */
    public function pay(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $claim = TravelAllowanceClaim::find($id);

        if (!$claim) {
            return $this->notFoundResponse('TA claim not found');
        }

        if (!$user->canAccessCompany($claim->company_id) || !$user->canAccessBranch($claim->branch_id)) {
            return $this->forbiddenResponse('Unauthorized access to pay claim in another branch');
        }

        $validated = $request->validate([
            'payment_method' => 'required|string|in:cash,bank,online',
            'remarks' => 'nullable|string|max:255',
        ]);

        try {
            $paid = $this->taService->payClaim($claim, $user, $validated['payment_method'], $validated['remarks'] ?? null);

            return $this->successResponse($paid, 'Travel allowance claim paid successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }
}

