<?php

namespace App\Services;

use App\Models\CashBookEntry;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\TravelAllowanceClaim;
use App\Models\User;
use App\Models\WebsiteSetting;
use Carbon\Carbon;
use InvalidArgumentException;
use RuntimeException;

class TravelAllowanceService
{
    public function __construct(
        protected LocationTrackingService $locationTrackingService,
        protected ActivityLogService $activityLogService
    ) {}

    /**
     * Get configurable per-km rates & limits for a company.
     */
    public function getPolicyConfig(?int $companyId = null): array
    {
        $websiteSetting = WebsiteSetting::first();

        // Defaults or configured rates
        $rateBike = $websiteSetting->ta_rate_per_km_bike ?? 4.00;
        $rateCar = $websiteSetting->ta_rate_per_km_car ?? 8.00;
        $rateDefault = $websiteSetting->ta_rate_per_km ?? 4.00;
        $maxDailyLimit = $websiteSetting->ta_max_daily_limit ?? 600.00;
        $minDistanceKm = $websiteSetting->ta_min_distance_km ?? 2.00;

        return [
            'rate_bike' => (float) $rateBike,
            'rate_car' => (float) $rateCar,
            'rate_default' => (float) $rateDefault,
            'max_daily_limit' => (float) $maxDailyLimit,
            'min_distance_km' => (float) $minDistanceKm,
        ];
    }

    /**
     * Calculate claim amount given distance, transport mode, and company.
     */
    public function calculateClaimAmount(float $distanceKm, string $transportMode = 'bike', ?int $companyId = null): array
    {
        $policy = $this->getPolicyConfig($companyId);
        $rate = match (strtolower($transportMode)) {
            'car' => $policy['rate_car'],
            'bike' => $policy['rate_bike'],
            default => $policy['rate_default'],
        };

        $rawAmount = round($distanceKm * $rate, 2);
        $cappedAmount = min($rawAmount, $policy['max_daily_limit']);
        $isCapped = $rawAmount > $policy['max_daily_limit'];

        return [
            'distance_km' => $distanceKm,
            'rate_per_km' => $rate,
            'calculated_amount' => $rawAmount,
            'final_amount' => $cappedAmount,
            'is_capped' => $isCapped,
            'max_daily_limit' => $policy['max_daily_limit'],
        ];
    }

    /**
     * Submit TA claim with duplicate check and verified GPS evidence.
     */
    public function submitClaim(User $user, array $data): TravelAllowanceClaim
    {
        $travelDate = $data['travel_date'] ?? now()->toDateString();
        $employee = Employee::where('user_id', $user->id)->first();

        // Duplicate check: Prevent multiple claims for the same user on the same date
        $existingClaim = TravelAllowanceClaim::where('user_id', $user->id)
            ->whereDate('travel_date', $travelDate)
            ->whereIn('status', ['pending', 'approved', 'paid'])
            ->first();

        if ($existingClaim) {
            throw new InvalidArgumentException("A travel allowance claim already exists for date {$travelDate} (Claim: {$existingClaim->claim_number}).");
        }

        // Calculate verified distance from GPS logs
        $verifiedDistanceKm = $this->locationTrackingService->calculateVerifiedDistanceKm($user->id, $travelDate);
        $claimedDistanceKm = (float) ($data['distance_km'] ?? $verifiedDistanceKm);
        $transportMode = $data['transport_mode'] ?? 'bike';

        $calc = $this->calculateClaimAmount($claimedDistanceKm, $transportMode, $user->company_id);
        $ratePerKm = (float) ($data['rate_per_km'] ?? $calc['rate_per_km']);
        $amount = (float) ($data['amount'] ?? $calc['final_amount']);

        $claimNumber = 'TA-' . Carbon::parse($travelDate)->format('Ymd') . '-' . sprintf('%04d', rand(1, 9999));

        $claim = TravelAllowanceClaim::create([
            'company_id' => $user->company_id ?? 1,
            'branch_id' => $user->branch_id ?? 1,
            'user_id' => $user->id,
            'employee_id' => $employee?->id,
            'claim_number' => $claimNumber,
            'travel_date' => $travelDate,
            'from_location' => $data['from_location'] ?? 'Branch Office',
            'to_location' => $data['to_location'] ?? 'Field Visit',
            'transport_mode' => $transportMode,
            'distance_km' => $claimedDistanceKm,
            'verified_distance_km' => $verifiedDistanceKm,
            'rate_per_km' => $ratePerKm,
            'amount' => $amount,
            'purpose' => $data['purpose'] ?? 'Daily Field Loan Collection & Customer Verification',
            'status' => 'pending',
        ]);

        $this->activityLogService->log('ta_claim_submitted', $claim, null, $claim->toArray());

        return $claim;
    }

    /**
     * Approve TA claim.
     */
    public function approveClaim(TravelAllowanceClaim $claim, User $approver): TravelAllowanceClaim
    {
        if ($claim->status !== 'pending') {
            throw new RuntimeException("Only pending claims can be approved.");
        }

        $claim->update([
            'status' => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        $this->activityLogService->log('ta_claim_approved', $claim, null, $claim->toArray());

        return $claim;
    }

    /**
     * Reject TA claim.
     */
    public function rejectClaim(TravelAllowanceClaim $claim, User $approver, string $reason): TravelAllowanceClaim
    {
        if ($claim->status !== 'pending') {
            throw new RuntimeException("Only pending claims can be rejected.");
        }

        $claim->update([
            'status' => 'rejected',
            'approved_by' => $approver->id,
            'rejection_reason' => $reason,
        ]);

        $this->activityLogService->log('ta_claim_rejected', $claim, null, $claim->toArray());

        return $claim;
    }

    /**
     * Mark TA claim as paid & record Cash Book / Expense voucher entry.
     */
    public function payClaim(
        TravelAllowanceClaim $claim,
        User $payer,
        string $paymentReference,
        string $paymentMethod = 'cash'
    ): TravelAllowanceClaim {
        if ($claim->status !== 'approved') {
            throw new RuntimeException("Only approved claims can be marked as paid.");
        }

        // Duplicate payment check
        if ($claim->status === 'paid' || $claim->paid_at !== null) {
            throw new RuntimeException("This travel allowance claim has already been paid.");
        }

        $cashBookEntryId = null;

        // Post cash entry if paid
        if (class_exists(CashBookService::class)) {
            $today = now()->toDateString();
            $cashBookService = app(CashBookService::class);
            $cashBook = $cashBookService->getOrCreateCashBook(
                $claim->company_id,
                $claim->branch_id,
                $today,
                $payer->id
            );

            $isCash = strtolower($paymentMethod) === 'cash';

            $entry = CashBookEntry::create([
                'cash_book_id' => $cashBook->id,
                'entry_type' => 'payment',
                'category_code' => 'management_expense',
                'particulars' => "TA Reimbursement for {$claim->user->name} ({$claim->claim_number})",
                'entry_date' => $today,
                'cash_amount' => $isCash ? $claim->amount : 0.00,
                'product_amount' => 0.00,
                'bank_amount' => !$isCash ? $claim->amount : 0.00,
                'reference_type' => TravelAllowanceClaim::class,
                'reference_id' => $claim->id,
                'remarks' => "TA Reimbursement Ref: {$paymentReference}",
                'created_by' => $payer->id,
            ]);
            $cashBookEntryId = $entry->id;
            $cashBookService->recalculateTotals($cashBook);
        }

        $claim->update([
            'status' => 'paid',
            'paid_at' => now(),
            'payment_reference' => $paymentReference,
            'cash_book_entry_id' => $cashBookEntryId,
        ]);

        $this->activityLogService->log('ta_claim_paid', $claim, null, $claim->toArray());

        return $claim;
    }
}
