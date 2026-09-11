<?php

namespace App\Services;

use App\Models\WebsiteSetting;
use Illuminate\Support\Facades\Cache;

class SystemBrandingService
{
    /**
     * Cache key for system branding data.
     */
    public const CACHE_KEY = 'system_branding';

    /**
     * Retrieve current system branding settings object (cached).
     */
    public static function getBranding(): object
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            try {
                $setting = WebsiteSetting::first();

                $systemName = !empty($setting?->company_name)
                    ? $setting->company_name
                    : config('app.name', 'Microfinance ERP');

                $legalName = !empty($setting?->legal_name)
                    ? $setting->legal_name
                    : $systemName;

                $logoUrl = $setting?->logo_url ?: asset('images/logo.png');
                $logoIconUrl = $setting?->logo_icon_url ?: asset('images/logo-icon.png');
                $faviconUrl = $setting?->favicon_url ?: asset('images/logo-icon.png');
                $fullAddress = $setting?->full_address ?: ($setting?->address ?: '');

                return (object) [
                    'system_name' => $systemName,
                    'company_name' => $systemName,
                    'legal_name' => $legalName,
                    'company_type' => $setting?->company_type ?: 'Financial Institution',
                    'tagline' => $setting?->tagline ?: 'Empowering Financial Growth',
                    'registration_number' => $setting?->registration_number ?: '',
                    'gstin' => $setting?->gstin ?: '',
                    'pan' => $setting?->pan ?: '',
                    'cin' => $setting?->cin ?: '',
                    'phone' => $setting?->phone ?: '',
                    'alternate_phone' => $setting?->alternate_phone ?: '',
                    'email' => $setting?->email ?: config('mail.from.address', ''),
                    'website' => $setting?->website ?: '',
                    'whatsapp' => $setting?->whatsapp ?: '',
                    'full_address' => $fullAddress,
                    'logo_url' => $logoUrl,
                    'logo_icon_url' => $logoIconUrl,
                    'favicon_url' => $faviconUrl,
                    'model' => $setting,
                ];
            } catch (\Throwable $e) {
                $fallbackName = config('app.name', 'Microfinance ERP');
                return (object) [
                    'system_name' => $fallbackName,
                    'company_name' => $fallbackName,
                    'legal_name' => $fallbackName,
                    'company_type' => 'Financial Institution',
                    'tagline' => 'Empowering Financial Growth',
                    'registration_number' => '',
                    'gstin' => '',
                    'pan' => '',
                    'cin' => '',
                    'phone' => '',
                    'alternate_phone' => '',
                    'email' => config('mail.from.address', ''),
                    'website' => '',
                    'whatsapp' => '',
                    'full_address' => '',
                    'logo_url' => asset('images/logo.png'),
                    'logo_icon_url' => asset('images/logo-icon.png'),
                    'favicon_url' => asset('images/logo-icon.png'),
                    'model' => null,
                ];
            }
        });
    }

    /**
     * Clear system branding cache immediately.
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
