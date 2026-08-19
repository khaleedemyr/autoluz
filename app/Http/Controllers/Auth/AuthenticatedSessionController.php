<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(Request $request): Response
    {
        if ($request->filled('intended')) {
            $intended = $request->string('intended')->toString();
            if (str_starts_with($intended, '/') && ! str_starts_with($intended, '//')) {
                $request->session()->put('url.intended', url($intended));
            }
        }

        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $guestSessionId = $request->session()->getId();

        $request->authenticate();

        app(CartService::class)->mergeGuestCartIntoUser($request->user(), $guestSessionId);

        $request->session()->regenerate();

        if ($request->user()?->canAccessAdmin()) {
            return redirect()->intended(route('admin.dashboard', absolute: false));
        }

        if ($request->user()?->canAccessSeller()) {
            return redirect()->intended(route('seller.dashboard', absolute: false));
        }

        return redirect()->intended(route('community.index', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
