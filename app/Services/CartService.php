<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function find(?User $user, Request $request): ?Cart
    {
        if ($user) {
            return Cart::query()->where('user_id', $user->id)->first();
        }

        return Cart::query()
            ->where('session_id', $request->session()->getId())
            ->whereNull('user_id')
            ->first();
    }

    public function current(?User $user, Request $request): Cart
    {
        $existing = $this->find($user, $request);
        if ($existing) {
            return $existing;
        }

        if ($user) {
            return Cart::query()->firstOrCreate(['user_id' => $user->id]);
        }

        return Cart::query()->create(['session_id' => $request->session()->getId()]);
    }

    /**
     * @return array{count: int, subtotal: int, subtotal_label: string, weight_grams: int, items: list<array>}
     */
    public function summaryForRequest(?User $user, Request $request): array
    {
        $cart = $this->find($user, $request);

        return $cart ? $this->summary($cart) : [
            'count' => 0,
            'subtotal' => 0,
            'subtotal_label' => MediaUrl::formatRupiah(0),
            'weight_grams' => 0,
            'items' => [],
        ];
    }

    public function add(Cart $cart, int $variantId, int $qty = 1): CartItem
    {
        $qty = max(1, $qty);
        $variant = $this->sellableVariant($variantId);

        if ($variant->stock < $qty) {
            throw ValidationException::withMessages([
                'qty' => 'Stok tidak mencukupi. Tersisa '.$variant->stock.'.',
            ]);
        }

        $item = $cart->items()->where('product_variant_id', $variant->id)->first();
        if ($item) {
            $next = $item->qty + $qty;
            if ($variant->stock < $next) {
                throw ValidationException::withMessages([
                    'qty' => 'Stok tidak mencukupi. Tersisa '.$variant->stock.'.',
                ]);
            }
            $item->update(['qty' => $next]);

            return $item->fresh(['product', 'variant']);
        }

        return $cart->items()->create([
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'qty' => $qty,
        ]);
    }

    public function updateQty(Cart $cart, int $itemId, int $qty): void
    {
        $item = $cart->items()->whereKey($itemId)->firstOrFail();

        if ($qty < 1) {
            $item->delete();

            return;
        }

        $variant = $this->sellableVariant((int) $item->product_variant_id);
        if ($variant->stock < $qty) {
            throw ValidationException::withMessages([
                'qty' => 'Stok tidak mencukupi. Tersisa '.$variant->stock.'.',
            ]);
        }

        $item->update(['qty' => $qty]);
    }

    public function remove(Cart $cart, int $itemId): void
    {
        $cart->items()->whereKey($itemId)->delete();
    }

    public function clear(Cart $cart): void
    {
        $cart->items()->delete();
    }

    public function mergeGuestCartIntoUser(User $user, string $guestSessionId): void
    {
        $guest = Cart::query()
            ->where('session_id', $guestSessionId)
            ->whereNull('user_id')
            ->with('items')
            ->first();

        if (! $guest || $guest->items->isEmpty()) {
            $guest?->delete();

            return;
        }

        $userCart = Cart::query()->firstOrCreate(['user_id' => $user->id]);

        DB::transaction(function () use ($guest, $userCart) {
            foreach ($guest->items as $item) {
                $existing = $userCart->items()->where('product_variant_id', $item->product_variant_id)->first();
                if ($existing) {
                    $existing->update(['qty' => $existing->qty + $item->qty]);
                    continue;
                }

                $userCart->items()->create([
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'qty' => $item->qty,
                ]);
            }

            $guest->delete();
        });
    }

    /**
     * @return array{count: int, subtotal: int, subtotal_label: string, items: list<array>}
     */
    public function summary(Cart $cart): array
    {
        $cart->load(['items.product.images', 'items.variant']);
        $items = $cart->items
            ->filter(fn (CartItem $item) => $item->product && $item->variant)
            ->values();

        $subtotal = $items->sum(fn (CartItem $item) => $item->lineTotal());
        $count = (int) $items->sum('qty');

        return [
            'count' => $count,
            'subtotal' => $subtotal,
            'subtotal_label' => MediaUrl::formatRupiah($subtotal),
            'weight_grams' => (int) $items->sum(fn (CartItem $item) => $item->toArrayPublic()['weight_grams']),
            'items' => $items->map->toArrayPublic()->values()->all(),
        ];
    }

    public function sellableVariant(int $variantId): ProductVariant
    {
        $variant = ProductVariant::query()
            ->with('product')
            ->whereKey($variantId)
            ->active()
            ->first();

        if (! $variant || ! $variant->product || $variant->product->status !== 'published') {
            throw ValidationException::withMessages([
                'variant_id' => 'Varian produk tidak tersedia.',
            ]);
        }

        return $variant;
    }
}
