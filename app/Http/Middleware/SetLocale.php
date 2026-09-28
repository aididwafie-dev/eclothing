<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the language the visitor picked with the EN | BM toggle.
 *
 * The choice lives in the session, so it follows them from the sign-in screen
 * into the app and survives logging in. A visitor who has not picked one, or
 * whose session holds anything unrecognised, gets the default language
 * (Bahasa Malaysia, config app.locale) rather than an error.
 */
class SetLocale
{
    public const SUPPORTED = ['en', 'ms'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = (string) $request->session()->get('locale', '');

        if (!in_array($locale, self::SUPPORTED, true)) {
            $locale = config('app.locale', 'en');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
