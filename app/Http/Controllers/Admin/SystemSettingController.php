<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebsiteSetting;
use Illuminate\Http\Request;

use App\Services\SystemBrandingService;
use Illuminate\Support\Facades\Storage;

class SystemSettingController extends Controller
{
    public function index()
    {
        $this->authorize('settings.view');

        $settings = WebsiteSetting::first() ?? WebsiteSetting::create([]);
        $branding = SystemBrandingService::getBranding();
        $theme = \App\Services\SystemThemeService::getTheme();
        $themePresets = \App\Services\SystemThemeService::getPresets();

        return view('admin.system.settings.index', compact('settings', 'branding', 'theme', 'themePresets'));
    }

    public function updateBranding(Request $request)
    {
        $this->authorize('settings.view');

        $request->validate([
            'company_name' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'logo_icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp,ico|max:2048',
            'favicon' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg,webp,ico|max:1024',
        ]);

        $settings = WebsiteSetting::first() ?? WebsiteSetting::create([]);
        $settings->company_name = trim($request->input('company_name'));

        // Handle Main Logo Removal or Upload
        if ($request->has('remove_logo') && !empty($request->input('remove_logo'))) {
            if ($settings->logo && Storage::disk('public')->exists($settings->logo)) {
                Storage::disk('public')->delete($settings->logo);
            }
            $settings->logo = null;
        } elseif ($request->hasFile('logo')) {
            if ($settings->logo && Storage::disk('public')->exists($settings->logo)) {
                Storage::disk('public')->delete($settings->logo);
            }
            $settings->logo = $request->file('logo')->store('cms/settings', 'public');
        }

        // Handle Logo Icon Removal or Upload
        if ($request->has('remove_logo_icon') && !empty($request->input('remove_logo_icon'))) {
            if ($settings->logo_icon && Storage::disk('public')->exists($settings->logo_icon)) {
                Storage::disk('public')->delete($settings->logo_icon);
            }
            $settings->logo_icon = null;
        } elseif ($request->hasFile('logo_icon')) {
            if ($settings->logo_icon && Storage::disk('public')->exists($settings->logo_icon)) {
                Storage::disk('public')->delete($settings->logo_icon);
            }
            $settings->logo_icon = $request->file('logo_icon')->store('cms/settings', 'public');
        }

        // Handle Favicon Removal or Upload
        if ($request->has('remove_favicon') && !empty($request->input('remove_favicon'))) {
            if ($settings->favicon && Storage::disk('public')->exists($settings->favicon)) {
                Storage::disk('public')->delete($settings->favicon);
            }
            $settings->favicon = null;
        } elseif ($request->hasFile('favicon')) {
            if ($settings->favicon && Storage::disk('public')->exists($settings->favicon)) {
                Storage::disk('public')->delete($settings->favicon);
            }
            $settings->favicon = $request->file('favicon')->store('cms/settings', 'public');
        }

        $settings->save();
        SystemBrandingService::clearCache();

        return redirect()->route('admin.system.settings.index')->with('success', 'System branding settings updated successfully.');
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

    public function updateCompanyProfile(Request $request)
    {
        $this->authorize('settings.view');

        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'company_type' => 'nullable|string|max:255',
            'tagline' => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|max:100',
            'gstin' => 'nullable|string|max:50',
            'pan' => 'nullable|string|max:50',
            'cin' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:50',
            'alternate_phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|string|max:255',
            'whatsapp' => 'nullable|string|max:50',
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'pin_code' => 'nullable|string|max:20',
        ]);

        $settings = WebsiteSetting::first() ?? WebsiteSetting::create([]);
        $settings->update($validated);

        SystemBrandingService::clearCache();

        return redirect()->route('admin.system.settings.index')->with('success', 'Company profile and registration information updated successfully.');
    }

    public function updateTheme(Request $request)
    {
        $this->authorize('settings.view');

        $validated = $request->validate([
            'theme_preset' => 'nullable|string|max:50',
            'primary_color' => ['required', 'string', 'regex:/^#(?:[0-9a-fA-F]{3}){1,2}$/'],
            'secondary_color' => ['required', 'string', 'regex:/^#(?:[0-9a-fA-F]{3}){1,2}$/'],
            'accent_color' => ['required', 'string', 'regex:/^#(?:[0-9a-fA-F]{3}){1,2}$/'],
            'sidebar_color' => ['required', 'string', 'regex:/^#(?:[0-9a-fA-F]{3}){1,2}$/'],
            'sidebar_text_color' => ['required', 'string', 'regex:/^#(?:[0-9a-fA-F]{3}){1,2}$/'],
            'sidebar_active_color' => ['required', 'string', 'regex:/^#(?:[0-9a-fA-F]{3}){1,2}$/'],
            'header_color' => ['required', 'string', 'regex:/^#(?:[0-9a-fA-F]{3}){1,2}$/'],
            'header_text_color' => ['required', 'string', 'regex:/^#(?:[0-9a-fA-F]{3}){1,2}$/'],
            'body_background_color' => ['required', 'string', 'regex:/^#(?:[0-9a-fA-F]{3}){1,2}$/'],
            'card_background_color' => ['required', 'string', 'regex:/^#(?:[0-9a-fA-F]{3}){1,2}$/'],
            'border_color' => ['required', 'string', 'regex:/^#(?:[0-9a-fA-F]{3}){1,2}$/'],
            'button_radius' => 'required|string|max:20',
            'theme_mode' => 'required|string|in:light,dark,system',
        ]);

        $settings = WebsiteSetting::first() ?? WebsiteSetting::create([]);
        $settings->update($validated);

        \App\Services\SystemThemeService::clearCache();

        return redirect()->route('admin.system.settings.index')->with('success', 'Admin Panel visual theme settings updated successfully.');
    }

    public function resetTheme(Request $request)
    {
        $this->authorize('settings.view');

        $defaultTheme = \App\Services\SystemThemeService::getPresets()['navy_gold'];

        $settings = WebsiteSetting::first() ?? WebsiteSetting::create([]);
        $settings->update([
            'theme_preset' => 'navy_gold',
            'primary_color' => $defaultTheme['primary_color'],
            'secondary_color' => $defaultTheme['secondary_color'],
            'accent_color' => $defaultTheme['accent_color'],
            'sidebar_color' => $defaultTheme['sidebar_color'],
            'sidebar_text_color' => $defaultTheme['sidebar_text_color'],
            'sidebar_active_color' => $defaultTheme['sidebar_active_color'],
            'header_color' => $defaultTheme['header_color'],
            'header_text_color' => $defaultTheme['header_text_color'],
            'body_background_color' => $defaultTheme['body_background_color'],
            'card_background_color' => $defaultTheme['card_background_color'],
            'border_color' => $defaultTheme['border_color'],
            'button_radius' => $defaultTheme['button_radius'],
            'theme_mode' => $defaultTheme['theme_mode'],
        ]);

        \App\Services\SystemThemeService::clearCache();

        return redirect()->route('admin.system.settings.index')->with('success', 'Admin Panel visual theme has been reset to default.');
    }
}
