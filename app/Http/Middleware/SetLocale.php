<?php

namespace App\Http\Middleware;

use App\Support\LocaleManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the active locale for the request.
 *
 * Resolution order:
 *   1. the signed-in user's stored preference (`user_settings.settings.locale`),
 *   2. the session (covers a guest who just switched on a public page),
 *   3. the configured default.
 *
 * Registered BEFORE HandleInertiaRequests so the shared `locale` prop and the
 * Blade `<html lang>` both read the same, already-applied value.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale();

        if (! LocaleManager::isSupported($locale)) {
            $locale = $request->session()->get('locale');
        }

        App::setLocale(LocaleManager::normalise($locale));

        return $next($request);
    }
}
