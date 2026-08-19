<?php

namespace App\Http\Controllers;

use App\Models\ShopCheckout;
use App\Services\MidtransService;
use App\Services\ShopOrderService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class ShopCheckoutController extends Controller
{
    public function show(Request $request, ShopCheckout $checkout, MidtransService $midtrans): Response
    {
        abort_unless($checkout->user_id === $request->user()->id, 403);
        $checkout->load(['orders.items', 'orders.store']);

        return Inertia::render('Shop/Checkouts/Show', [
            'checkout' => $checkout->toArrayPublic(),
            'snap_token' => $request->session()->get('snap_token', $checkout->canPay() ? $checkout->snap_token : null),
            'midtrans' => [
                'client_key' => $midtrans->clientKey(),
                'snap_url' => $midtrans->snapScriptUrl(),
                'configured' => $midtrans->configured(),
            ],
        ]);
    }

    public function pay(Request $request, ShopCheckout $checkout, ShopOrderService $orders)
    {
        abort_unless($checkout->user_id === $request->user()->id, 403);

        try {
            $token = $orders->ensureCheckoutSnapToken($checkout);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['payment' => $e->getMessage()]);
        }

        if ($request->wantsJson()) {
            return response()->json(['token' => $token]);
        }

        return back()->with('snap_token', $token);
    }
}
