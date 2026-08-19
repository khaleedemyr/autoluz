<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use App\Models\WishlistItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class WishlistService
{
    /**
     * @return list<int>
     */
    public function productIds(?User $user, Request $request): array
    {
        if (! Schema::hasTable('wishlist_items')) {
            return [];
        }

        return $this->query($user, $request)
            ->pluck('product_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @return array{count: int, product_ids: list<int>}
     */
    public function summaryForRequest(?User $user, Request $request): array
    {
        $ids = $this->productIds($user, $request);

        return [
            'count' => count($ids),
            'product_ids' => $ids,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function products(?User $user, Request $request): array
    {
        $ids = $this->productIds($user, $request);
        if ($ids === []) {
            return [];
        }

        $order = array_flip($ids);

        return Product::query()
            ->published()
            ->withRating()
            ->with(['category', 'variants', 'images', 'store'])
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn (Product $product) => $order[$product->id] ?? 9999)
            ->map->toCardArray()
            ->values()
            ->all();
    }

    public function has(?User $user, Request $request, int $productId): bool
    {
        return $this->query($user, $request)->where('product_id', $productId)->exists();
    }

    public function toggle(?User $user, Request $request, int $productId): bool
    {
        if (! Schema::hasTable('wishlist_items')) {
            throw ValidationException::withMessages([
                'product_id' => 'Wishlist belum siap. Refresh halaman lalu coba lagi.',
            ]);
        }

        $this->assertWishable($productId);

        $existing = $this->query($user, $request)->where('product_id', $productId)->first();
        if ($existing) {
            $existing->delete();

            return false;
        }

        WishlistItem::query()->create([
            'user_id' => $user?->id,
            'session_id' => $user ? null : $request->session()->getId(),
            'product_id' => $productId,
        ]);

        return true;
    }

    public function remove(?User $user, Request $request, int $productId): void
    {
        $this->query($user, $request)->where('product_id', $productId)->delete();
    }

    public function mergeGuestWishlistIntoUser(User $user, string $guestSessionId): void
    {
        if (! Schema::hasTable('wishlist_items')) {
            return;
        }
        $guestItems = WishlistItem::query()
            ->where('session_id', $guestSessionId)
            ->whereNull('user_id')
            ->get();

        if ($guestItems->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($guestItems, $user) {
            $owned = WishlistItem::query()
                ->where('user_id', $user->id)
                ->pluck('product_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            foreach ($guestItems as $item) {
                if (! in_array((int) $item->product_id, $owned, true)) {
                    WishlistItem::query()->create([
                        'user_id' => $user->id,
                        'session_id' => null,
                        'product_id' => $item->product_id,
                    ]);
                }
                $item->delete();
            }
        });
    }

    private function query(?User $user, Request $request)
    {
        if ($user) {
            return WishlistItem::query()->where('user_id', $user->id);
        }

        return WishlistItem::query()
            ->where('session_id', $request->session()->getId())
            ->whereNull('user_id');
    }

    private function assertWishable(int $productId): Product
    {
        $product = Product::query()->published()->whereKey($productId)->first();
        if (! $product) {
            throw ValidationException::withMessages([
                'product_id' => 'Produk tidak tersedia.',
            ]);
        }

        return $product;
    }
}
