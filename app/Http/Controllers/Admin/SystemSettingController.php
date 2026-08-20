<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebsiteSetting;
use Illuminate\Http\Request;

class SystemSettingController extends Controller
{
    public function index()
    {
        $this->authorize('settings.view');

        $settings = WebsiteSetting::firstOrCreate(['id' => 1]);

        return view('admin.system.settings.index', compact('settings'));
    }

    public function updateLoanCharges(Request $request)
    {
        $this->authorize('settings.view');

        $validated = $request->validate([
            'loan_processing_fee_percentage' => 'required|numeric|min:0|max:100',
            'loan_processing_fee_enabled' => 'nullable|boolean',
            'loan_insurance_percentage' => 'required|numeric|min:0|max:100',
            'loan_insurance_enabled' => 'nullable|boolean',
        ]);

        $settings = WebsiteSetting::firstOrCreate(['id' => 1]);

        $settings->update([
            'loan_processing_fee_percentage' => (float) $validated['loan_processing_fee_percentage'],
            'loan_processing_fee_enabled' => $request->has('loan_processing_fee_enabled'),
            'loan_insurance_percentage' => (float) $validated['loan_insurance_percentage'],
            'loan_insurance_enabled' => $request->has('loan_insurance_enabled'),
        ]);

        return redirect()->route('admin.system.settings.index')->with('success', 'Loan charges and fee settings updated successfully.');
    }
}
