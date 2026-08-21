<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public const SESSION_KEY = 'locale';

    public const SUPPORTED = ['id', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->header('X-Locale')
            ?: $request->query('locale')
            ?: ($request->hasSession() ? $request->session()->get(self::SESSION_KEY) : null)
            ?: config('app.locale', 'id');

        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = config('app.fallback_locale', 'id');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
