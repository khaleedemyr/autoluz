<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ShopCategory;
use App\Models\Store;
use App\Services\ProductReviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class ShopController extends Controller
{
    public function index(Request $request): Response
    {
        [$products, $filters] = $this->catalog($request);

        return Inertia::render('Shop/Index', [
            'products' => $products,
            'categories' => $this->categoryOptions(),
            'stores' => Store::query()->approved()->orderByDesc('is_official')->orderBy('name')->get()->map->toCardArray()->values()->all(),
            'filters' => $filters,
        ]);
    }

    public function showStore(Request $request, Store $store): Response
    {
        abort_unless($store->isApproved(), 404);

        [$products, $filters] = $this->catalog($request, $store->id);

        $cover = Product::query()
            ->published()
            ->where('store_id', $store->id)
            ->whereNotNull('cover_image_url')
            ->where('cover_image_url', '!=', '')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->first();

        return Inertia::render('Shop/Stores/Show', [
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

    public function show(Request $request, Product $product, ProductReviewService $reviews): Response
    {
        $product->load(['category', 'images', 'variants', 'store']);

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

        return Inertia::render('Shop/Show', [
            'product' => $product->toDetailArray(),
            'related' => $related,
            'reviews' => $reviews->forProduct($product, $request->user()),
        ]);
    }

    /**
     * @return array{0: \Illuminate\Pagination\LengthAwarePaginator, 1: array<string, mixed>}
     */
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
            ->withQueryString()
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

    /**
     * @return list<array{id: int, name: string, slug: string}>
     */
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
