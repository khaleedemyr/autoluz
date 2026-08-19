<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\MidtransService;
use App\Services\ShopOrderService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class ShopOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $orders = $request->user()
            ->orders()
            ->with(['items.product', 'store', 'checkout'])
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Order $order) => $order->toArrayPublic());

        return Inertia::render('Shop/Orders/Index', [
            'orders' => $orders,
        ]);
    }

    public function show(Request $request, Order $order, MidtransService $midtrans): Response
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        $order->load(['items.product', 'store', 'checkout']);

        return Inertia::render('Shop/Orders/Show', [
            'order' => $order->toArrayPublic(),
            'snap_token' => $request->session()->get('snap_token', $order->canPay() ? $order->snap_token : null),
            'midtrans' => [
                'client_key' => $midtrans->clientKey(),
                'snap_url' => $midtrans->snapScriptUrl(),
                'configured' => $midtrans->configured(),
            ],
        ]);
    }

    public function pay(Request $request, Order $order, ShopOrderService $orders)
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        try {
            $token = $orders->ensureSnapToken($order);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['payment' => $e->getMessage()]);
        }

        if ($request->wantsJson()) {
            return response()->json(['token' => $token]);
        }

        return back()->with('snap_token', $token);
    }
}
