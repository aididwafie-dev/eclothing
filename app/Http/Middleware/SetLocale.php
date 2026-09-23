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
 * into the app and survives logging in. Anything unrecognised falls back to
 * English rather than erroring, which also covers a session written by an
 * older version of this app.
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
