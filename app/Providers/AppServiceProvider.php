<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
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

        $siteTitle = 'e-Clothing';
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

        // 60/min per caller, not per IP. The mobile API's bearer-token auth
        // (EnsureMobileTokenIsAuthenticated) doesn't use Laravel's Auth guard,
        // so the default throttle middleware's $request->user() lookup is
        // always empty and it falls back to IP -- meaning everyone behind the
        // same base/office network shares one 60-request budget for the
        // entire API. Keying on the token itself (present on every
        // authenticated call, before api.auth even runs) gives each signed-in
        // device its own budget. Calls with no token yet (login, register,
        // forgot-password) still throttle by IP, since that is genuinely the
        // only thing to key on and brute-force protection there matters.
        RateLimiter::for('api', function (Request $request) {
            $token = $request->bearerToken();
            $key = $token ? 'token:' . hash('sha256', $token) : 'ip:' . $request->ip();

            return Limit::perMinute(60)->by($key);
        });
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
