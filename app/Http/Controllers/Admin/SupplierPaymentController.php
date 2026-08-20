<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSupplierPaymentRequest;
use App\Services\SupplierService;
use Illuminate\Http\RedirectResponse;

class SupplierPaymentController extends Controller
{
    public function __construct(protected SupplierService $supplierService) {}

    public function store(StoreSupplierPaymentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $payment = $this->supplierService->recordPayment($data);

        return redirect()->back()
            ->with('success', "Payment '{$payment->payment_number}' of ₹" . number_format($payment->amount, 2) . " recorded successfully for supplier.");
    }

    public function allocate(\Illuminate\Http\Request $request, \App\Models\SupplierPayment $payment): RedirectResponse
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user && !$user->isSuperAdmin() && $payment->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to supplier payment.');
        }

        $data = $request->validate([
            'allocation_mode' => 'required|in:auto,manual',
            'allocations' => 'nullable|array',
            'allocations.*.purchase_id' => 'required_with:allocations|exists:product_purchases,id',
            'allocations.*.amount' => 'required_with:allocations|numeric|min:0',
        ]);

        $this->supplierService->allocateExistingPayment($payment, $data);

        return redirect()->back()
            ->with('success', "Payment '{$payment->payment_number}' allocated successfully.");
    }
}
