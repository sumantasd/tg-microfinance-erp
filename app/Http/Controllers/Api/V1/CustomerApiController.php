<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\CustomerService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerApiController extends Controller
{
    use ApiResponse;

    protected CustomerService $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    /**
     * Customer listing with search and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $scopedBranchId = $user->resolveScopedBranchId($request->query('branch_id'));

        $query = Customer::with(['branch', 'company', 'addresses', 'presentAddress', 'permanentAddress', 'kycDocuments', 'groupMemberships.group']);

        if ($scopedBranchId) {
            $query->where('branch_id', $scopedBranchId);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('mobile_number', 'like', "%{$search}%")
                  ->orWhere('customer_code', 'like', "%{$search}%")
                  ->orWhere('aadhaar_number', 'like', "%{$search}%");
            });
        }

        $perPage = min((int) ($request->query('per_page', 15)), 100);
        $customers = $query->latest()->paginate($perPage);

        return $this->successResponse($customers, 'Customers list retrieved');
    }

    /**
     * Show single customer profile.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $customer = Customer::with([
            'branch',
            'company',
            'addresses',
            'presentAddress',
            'permanentAddress',
            'kycDocuments',
            'guarantors',
            'nominees',
            'groupMemberships.group',
            'loanAccounts',
        ])->find($id);

        if (!$customer) {
            return $this->notFoundResponse('Customer not found');
        }

        if (!$user->canAccessCompany($customer->company_id) || !$user->canAccessBranch($customer->branch_id)) {
            return $this->forbiddenResponse('You are not authorized to view customer from another branch');
        }

        return $this->successResponse($customer, 'Customer profile retrieved');
    }

    /**
     * Store new customer profile via CustomerService.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($request->has('mobile') && !$request->has('mobile_number')) {
            $request->merge(['mobile_number' => $request->input('mobile')]);
        }
        if ($request->has('gender')) {
            $request->merge(['gender' => strtolower((string) $request->input('gender'))]);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'father_husband_name' => 'nullable|string|max:255',
            'mobile_number' => 'required|string|max:20|unique:customers,mobile_number',
            'email' => 'nullable|email|max:255',
            'date_of_birth' => 'nullable|date',
            'dob' => 'nullable|date',
            'gender' => 'required|in:male,female,other',
            'marital_status' => 'nullable|in:single,married,widowed,divorced',
            'address' => 'nullable|string',
            'address_line' => 'nullable|string',
            'present_address' => 'nullable|string',
            'village' => 'nullable|string',
            'village_area' => 'nullable|string',
            'post_office' => 'nullable|string',
            'police_station' => 'nullable|string',
            'district' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:20',
            'pin_code' => 'nullable|string|max:20',
            'permanent_address' => 'nullable|string',
            'permanent_address_line' => 'nullable|string',
            'permanent_village' => 'nullable|string',
            'permanent_post_office' => 'nullable|string',
            'permanent_police_station' => 'nullable|string',
            'permanent_district' => 'nullable|string|max:100',
            'permanent_state' => 'nullable|string|max:100',
            'permanent_pincode' => 'nullable|string|max:20',
            'branch_id' => 'required|exists:branches,id',
            'aadhaar_number' => 'nullable|string|max:20',
            'pan_number' => 'nullable|string|max:20',
        ]);

        if (empty($validated['name']) && empty($validated['first_name'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'name' => 'The name or first_name field is required.',
            ]);
        }

        if (!$user->canAccessBranch($validated['branch_id'])) {
            return $this->forbiddenResponse('Cannot create customer in unauthorized branch');
        }

        if (!empty($validated['name'])) {
            $parts = explode(' ', trim($validated['name']), 2);
            $validated['first_name'] = $validated['first_name'] ?? $parts[0];
            $validated['last_name'] = $validated['last_name'] ?? ($parts[1] ?? '.');
        } else {
            $validated['last_name'] = $validated['last_name'] ?? '.';
        }

        if (!empty($validated['father_husband_name'])) {
            $validated['father_husband_guardian_name'] = $validated['father_husband_name'];
        }

        if (!empty($validated['date_of_birth'])) {
            $validated['dob'] = $validated['date_of_birth'];
        }

        $validated['created_by'] = $user->id;
        $validated['company_id'] = $user->resolveScopedCompanyId($request->input('company_id'));
        $validated['registration_date'] = now()->toDateString();
        $validated['customer_type'] = $validated['customer_type'] ?? 'individual';

        $addresses = $this->parseAddressesFromRequest($request);

        $customer = $this->customerService->createCustomer($validated, null, $addresses);

        return $this->successResponse(
            $customer->load(['branch', 'company', 'addresses', 'presentAddress', 'permanentAddress', 'kycDocuments', 'guarantors', 'nominees', 'groupMemberships.group', 'loanAccounts']),
            'Customer created successfully',
            201
        );
    }

    /**
     * Update existing customer profile.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $customer = Customer::find($id);

        if (!$customer) {
            return $this->notFoundResponse('Customer not found');
        }

        if (!$user->canAccessCompany($customer->company_id) || !$user->canAccessBranch($customer->branch_id)) {
            return $this->forbiddenResponse('You are not authorized to update customer from another branch or company');
        }

        if ($request->has('mobile') && !$request->has('mobile_number')) {
            $request->merge(['mobile_number' => $request->input('mobile')]);
        }
        if ($request->has('gender')) {
            $request->merge(['gender' => strtolower((string) $request->input('gender'))]);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'father_husband_name' => 'nullable|string|max:255',
            'mobile_number' => 'nullable|string|max:20|unique:customers,mobile_number,' . $customer->id,
            'email' => 'nullable|email|max:255',
            'date_of_birth' => 'nullable|date',
            'dob' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'marital_status' => 'nullable|in:single,married,widowed,divorced',
            'address' => 'nullable|string',
            'address_line' => 'nullable|string',
            'present_address' => 'nullable|string',
            'village' => 'nullable|string',
            'village_area' => 'nullable|string',
            'post_office' => 'nullable|string',
            'police_station' => 'nullable|string',
            'district' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:20',
            'pin_code' => 'nullable|string|max:20',
            'permanent_address' => 'nullable|string',
            'permanent_address_line' => 'nullable|string',
            'permanent_village' => 'nullable|string',
            'permanent_post_office' => 'nullable|string',
            'permanent_police_station' => 'nullable|string',
            'permanent_district' => 'nullable|string|max:100',
            'permanent_state' => 'nullable|string|max:100',
            'permanent_pincode' => 'nullable|string|max:20',
            'branch_id' => 'nullable|exists:branches,id',
            'aadhaar_number' => 'nullable|string|max:20',
            'pan_number' => 'nullable|string|max:20',
        ]);

        if (!empty($validated['branch_id']) && !$user->canAccessBranch($validated['branch_id'])) {
            return $this->forbiddenResponse('Cannot move customer to unauthorized branch');
        }

        if (!empty($validated['name'])) {
            $parts = explode(' ', trim($validated['name']), 2);
            $validated['first_name'] = $validated['first_name'] ?? $parts[0];
            $validated['last_name'] = $validated['last_name'] ?? ($parts[1] ?? '.');
        }

        if (!empty($validated['father_husband_name'])) {
            $validated['father_husband_guardian_name'] = $validated['father_husband_name'];
        }

        if (!empty($validated['date_of_birth'])) {
            $validated['dob'] = $validated['date_of_birth'];
        }

        $photo = $request->hasFile('photo') ? $request->file('photo') : null;

        $addresses = $this->parseAddressesFromRequest($request);

        $updatedCustomer = $this->customerService->updateCustomer($customer, array_filter($validated, fn($v) => !is_null($v)), $photo, $addresses);

        return $this->successResponse(
            $updatedCustomer->load(['branch', 'company', 'addresses', 'presentAddress', 'permanentAddress', 'kycDocuments', 'guarantors', 'nominees', 'groupMemberships.group', 'loanAccounts']),
            'Customer profile updated successfully'
        );
    }

    /**
     * Parse present and permanent address structures from incoming API request.
     */
    private function parseAddressesFromRequest(Request $request): array
    {
        $rawAddresses = $request->input('addresses');
        $addresses = [];

        if (is_array($rawAddresses)) {
            foreach ($rawAddresses as $key => $val) {
                if (is_array($val)) {
                    $type = $val['address_type'] ?? (is_string($key) ? $key : null);
                    if ($type && !empty($val['address_line'] ?? $val['address'] ?? null)) {
                        $addresses[$type] = [
                            'address_line' => $val['address_line'] ?? $val['address'] ?? '',
                            'village_area' => $val['village_area'] ?? $val['village'] ?? null,
                            'post_office' => $val['post_office'] ?? null,
                            'police_station' => $val['police_station'] ?? null,
                            'district' => $val['district'] ?? $val['city'] ?? '',
                            'state' => $val['state'] ?? '',
                            'pin_code' => $val['pin_code'] ?? $val['pincode'] ?? '',
                        ];
                    }
                }
            }
        }

        if (!isset($addresses['present'])) {
            $presentLine = $request->input('present_address') ?? $request->input('address') ?? $request->input('address_line');
            if (!empty($presentLine)) {
                $addresses['present'] = [
                    'address_line' => $presentLine,
                    'village_area' => $request->input('village_area') ?? $request->input('village'),
                    'post_office' => $request->input('post_office'),
                    'police_station' => $request->input('police_station'),
                    'district' => $request->input('district') ?? $request->input('city') ?? '',
                    'state' => $request->input('state') ?? '',
                    'pin_code' => $request->input('pin_code') ?? $request->input('pincode') ?? '',
                ];
            }
        }

        if (!isset($addresses['permanent'])) {
            $permLine = $request->input('permanent_address') ?? $request->input('permanent_address_line');
            if (!empty($permLine)) {
                $addresses['permanent'] = [
                    'address_line' => $permLine,
                    'village_area' => $request->input('permanent_village_area') ?? $request->input('permanent_village'),
                    'post_office' => $request->input('permanent_post_office'),
                    'police_station' => $request->input('permanent_police_station'),
                    'district' => $request->input('permanent_district') ?? $request->input('permanent_city') ?? '',
                    'state' => $request->input('permanent_state') ?? '',
                    'pin_code' => $request->input('permanent_pin_code') ?? $request->input('permanent_pincode') ?? '',
                ];
            }
        }

        return $addresses;
    }

    /**
     * Toggle customer active/inactive status.
     */
    public function toggleStatus(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $customer = Customer::find($id);

        if (!$customer) {
            return $this->notFoundResponse('Customer not found');
        }

        if (!$user->canAccessCompany($customer->company_id) || !$user->canAccessBranch($customer->branch_id)) {
            return $this->forbiddenResponse('Unauthorized access to change customer status');
        }

        $newStatus = $customer->status === 'active' ? 'inactive' : 'active';
        $customer->update(['status' => $newStatus]);

        return $this->successResponse($customer->fresh(), 'Customer status updated successfully');
    }

    /**
     * Soft delete customer record.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $customer = Customer::find($id);

        if (!$customer) {
            return $this->notFoundResponse('Customer not found');
        }

        if (!$user->canAccessCompany($customer->company_id) || !$user->canAccessBranch($customer->branch_id)) {
            return $this->forbiddenResponse('Unauthorized access to delete customer');
        }

        $customer->delete();

        return $this->successResponse(null, 'Customer deleted successfully');
    }

    /**
     * Restore soft-deleted customer record.
     */
    public function restore(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $customer = Customer::withTrashed()->find($id);

        if (!$customer) {
            return $this->notFoundResponse('Customer not found');
        }

        if (!$user->canAccessCompany($customer->company_id) || !$user->canAccessBranch($customer->branch_id)) {
            return $this->forbiddenResponse('Unauthorized access to restore customer');
        }

        $customer->restore();

        return $this->successResponse($customer->fresh(), 'Customer restored successfully');
    }

    /**
     * Get customer guarantors.
     */
    public function guarantors(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $customer = Customer::with('guarantors')->find($id);

        if (!$customer) {
            return $this->notFoundResponse('Customer not found');
        }

        if (!$user->canAccessCompany($customer->company_id) || !$user->canAccessBranch($customer->branch_id)) {
            return $this->forbiddenResponse('Unauthorized access to customer guarantors');
        }

        return $this->successResponse($customer->guarantors, 'Customer guarantors retrieved');
    }

    /**
     * Add or update customer guarantor.
     */
    public function storeGuarantor(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $customer = Customer::find($id);

        if (!$customer) {
            return $this->notFoundResponse('Customer not found');
        }

        if (!$user->canAccessCompany($customer->company_id) || !$user->canAccessBranch($customer->branch_id)) {
            return $this->forbiddenResponse('Unauthorized access to add guarantor for customer');
        }

        $validated = $request->validate([
            'id' => 'nullable|exists:customer_guarantors,id',
            'full_name' => 'required|string|max:255',
            'relationship' => 'required|string|max:100',
            'mobile' => 'required|string|max:20',
            'alternate_contact' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'occupation' => 'nullable|string|max:100',
            'monthly_income' => 'nullable|numeric|min:0',
            'kyc_type' => 'nullable|string|max:50',
            'kyc_number' => 'nullable|string|max:100',
            'remarks' => 'nullable|string',
            'kyc_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $validated['address'] = $validated['address'] ?? '';
        $kycFile = $request->hasFile('kyc_file') ? $request->file('kyc_file') : null;
        $guarantor = $this->customerService->addOrUpdateGuarantor($customer, $validated, $kycFile);

        return $this->successResponse($guarantor, 'Guarantor saved successfully', 201);
    }

    /**
     * Delete customer guarantor.
     */
    public function destroyGuarantor(Request $request, int $id, int $guarantorId): JsonResponse
    {
        $user = $request->user();
        $customer = Customer::find($id);

        if (!$customer) {
            return $this->notFoundResponse('Customer not found');
        }

        if (!$user->canAccessCompany($customer->company_id) || !$user->canAccessBranch($customer->branch_id)) {
            return $this->forbiddenResponse('Unauthorized access to remove guarantor');
        }

        $guarantor = \App\Models\CustomerGuarantor::where('customer_id', $customer->id)->find($guarantorId);
        if (!$guarantor) {
            return $this->notFoundResponse('Guarantor record not found');
        }

        $guarantor->delete();

        return $this->successResponse(null, 'Guarantor deleted successfully');
    }

    /**
     * Get customer nominees.
     */
    public function nominees(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $customer = Customer::with('nominees')->find($id);

        if (!$customer) {
            return $this->notFoundResponse('Customer not found');
        }

        if (!$user->canAccessCompany($customer->company_id) || !$user->canAccessBranch($customer->branch_id)) {
            return $this->forbiddenResponse('Unauthorized access to customer nominees');
        }

        return $this->successResponse($customer->nominees, 'Customer nominees retrieved');
    }

    /**
     * Add or update customer nominee.
     */
    public function storeNominee(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $customer = Customer::find($id);

        if (!$customer) {
            return $this->notFoundResponse('Customer not found');
        }

        if (!$user->canAccessCompany($customer->company_id) || !$user->canAccessBranch($customer->branch_id)) {
            return $this->forbiddenResponse('Unauthorized access to add nominee for customer');
        }

        $validated = $request->validate([
            'id' => 'nullable|exists:customer_nominees,id',
            'nominee_name' => 'required|string|max:255',
            'relationship' => 'required|string|max:100',
            'dob' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'mobile' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'share_percentage' => 'nullable|numeric|min:1|max:100',
            'is_minor' => 'nullable|boolean',
            'guardian_name' => 'nullable|string|max:255',
            'guardian_relationship' => 'nullable|string|max:100',
            'guardian_contact' => 'nullable|string|max:20',
            'guardian_address' => 'nullable|string',
        ]);

        $nominee = $this->customerService->addOrUpdateNominee($customer, $validated);

        return $this->successResponse($nominee, 'Nominee saved successfully', 201);
    }

    /**
     * Delete customer nominee.
     */
    public function destroyNominee(Request $request, int $id, int $nomineeId): JsonResponse
    {
        $user = $request->user();
        $customer = Customer::find($id);

        if (!$customer) {
            return $this->notFoundResponse('Customer not found');
        }

        if (!$user->canAccessCompany($customer->company_id) || !$user->canAccessBranch($customer->branch_id)) {
            return $this->forbiddenResponse('Unauthorized access to remove nominee');
        }

        $nominee = \App\Models\CustomerNominee::where('customer_id', $customer->id)->find($nomineeId);
        if (!$nominee) {
            return $this->notFoundResponse('Nominee record not found');
        }

        $nominee->delete();

        return $this->successResponse(null, 'Nominee deleted successfully');
    }
}