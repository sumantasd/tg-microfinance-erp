<?php

namespace App\Services;

use App\Models\WebsiteSetting;
use Illuminate\Support\Facades\Cache;

class SystemThemeService
{
    /**
     * Cache key for system theme configuration.
     */
    public const CACHE_KEY = 'system_theme';

    /**
     * Get theme presets definitions.
     */
    public static function getPresets(): array
    {
        return [
            'navy_gold' => [
                'name' => 'Navy Gold (Default)',
                'badge' => '#0C2340',
                'primary_color' => '#0C2340',
                'secondary_color' => '#C5A059',
                'accent_color' => '#D4AF37',
                'sidebar_color' => '#FFFFFF',
                'sidebar_text_color' => '#475569',
                'sidebar_active_color' => '#2563EB',
                'header_color' => '#FFFFFF',
                'header_text_color' => '#0C2340',
                'body_background_color' => '#F8FAFC',
                'card_background_color' => '#FFFFFF',
                'border_color' => '#E2E8F0',
                'button_radius' => '0.5rem',
                'theme_mode' => 'light',
            ],
            'corporate_blue' => [
                'name' => 'Corporate Blue',
                'badge' => '#1E40AF',
                'primary_color' => '#1E40AF',
                'secondary_color' => '#3B82F6',
                'accent_color' => '#60A5FA',
                'sidebar_color' => '#1E3A8A',
                'sidebar_text_color' => '#F8FAFC',
                'sidebar_active_color' => '#60A5FA',
                'header_color' => '#FFFFFF',
                'header_text_color' => '#1E40AF',
                'body_background_color' => '#F8FAFC',
                'card_background_color' => '#FFFFFF',
                'border_color' => '#E2E8F0',
                'button_radius' => '0.375rem',
                'theme_mode' => 'light',
            ],
            'finance_green' => [
                'name' => 'Finance Green',
                'badge' => '#065F46',
                'primary_color' => '#065F46',
                'secondary_color' => '#10B981',
                'accent_color' => '#34D399',
                'sidebar_color' => '#064E3B',
                'sidebar_text_color' => '#F8FAFC',
                'sidebar_active_color' => '#34D399',
                'header_color' => '#FFFFFF',
                'header_text_color' => '#065F46',
                'body_background_color' => '#F8FAFC',
                'card_background_color' => '#FFFFFF',
                'border_color' => '#E2E8F0',
                'button_radius' => '0.5rem',
                'theme_mode' => 'light',
            ],
            'modern_purple' => [
                'name' => 'Modern Purple',
                'badge' => '#5B21B6',
                'primary_color' => '#5B21B6',
                'secondary_color' => '#8B5CF6',
                'accent_color' => '#A78BFA',
                'sidebar_color' => '#4C1D95',
                'sidebar_text_color' => '#F8FAFC',
                'sidebar_active_color' => '#A78BFA',
                'header_color' => '#FFFFFF',
                'header_text_color' => '#5B21B6',
                'body_background_color' => '#F8FAFC',
                'card_background_color' => '#FFFFFF',
                'border_color' => '#E2E8F0',
                'button_radius' => '0.75rem',
                'theme_mode' => 'light',
            ],
            'professional_dark' => [
                'name' => 'Professional Dark',
                'badge' => '#1F2937',
                'primary_color' => '#1F2937',
                'secondary_color' => '#374151',
                'accent_color' => '#9CA3AF',
                'sidebar_color' => '#111827',
                'sidebar_text_color' => '#F9FAFB',
                'sidebar_active_color' => '#60A5FA',
                'header_color' => '#1F2937',
                'header_text_color' => '#F9FAFB',
                'body_background_color' => '#1F2937',
                'card_background_color' => '#111827',
                'border_color' => '#374151',
                'button_radius' => '0.5rem',
                'theme_mode' => 'dark',
            ],
        ];
    }

    /**
     * Validate whether a given string is a valid HEX color code.
     */
    public static function isValidHex(?string $color): bool
    {
        if (empty($color)) {
            return false;
        }

        return (bool) preg_match('/^#(?:[0-9a-fA-F]{3}){1,2}$/', trim($color));
    }

    /**
     * Sanitize a HEX color value or return a default.
     */
    public static function sanitizeHex(?string $color, string $default): string
    {
        if (self::isValidHex($color)) {
            return strtoupper(trim($color));
        }

        return strtoupper($default);
    }

    /**
     * Get active system theme object (cached).
     */
    public static function getTheme(): object
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $default = self::getPresets()['navy_gold'];

            try {
                $setting = WebsiteSetting::first();

                $themePreset = !empty($setting?->theme_preset) ? $setting->theme_preset : 'navy_gold';
                $primaryColor = self::sanitizeHex($setting?->primary_color, $default['primary_color']);
                $secondaryColor = self::sanitizeHex($setting?->secondary_color, $default['secondary_color']);
                $accentColor = self::sanitizeHex($setting?->accent_color, $default['accent_color']);
                $sidebarColor = self::sanitizeHex($setting?->sidebar_color, $default['sidebar_color']);
                $sidebarTextColor = self::sanitizeHex($setting?->sidebar_text_color, $default['sidebar_text_color']);
                $sidebarActiveColor = self::sanitizeHex($setting?->sidebar_active_color, $default['sidebar_active_color']);
                $headerColor = self::sanitizeHex($setting?->header_color, $default['header_color']);
                $headerTextColor = self::sanitizeHex($setting?->header_text_color, $default['header_text_color']);
                $bodyBgColor = self::sanitizeHex($setting?->body_background_color, $default['body_background_color']);
                $cardBgColor = self::sanitizeHex($setting?->card_background_color, $default['card_background_color']);
                $borderColor = self::sanitizeHex($setting?->border_color, $default['border_color']);

                $buttonRadius = !empty($setting?->button_radius) ? $setting->button_radius : $default['button_radius'];
                $themeMode = in_array($setting?->theme_mode, ['light', 'dark', 'system']) ? $setting->theme_mode : 'light';

                return (object) [
                    'theme_preset' => $themePreset,
                    'primary_color' => $primaryColor,
                    'secondary_color' => $secondaryColor,
                    'accent_color' => $accentColor,
                    'sidebar_color' => $sidebarColor,
                    'sidebar_text_color' => $sidebarTextColor,
                    'sidebar_active_color' => $sidebarActiveColor,
                    'header_color' => $headerColor,
                    'header_text_color' => $headerTextColor,
                    'body_background_color' => $bodyBgColor,
                    'card_background_color' => $cardBgColor,
                    'border_color' => $borderColor,
                    'button_radius' => $buttonRadius,
                    'theme_mode' => $themeMode,
                ];
            } catch (\Throwable $e) {
                return (object) $default;
            }
        });
    }

    /**
     * Clear system theme cache immediately.
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
