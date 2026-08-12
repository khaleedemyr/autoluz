<?php

namespace App\Http\Middleware;

use App\Models\RedirectMap;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CheckRedirectMaps
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethodSafe()) {
            return $next($request);
        }

        try {
            $raw = $request->getPathInfo() ?: '/';
            $normalized = '/'.ltrim($raw, '/');
            $candidates = array_values(array_unique([
                $raw,
                $normalized,
                rtrim($normalized, '/') ?: '/',
            ]));

            // Cache plain arrays only — never Eloquent collections (breaks unserialize).
            /** @var array<string, array{to_path: string, status_code: int}> $map */
            $map = Cache::remember('autoluz.redirect_maps.v2', 300, function () {
                return RedirectMap::query()
                    ->active()
                    ->get(['from_path', 'to_path', 'status_code'])
                    ->mapWithKeys(fn (RedirectMap $row) => [
                        (string) $row->from_path => [
                            'to_path' => (string) $row->to_path,
                            'status_code' => (int) ($row->status_code ?: 301),
                        ],
                    ])
                    ->all();
            });

            foreach ($candidates as $candidate) {
                if (! isset($map[$candidate])) {
                    continue;
                }

                $target = $map[$candidate]['to_path'];
                $status = $map[$candidate]['status_code'] ?: 301;

                if (! str_starts_with($target, 'http://') && ! str_starts_with($target, 'https://')) {
                    $target = url($target);
                }

                return redirect()->to($target, $status);
            }
        } catch (Throwable $e) {
            Log::warning('redirect_maps lookup failed', [
                'message' => $e->getMessage(),
            ]);
        }

        return $next($request);
    }
}
