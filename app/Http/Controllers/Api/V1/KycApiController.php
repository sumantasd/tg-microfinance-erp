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

        if (!$user->canAccessBranch($customer->branch_id)) {
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

        if (!$user->canAccessBranch($kycDoc->customer?->branch_id)) {
            return $this->forbiddenResponse('Unauthorized access to document file');
        }

        if (!Storage::disk('local')->exists($kycDoc->file_path)) {
            return $this->notFoundResponse('File not found on server storage');
        }

        return Storage::disk('local')->download($kycDoc->file_path, $kycDoc->file_name);
    }
}
