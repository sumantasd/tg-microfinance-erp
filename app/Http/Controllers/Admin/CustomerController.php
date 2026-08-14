<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCustomerRequest;
use App\Http\Requests\Admin\UpdateCustomerRequest;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(protected CustomerService $customerService) {}

    public function index(Request $request): View
    {
        $filters = $request->only([
            'search', 'company_id', 'branch_id', 'status', 'customer_type', 'kyc_status', 'date_from', 'date_to', 'trashed'
        ]);

        $customers = $this->customerService->getPaginatedCustomers($filters, 15);
        $branches = Branch::where('is_active', true)->get();
        $companies = Company::where('is_active', true)->get();

        return view('admin.customers.index', compact('customers', 'branches', 'companies', 'filters'));
    }

    public function create(): View
    {
        $branches = Branch::where('is_active', true)->get();
        $companies = Company::where('is_active', true)->get();

        return view('admin.customers.create', compact('branches', 'companies'));
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $photo = $request->file('profile_photo');
        $addresses = $data['addresses'] ?? [];
        $kycDocs = $data['kyc'] ?? [];
        $guarantors = $data['guarantors'] ?? [];
        $nominees = $data['nominees'] ?? [];

        $customer = $this->customerService->createCustomer(
            $data,
            $photo,
            $addresses,
            $kycDocs,
            $guarantors,
            $nominees
        );

        return redirect()->route('admin.customer.show', $customer->id)
            ->with('success', "Customer '{$customer->full_name}' ({$customer->customer_code}) registered successfully.");
    }

    public function show(Customer $customer): View
    {
        $customer = $this->customerService->getCustomerById($customer->id);

        return view('admin.customers.show', compact('customer'));
    }

    public function edit(Customer $customer): View
    {
        $customer = $this->customerService->getCustomerById($customer->id);
        $branches = Branch::where('is_active', true)->get();
        $companies = Company::where('is_active', true)->get();

        return view('admin.customers.edit', compact('customer', 'branches', 'companies'));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $data = $request->validated();
        $photo = $request->file('profile_photo');
        $addresses = $data['addresses'] ?? [];

        $updatedCustomer = $this->customerService->updateCustomer($customer, $data, $photo, $addresses);

        return redirect()->route('admin.customer.show', $updatedCustomer->id)
            ->with('success', "Customer '{$updatedCustomer->full_name}' updated successfully.");
    }

    public function toggleStatus(Request $request, Customer $customer): RedirectResponse
    {
        $request->validate([
            'status' => 'required|in:active,inactive,blacklisted,deceased,closed',
        ]);

        $this->customerService->changeCustomerStatus($customer, $request->status);

        return redirect()->back()->with('success', "Customer status changed to '{$request->status}' successfully.");
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $name = $customer->full_name;
        $this->customerService->deleteCustomer($customer);

        return redirect()->route('admin.customer.index')
            ->with('success', "Customer '{$name}' moved to trash successfully.");
    }

    public function restore(int $id): RedirectResponse
    {
        $customer = Customer::onlyTrashed()->findOrFail($id);
        $this->customerService->restoreCustomer($customer);

        return redirect()->route('admin.customer.show', $customer->id)
            ->with('success', "Customer '{$customer->full_name}' restored successfully.");
    }
}
