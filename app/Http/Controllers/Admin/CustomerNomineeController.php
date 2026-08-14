<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreNomineeRequest;
use App\Models\Customer;
use App\Models\CustomerNominee;
use App\Services\CustomerService;
use Illuminate\Http\RedirectResponse;

class CustomerNomineeController extends Controller
{
    public function __construct(protected CustomerService $customerService) {}

    public function store(StoreNomineeRequest $request, Customer $customer): RedirectResponse
    {
        $data = $request->validated();
        $this->customerService->addOrUpdateNominee($customer, $data);

        $action = !empty($data['id']) ? 'updated' : 'added';
        return redirect()->back()->with('success', "Nominee {$action} successfully.");
    }

    public function destroy(CustomerNominee $nominee): RedirectResponse
    {
        $this->customerService->deleteNominee($nominee);
        return redirect()->back()->with('success', 'Nominee removed successfully.');
    }
}
