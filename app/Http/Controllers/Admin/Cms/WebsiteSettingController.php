<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\UpdateWebsiteSettingRequest;
use App\Models\WebsiteSetting;
use Illuminate\Support\Facades\Storage;

class WebsiteSettingController extends Controller
{
    public function edit()
    {
        $this->authorize('website.manage');

        $settings = WebsiteSetting::firstOrCreate(
            ['id' => 1],
            [
                'company_name' => 'TG Microfinance',
                'phone' => '+1 (800) 555-0199',
                'email' => 'info@tgmicrofinance.org',
                'address' => '123 Financial Plaza, Suite 400, Capital City',
                'footer_text' => '© ' . date('Y') . ' TG Microfinance ERP. All rights reserved. Empowering financial inclusion.',
                'social_links' => [
                    'facebook' => 'https://facebook.com',
                    'twitter' => 'https://twitter.com',
                    'linkedin' => 'https://linkedin.com',
                    'instagram' => 'https://instagram.com',
                    'youtube' => 'https://youtube.com',
                ],
            ]
        );

        return view('admin.cms.settings.edit', compact('settings'));
    }

    public function update(UpdateWebsiteSettingRequest $request)
    {
        $this->authorize('website.manage');

        $settings = WebsiteSetting::firstOrCreate(['id' => 1]);
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            if ($settings->logo && Storage::disk('public')->exists($settings->logo)) {
                Storage::disk('public')->delete($settings->logo);
            }
            $data['logo'] = $request->file('logo')->store('cms/settings', 'public');
        }

        if ($request->hasFile('favicon')) {
            if ($settings->favicon && Storage::disk('public')->exists($settings->favicon)) {
                Storage::disk('public')->delete($settings->favicon);
            }
            $data['favicon'] = $request->file('favicon')->store('cms/settings', 'public');
        }

        $settings->update($data);

        return redirect()->route('admin.cms.settings.edit')->with('success', 'Website settings updated successfully.');
    }
}
