<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\FooterSettingRequest;
use App\Models\FooterSetting;
use Illuminate\Support\Facades\Storage;

class FooterSettingController extends Controller
{
    public function edit()
    {
        $this->authorize('website.manage');

        $footer = FooterSetting::firstOrCreate(['id' => 1]);

        return view('admin.cms.footer.edit', compact('footer'));
    }

    public function update(FooterSettingRequest $request)
    {
        $this->authorize('website.manage');

        $footer = FooterSetting::firstOrCreate(['id' => 1]);
        $data = $request->validated();

        if ($request->hasFile('footer_logo')) {
            if ($footer->footer_logo && Storage::disk('public')->exists($footer->footer_logo)) {
                Storage::disk('public')->delete($footer->footer_logo);
            }
            $data['footer_logo'] = $request->file('footer_logo')->store('cms/footer', 'public');
        }

        $footer->update($data);

        return redirect()->route('admin.cms.footer.edit')->with('success', 'Footer settings updated successfully.');
    }
}
