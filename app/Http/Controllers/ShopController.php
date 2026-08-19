<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ShopCategory;
use App\Models\Store;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShopController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));
        $category = trim((string) $request->query('kategori', ''));
        $storeSlug = trim((string) $request->query('toko', ''));
        $sort = (string) $request->query('sort', 'newest');
        $min = $request->query('min');
        $max = $request->query('max');

        $products = Product::query()
            ->published()
            ->with(['category', 'variants', 'images', 'store'])
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
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Product $product) => $product->toCardArray());

        return Inertia::render('Shop/Index', [
            'products' => $products,
            'categories' => ShopCategory::query()->active()->orderBy('sort_order')->orderBy('name')->get()->map->toNavArray()->values()->all(),
            'stores' => Store::query()->approved()->orderByDesc('is_official')->orderBy('name')->get()->map->toCardArray()->values()->all(),
            'filters' => [
                'q' => $q,
                'kategori' => $category,
                'toko' => $storeSlug,
                'sort' => $sort,
                'min' => is_numeric($min) ? (int) $min : '',
                'max' => is_numeric($max) ? (int) $max : '',
            ],
        ]);
    }

    public function showStore(Request $request, Store $store): Response
    {
        abort_unless($store->isApproved(), 404);

        $products = Product::query()
            ->published()
            ->where('store_id', $store->id)
            ->with(['category', 'variants', 'images', 'store'])
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Product $product) => $product->toCardArray());

        return Inertia::render('Shop/Stores/Show', [
            'store' => $store->toPublicArray(),
            'products' => $products,
        ]);
    }

    public function show(Product $product): Response
    {
        $product->load(['category', 'images', 'variants', 'store']);

        abort_unless(
            $product->status === 'published'
            && ($product->published_at === null || $product->published_at->lte(now()))
            && $product->store?->isApproved(),
            404
        );

        $related = Product::query()
            ->published()
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
        ]);
    }
}
