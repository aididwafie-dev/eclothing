<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        $appUrl = config('app.url');
        if (is_string($appUrl) && Str::startsWith($appUrl, 'https://')) {
            URL::forceRootUrl($appUrl);
            URL::forceScheme('https');
        }

        $siteTitle = 'Personnel Logistic Accounting System';
        $siteLogoPath = 'front_end/images/logo.png';
        $siteLogoVersion = null;
        try {
            $value = DB::table('site_settings')
                ->where('setting_key', 'site_title')
                ->value('setting_value');
            if (is_string($value) && trim($value) !== '') {
                $siteTitle = $value;
            }

            $logoValue = DB::table('site_settings')
                ->where('setting_key', 'site_logo')
                ->value('setting_value');
            if (is_string($logoValue) && trim($logoValue) !== '') {
                $siteLogoPath = ltrim($logoValue, '/');
            }

            $logoVersionValue = DB::table('site_settings')
                ->where('setting_key', 'site_logo_version')
                ->value('setting_value');
            if (is_string($logoVersionValue) && trim($logoVersionValue) !== '') {
                $siteLogoVersion = trim($logoVersionValue);
            }
        } catch (\Throwable $e) {
        }

        $siteLogoUrl = asset($siteLogoPath);
        if (is_string($siteLogoVersion) && $siteLogoVersion !== '') {
            $siteLogoUrl = $siteLogoUrl . '?v=' . rawurlencode($siteLogoVersion);
        }

        View::share('siteTitle', $siteTitle);
        View::share('siteLogoUrl', $siteLogoUrl);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        //
    }
}
