<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

class Branding
{
    /** Returns the URL to the uploaded logo, or null if none is set. */
    public static function logoUrl(): ?string
    {
        $path = Setting::get('app_logo_path');
        if (! $path) {
            return null;
        }
        if (! Storage::disk('public')->exists($path)) {
            return null;
        }
        return Storage::disk('public')->url($path);
    }

    /** Returns the configured brand name (falls back to APP_NAME). */
    public static function name(): string
    {
        return (string) Setting::get('app_brand_name', config('app.name'));
    }

    /** True if a custom logo has been uploaded. */
    public static function hasCustomLogo(): bool
    {
        return self::logoUrl() !== null;
    }

    /** Returns the URL to the uploaded favicon, or null if none is set. */
    public static function faviconUrl(): ?string
    {
        $path = Setting::get('app_favicon_path');
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }
        return Storage::disk('public')->url($path);
    }
}
