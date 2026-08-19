<?php

namespace App\Http\Controllers;

use App\Services\WishlistService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WishlistController extends Controller
{
    public function __construct(private WishlistService $wishlists) {}

    public function show(Request $request): Response
    {
        return Inertia::render('Shop/Wishlist', [
            'products' => $this->wishlists->products($request->user(), $request),
        ]);
    }

    public function toggle(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
        ]);

        $on = $this->wishlists->toggle($request->user(), $request, (int) $data['product_id']);

        return back()->with('success', $on ? 'Ditambahkan ke wishlist.' : 'Dihapus dari wishlist.');
    }

    public function destroy(Request $request, int $product): RedirectResponse
    {
        $this->wishlists->remove($request->user(), $request, $product);

        return back();
    }
}
