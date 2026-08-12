<?php

namespace App\Http\Middleware;

use App\Models\RedirectMap;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CheckRedirectMaps
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethodSafe()) {
            return $next($request);
        }

        $raw = $request->getPathInfo() ?: '/';
        $normalized = '/'.ltrim($raw, '/');
        $candidates = array_values(array_unique([
            $raw,
            $normalized,
            rtrim($normalized, '/') ?: '/',
        ]));

        $map = Cache::remember('autoluz.redirect_maps.v1', 300, function () {
            return RedirectMap::query()
                ->active()
                ->get(['from_path', 'to_path', 'status_code'])
                ->keyBy('from_path');
        });

        $redirect = null;
        foreach ($candidates as $candidate) {
            if ($map->has($candidate)) {
                $redirect = $map->get($candidate);
                break;
            }
        }

        if ($redirect) {
            $target = $redirect->to_path;

            if (! str_starts_with($target, 'http://') && ! str_starts_with($target, 'https://')) {
                $target = url($target);
            }

            return redirect()->to($target, $redirect->status_code ?: 301);
        }

        return $next($request);
    }
}
