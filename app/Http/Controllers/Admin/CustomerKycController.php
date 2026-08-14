<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VerifyKycRequest;
use App\Models\Customer;
use App\Models\CustomerKycDocument;
use App\Services\CustomerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerKycController extends Controller
{
    public function __construct(protected CustomerService $customerService) {}

    public function store(Request $request, Customer $customer): RedirectResponse
    {
        $request->validate([
            'kyc_document_type' => 'required|string|max:50',
            'document_number' => 'required|string|max:50',
            'file' => 'required|file|mimes:pdf,jpeg,png,jpg|max:5120',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'remarks' => 'nullable|string',
        ]);

        $this->customerService->addKycDocument($customer, $request->only([
            'kyc_document_type', 'document_number', 'issue_date', 'expiry_date', 'remarks'
        ]), $request->file('file'));

        return redirect()->back()->with('success', 'KYC Document uploaded successfully.');
    }

    public function download(CustomerKycDocument $kyc): StreamedResponse|RedirectResponse
    {
        if (!Storage::disk('private')->exists($kyc->file_path)) {
            return redirect()->back()->with('error', 'File not found on storage server.');
        }

        return Storage::disk('private')->download($kyc->file_path, $kyc->file_name);
    }

    public function verify(VerifyKycRequest $request, CustomerKycDocument $kyc): RedirectResponse
    {
        $data = $request->validated();
        $this->customerService->verifyKycDocument(
            $kyc,
            Auth::id(),
            $data['verification_status'],
            $data['rejection_reason'] ?? null,
            $data['remarks'] ?? null
        );

        $statusText = ucfirst($data['verification_status']);
        return redirect()->back()->with('success', "KYC Document status updated to '{$statusText}'.");
    }

    public function destroy(CustomerKycDocument $kyc): RedirectResponse
    {
        $this->customerService->deleteKycDocument($kyc);
        return redirect()->back()->with('success', 'KYC Document deleted successfully.');
    }
}
