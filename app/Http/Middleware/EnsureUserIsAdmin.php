<?php

namespace App\Http\Middleware;

use App\Support\AdminPermissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->canAccessAdmin()) {
            abort(403, 'Akses admin diperlukan.');
        }

        $user->loadMissing('role');

        $permission = AdminPermissions::fromRoute($request->route()?->getName());
        if ($permission && ! $user->hasAdminPermission($permission)) {
            abort(403, 'Anda tidak punya akses ke menu ini.');
        }

        return $next($request);
    }
}
