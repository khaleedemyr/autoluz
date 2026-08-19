<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ProductReviewService
{
    /**
     * @return array{
     *     avg: float|null,
     *     avg_label: string|null,
     *     count: int,
     *     distribution: array<int, int>,
     *     items: list<array<string, mixed>>,
     *     can_review: bool,
     *     mine: array<string, mixed>|null,
     *     login_required: bool
     * }
     */
    public function forProduct(Product $product, ?User $user): array
    {
        $empty = [
            'avg' => null,
            'avg_label' => null,
            'count' => 0,
            'distribution' => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0],
            'items' => [],
            'can_review' => false,
            'mine' => null,
            'login_required' => $user === null,
        ];

        if (! Schema::hasTable('product_reviews')) {
            return $empty;
        }
        $items = ProductReview::query()
            ->with('user')
            ->where('product_id', $product->id)
            ->orderByDesc('id')
            ->limit(40)
            ->get();

        $count = ProductReview::query()->where('product_id', $product->id)->count();
        $avg = $count
            ? round((float) ProductReview::query()->where('product_id', $product->id)->avg('rating'), 1)
            : null;

        $distribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        ProductReview::query()
            ->where('product_id', $product->id)
            ->selectRaw('rating, COUNT(*) as total')
            ->groupBy('rating')
            ->get()
            ->each(function ($row) use (&$distribution) {
                $distribution[(int) $row->rating] = (int) $row->total;
            });

        $mine = $user
            ? $items->first(fn (ProductReview $review) => $review->user_id === $user->id)
                ?: ProductReview::query()->where('product_id', $product->id)->where('user_id', $user->id)->first()
            : null;

        $order = $user ? $this->purchasedOrder($user, $product->id) : null;

        return [
            'avg' => $avg,
            'avg_label' => $avg !== null ? number_format($avg, 1) : null,
            'count' => $count,
            'distribution' => $distribution,
            'items' => $items->map->toPublicArray()->values()->all(),
            'can_review' => (bool) $order,
            'mine' => $mine?->toPublicArray(),
            'login_required' => $user === null,
        ];
    }

    public function upsert(User $user, Product $product, int $rating, string $body): ProductReview
    {
        $order = $this->purchasedOrder($user, $product->id);
        if (! $order) {
            throw ValidationException::withMessages([
                'rating' => 'Hanya pembeli produk ini yang bisa memberi ulasan.',
            ]);
        }

        return ProductReview::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'product_id' => $product->id,
            ],
            [
                'order_id' => $order->id,
                'rating' => $rating,
                'body' => $body,
            ]
        );
    }

    public function purchasedOrder(User $user, int $productId): ?Order
    {
        $item = OrderItem::query()
            ->with('order')
            ->where('product_id', $productId)
            ->whereHas('order', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->whereIn('status', Order::purchasedStatuses());
            })
            ->latest('id')
            ->first();

        return $item?->order;
    }
}
