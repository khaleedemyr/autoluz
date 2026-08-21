<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ShopCategory;
use App\Models\Store;
use App\Services\CartService;
use App\Services\ProductReviewService;
use App\Services\WishlistService;
use App\Support\GuestSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ShopController extends Controller
{
    public function __construct(
        private CartService $carts,
        private WishlistService $wishlists,
        private ProductReviewService $reviews,
    ) {}

    public function index(Request $request): JsonResponse
    {
        [$products, $filters] = $this->catalog($request);

        return response()->json([
            'products' => $products,
            'categories' => $this->categoryOptions(),
            'stores' => Store::query()->approved()->orderByDesc('is_official')->orderBy('name')->get()->map->toCardArray()->values()->all(),
            'filters' => $filters,
        ]);
    }

    public function showStore(Request $request, string $slug): JsonResponse
    {
        $store = Store::query()->where('slug', $slug)->firstOrFail();
        abort_unless($store->isApproved(), 404);

        [$products, $filters] = $this->catalog($request, $store->id);

        $cover = Product::query()
            ->published()
            ->where('store_id', $store->id)
            ->whereNotNull('cover_image_url')
            ->where('cover_image_url', '!=', '')
            ->orderByDesc('published_at')
            ->first();

        return response()->json([
            'store' => [
                ...$store->toPublicArray(),
                'products_count' => Product::query()->published()->where('store_id', $store->id)->count(),
                'cover_url' => $cover?->coverUrl(),
            ],
            'products' => $products,
            'categories' => $this->categoryOptions($store->id),
            'filters' => $filters,
        ]);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $product = Product::query()
            ->with(['category', 'images', 'variants', 'store'])
            ->where('slug', $slug)
            ->firstOrFail();

        abort_unless(
            $product->status === 'published'
            && ($product->published_at === null || $product->published_at->lte(now()))
            && $product->store?->isApproved(),
            404
        );

        if (Schema::hasTable('product_reviews')) {
            $product->loadCount('reviews')->loadAvg('reviews', 'rating');
        }

        $related = Product::query()
            ->published()
            ->withRating()
            ->with(['category', 'variants', 'images', 'store'])
            ->where('id', '!=', $product->id)
            ->when($product->shop_category_id, fn ($q) => $q->where('shop_category_id', $product->shop_category_id))
            ->orderByDesc('published_at')
            ->limit(4)
            ->get()
            ->map->toCardArray()
            ->values()
            ->all();

        return response()->json([
            'product' => $product->toDetailArray(),
            'related' => $related,
            'reviews' => $this->reviews->forProduct($product, $request->user()),
            'in_wishlist' => $this->wishlists->has($request->user(), $request, $product->id),
        ]);
    }

    public function cart(Request $request): JsonResponse
    {
        $cart = $this->carts->current($request->user(), $request);

        return response()->json([
            'cart' => $this->carts->summary($cart),
            'cart_token' => GuestSession::publicToken($request),
        ]);
    }

    public function addToCart(Request $request): JsonResponse
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer'],
            'qty' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $cart = $this->carts->current($request->user(), $request);
        $this->carts->add($cart, (int) $data['variant_id'], (int) ($data['qty'] ?? 1));

        return response()->json([
            'cart' => $this->carts->summary($cart->fresh()),
            'message' => 'Ditambahkan ke keranjang.',
        ]);
    }

    public function updateCart(Request $request, int $item): JsonResponse
    {
        $data = $request->validate([
            'qty' => ['required', 'integer', 'min:0', 'max:99'],
        ]);

        $cart = $this->carts->current($request->user(), $request);
        $this->carts->updateQty($cart, $item, (int) $data['qty']);

        return response()->json(['cart' => $this->carts->summary($cart->fresh())]);
    }

    public function removeCart(Request $request, int $item): JsonResponse
    {
        $cart = $this->carts->current($request->user(), $request);
        $this->carts->remove($cart, $item);

        return response()->json(['cart' => $this->carts->summary($cart->fresh())]);
    }

    public function wishlist(Request $request): JsonResponse
    {
        return response()->json([
            'products' => $this->wishlists->products($request->user(), $request),
            'summary' => $this->wishlists->summaryForRequest($request->user(), $request),
        ]);
    }

    public function toggleWishlist(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
        ]);

        $on = $this->wishlists->toggle($request->user(), $request, (int) $data['product_id']);

        return response()->json([
            'on' => $on,
            'summary' => $this->wishlists->summaryForRequest($request->user(), $request),
            'message' => $on ? 'Ditambahkan ke wishlist.' : 'Dihapus dari wishlist.',
        ]);
    }

    public function review(Request $request, string $slug): JsonResponse
    {
        $product = Product::query()->where('slug', $slug)->firstOrFail();
        $product->loadMissing('store');

        abort_unless(
            $product->status === 'published'
            && $product->store?->isApproved(),
            404
        );

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'body' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $this->reviews->upsert($request->user(), $product, (int) $data['rating'], trim($data['body']));

        return response()->json([
            'reviews' => $this->reviews->forProduct($product->fresh(), $request->user()),
            'message' => 'Ulasan tersimpan.',
        ]);
    }

    private function catalog(Request $request, ?int $storeId = null): array
    {
        $q = trim((string) $request->query('q', ''));
        $category = trim((string) $request->query('kategori', ''));
        $storeSlug = $storeId ? '' : trim((string) $request->query('toko', ''));
        $sort = (string) $request->query('sort', 'newest');
        $min = $request->query('min');
        $max = $request->query('max');

        $products = Product::query()
            ->published()
            ->withRating()
            ->with(['category', 'variants', 'images', 'store'])
            ->when($storeId, fn ($query) => $query->where('store_id', $storeId))
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('name', 'like', $like)->orWhere('excerpt', 'like', $like);
                });
            })
            ->when($category !== '', function ($query) use ($category) {
                $query->whereHas('category', fn ($inner) => $inner->where('slug', $category));
            })
            ->when($storeSlug !== '', function ($query) use ($storeSlug) {
                $query->whereHas('store', fn ($inner) => $inner->where('slug', $storeSlug));
            })
            ->when(is_numeric($min), fn ($query) => $query->whereHas('variants', fn ($inner) => $inner->where('price', '>=', (int) $min)))
            ->when(is_numeric($max), fn ($query) => $query->whereHas('variants', fn ($inner) => $inner->where('price', '<=', (int) $max)))
            ->when($sort === 'price_asc', fn ($query) => $query->withMin('variants', 'price')->orderBy('variants_min_price'))
            ->when($sort === 'price_desc', fn ($query) => $query->withMin('variants', 'price')->orderByDesc('variants_min_price'))
            ->when($sort === 'name', fn ($query) => $query->orderBy('name'))
            ->when($sort === 'newest' || ! in_array($sort, ['price_asc', 'price_desc', 'name'], true), fn ($query) => $query->orderByDesc('published_at')->orderByDesc('id'))
            ->paginate(16)
            ->through(fn (Product $product) => $product->toCardArray());

        return [
            $products,
            [
                'q' => $q,
                'kategori' => $category,
                'toko' => $storeSlug,
                'sort' => in_array($sort, ['price_asc', 'price_desc', 'name'], true) ? $sort : 'newest',
                'min' => is_numeric($min) ? (int) $min : '',
                'max' => is_numeric($max) ? (int) $max : '',
            ],
        ];
    }

    private function categoryOptions(?int $storeId = null): array
    {
        return ShopCategory::query()
            ->active()
            ->whereHas('products', function ($query) use ($storeId) {
                $query->published()->when($storeId, fn ($inner) => $inner->where('store_id', $storeId));
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map->toNavArray()
            ->values()
            ->all();
    }
}
