<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGuarantorRequest;
use App\Models\Customer;
use App\Models\CustomerGuarantor;
use App\Services\CustomerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerGuarantorController extends Controller
{
    public function __construct(protected CustomerService $customerService) {}

    public function store(StoreGuarantorRequest $request, Customer $customer): RedirectResponse
    {
        $data = $request->validated();
        $kycFile = $request->file('kyc_file');

        $this->customerService->addOrUpdateGuarantor($customer, $data, $kycFile);

        $action = !empty($data['id']) ? 'updated' : 'added';
        return redirect()->back()->with('success', "Guarantor {$action} successfully.");
    }

    public function downloadKyc(CustomerGuarantor $guarantor): StreamedResponse|RedirectResponse
    {
        if (!$guarantor->kyc_document_path || !Storage::disk('private')->exists($guarantor->kyc_document_path)) {
            return redirect()->back()->with('error', 'Guarantor document not found on storage server.');
        }

        return Storage::disk('private')->download($guarantor->kyc_document_path);
    }

    public function destroy(CustomerGuarantor $guarantor): RedirectResponse
    {
        $this->customerService->deleteGuarantor($guarantor);
        return redirect()->back()->with('success', 'Guarantor removed successfully.');
    }
}
