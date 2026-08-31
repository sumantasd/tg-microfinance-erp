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

        $query = Customer::with(['branch', 'company', 'kycDocuments', 'groupMemberships.group']);

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
            'kycDocuments',
            'guarantors',
            'nominees',
            'groupMemberships.group',
            'loanAccounts',
        ])->find($id);

        if (!$customer) {
            return $this->notFoundResponse('Customer not found');
        }

        if (!$user->canAccessBranch($customer->branch_id)) {
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
            'address' => 'required|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:20',
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

        $addresses = [
            'present' => [
                'address_line' => $validated['address'],
                'district' => $validated['city'] ?? '',
                'state' => $validated['state'] ?? '',
                'pin_code' => $validated['pincode'] ?? '',
            ],
        ];

        $customer = $this->customerService->createCustomer($validated, null, $addresses);

        return $this->successResponse($customer, 'Customer created successfully', 201);
    }
}
