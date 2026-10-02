<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerKycDocument;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class KycApiController extends Controller
{
    use ApiResponse;

    /**
     * Upload customer KYC document.
     */
    public function upload(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'document_type' => 'required|string|max:50',
            'document_number' => 'nullable|string|max:100',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB limit
        ]);

        $customer = Customer::find($validated['customer_id']);

        if (!$customer) {
            return $this->notFoundResponse('Customer not found');
        }

        if (!$user->canAccessCompany($customer->company_id) || !$user->canAccessBranch($customer->branch_id)) {
            return $this->forbiddenResponse('Unauthorized access to upload KYC document for another branch customer');
        }

        $file = $request->file('file');
        $filePath = $file->store("kyc_documents/customer_{$customer->id}", 'local');

        $kycDoc = CustomerKycDocument::create([
            'customer_id' => $customer->id,
            'kyc_document_type' => $validated['document_type'],
            'document_number' => $validated['document_number'] ?? null,
            'file_path' => $filePath,
            'file_name' => $file->getClientOriginalName(),
            'file_size_kb' => round($file->getSize() / 1024, 2),
            'verification_status' => 'pending',
            'created_by' => $user->id,
        ]);

        return $this->successResponse([
            'document_id' => $kycDoc->id,
            'customer_id' => $customer->id,
            'document_type' => $kycDoc->kyc_document_type,
            'document_number' => $kycDoc->document_number,
            'original_filename' => $kycDoc->file_name,
            'status' => $kycDoc->verification_status,
        ], 'KYC document uploaded successfully', 201);
    }

    /**
     * Download or stream KYC document file.
     */
    public function download(Request $request, int $id)
    {
        $user = $request->user();
        $kycDoc = CustomerKycDocument::with('customer')->find($id);

        if (!$kycDoc) {
            return $this->notFoundResponse('KYC document not found');
        }

        if (!$user->canAccessCompany($kycDoc->customer?->company_id) || !$user->canAccessBranch($kycDoc->customer?->branch_id)) {
            return $this->forbiddenResponse('Unauthorized access to document file');
        }

        if (!Storage::disk('local')->exists($kycDoc->file_path)) {
            return $this->notFoundResponse('File not found on server storage');
        }

        return Storage::disk('local')->download($kycDoc->file_path, $kycDoc->file_name);
    }

    /**
     * Verify or reject customer KYC document.
     */
    public function verify(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $kycDoc = CustomerKycDocument::with('customer')->find($id);

        if (!$kycDoc) {
            return $this->notFoundResponse('KYC document not found');
        }

        if (!$user->canAccessCompany($kycDoc->customer?->company_id) || !$user->canAccessBranch($kycDoc->customer?->branch_id)) {
            return $this->forbiddenResponse('Unauthorized access to verify document for another branch');
        }

        $validated = $request->validate([
            'status' => 'required|in:verified,rejected',
            'rejection_reason' => 'nullable|required_if:status,rejected|string|max:255',
            'remarks' => 'nullable|string|max:255',
        ]);

        $kycDoc->update([
            'verification_status' => $validated['status'],
            'verified_by' => $user->id,
            'verified_at' => now(),
            'rejection_reason' => $validated['rejection_reason'] ?? null,
            'remarks' => $validated['remarks'] ?? $kycDoc->remarks,
        ]);

        return $this->successResponse($kycDoc->fresh(), 'KYC document verification status updated');
    }

    /**
     * Delete customer KYC document.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $kycDoc = CustomerKycDocument::with('customer')->find($id);

        if (!$kycDoc) {
            return $this->notFoundResponse('KYC document not found');
        }

        if (!$user->canAccessCompany($kycDoc->customer?->company_id) || !$user->canAccessBranch($kycDoc->customer?->branch_id)) {
            return $this->forbiddenResponse('Unauthorized access to delete document for another branch');
        }

        if ($kycDoc->file_path && Storage::disk('local')->exists($kycDoc->file_path)) {
            Storage::disk('local')->delete($kycDoc->file_path);
        }

        $kycDoc->delete();

        return $this->successResponse(null, 'KYC document deleted successfully');
    }
}

