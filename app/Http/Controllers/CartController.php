<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function __construct(private CartService $carts) {}

    public function show(Request $request): Response
    {
        $cart = $this->carts->current($request->user(), $request);

        return Inertia::render('Shop/Cart', [
            'cart' => $this->carts->summary($cart),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer'],
            'qty' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $cart = $this->carts->current($request->user(), $request);
        $this->carts->add($cart, (int) $data['variant_id'], (int) ($data['qty'] ?? 1));

        return back()->with('success', 'Ditambahkan ke keranjang.');
    }

    public function update(Request $request, int $item): RedirectResponse
    {
        $data = $request->validate([
            'qty' => ['required', 'integer', 'min:0', 'max:99'],
        ]);

        $cart = $this->carts->current($request->user(), $request);
        $this->carts->updateQty($cart, $item, (int) $data['qty']);

        return back();
    }

    public function destroy(Request $request, int $item): RedirectResponse
    {
        $cart = $this->carts->current($request->user(), $request);
        $this->carts->remove($cart, $item);

        return back();
    }
}
