<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ShopCategory;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $store = $this->currentStore($request);
        $q = trim((string) $request->query('q', ''));
        $categoryId = (int) $request->query('shop_category_id', 0);

        $products = Product::query()
            ->where('store_id', $store->id)
            ->with(['category', 'store'])
            ->with(['variants' => fn ($query) => $query->orderBy('sort_order')])
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('name', 'like', $like)->orWhere('excerpt', 'like', $like);
                });
            })
            ->when($categoryId > 0, fn ($query) => $query->where('shop_category_id', $categoryId))
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Product $product) => [
                ...$product->toCardArray(),
                'status' => $product->status,
                'stock' => $product->totalStock(),
                'variants_count' => $product->variants->count(),
            ]);

        return Inertia::render('Admin/Products/Index', [
            'mode' => 'seller',
            'products' => $products,
            'categories' => $this->categoryOptions(),
            'filters' => [
                'q' => $q,
                'shop_category_id' => $categoryId > 0 ? $categoryId : '',
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Products/Form', [
            'mode' => 'seller',
            'product' => null,
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $store = $this->currentStore($request);
        $data = $this->validated($request);
        $product = new Product($this->payload($data));
        $product->store_id = $store->id;
        $product->featured = false;
        $product->slug = Product::uniqueSlug($data['slug'] ?: $data['name']);
        $this->applyCover($request, $product);
        $product->save();
        $this->syncImages($request, $product);
        $this->syncVariants($request, $product);
        Cache::forget('autoluz.home.page.v4');
        Cache::forget('autoluz.home.page.v5');

        return redirect()
            ->route('seller.products.edit', $product)
            ->with('success', 'Produk dibuat.');
    }

    public function edit(Request $request, Product $product): Response
    {
        $this->authorizeStore($request, $product);
        $product->load(['category', 'images', 'variants']);

        return Inertia::render('Admin/Products/Form', [
            'mode' => 'seller',
            'product' => [
                'id' => $product->id,
                'shop_category_id' => $product->shop_category_id,
                'name' => $product->name,
                'slug' => $product->slug,
                'excerpt' => $product->excerpt,
                'description_html' => $product->description_html,
                'cover_image_url' => $product->coverUrl(),
                'weight_grams' => $product->weight_grams,
                'featured' => $product->featured,
                'status' => $product->status,
                'published_at' => optional($product->published_at)?->format('Y-m-d\TH:i'),
                'sort_order' => $product->sort_order,
                'images' => $product->images->map->toArrayPublic()->values()->all(),
                'variants' => $product->variants->map(fn (ProductVariant $variant) => [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'size' => $variant->size,
                    'color' => $variant->color,
                    'price' => $variant->price,
                    'stock' => $variant->stock,
                    'is_active' => $variant->is_active,
                ])->values()->all(),
            ],
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->authorizeStore($request, $product);
        $data = $this->validated($request, $product);
        $product->fill($this->payload($data));
        $product->featured = false;
        if (! empty($data['slug'])) {
            $product->slug = Product::uniqueSlug($data['slug'], $product->id);
        }
        $this->applyCover($request, $product);
        $product->save();
        $this->syncImages($request, $product);
        $this->syncVariants($request, $product);
        Cache::forget('autoluz.home.page.v4');
        Cache::forget('autoluz.home.page.v5');

        return redirect()
            ->route('seller.products.edit', $product)
            ->with('success', 'Produk disimpan.');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $this->authorizeStore($request, $product);
        $product->delete();
        Cache::forget('autoluz.home.page.v4');
        Cache::forget('autoluz.home.page.v5');

        return redirect()
            ->route('seller.products.index')
            ->with('success', 'Produk dihapus.');
    }

    public function uploadImage(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'max:5120'],
        ]);

        $path = $request->file('image')->store('products/content', 'public');

        return response()->json([
            'url' => url('/storage/'.$path),
        ]);
    }

    private function currentStore(Request $request): Store
    {
        return $request->user()->ownedStore()->firstOrFail();
    }

    private function authorizeStore(Request $request, Product $product): void
    {
        abort_unless($product->store_id === $this->currentStore($request)->id, 403);
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'shop_category_id' => ['nullable', 'integer', 'exists:shop_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:220', Rule::unique('products', 'slug')->ignore($product?->id)],
            'excerpt' => ['nullable', 'string', 'max:2000'],
            'description_html' => ['nullable', 'string'],
            'weight_grams' => ['required', 'integer', 'min:1', 'max:30000'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'published_at' => ['nullable', 'date'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'cover_image' => ['nullable', 'image', 'max:5120'],
            'remove_cover_image' => ['boolean'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'max:5120'],
            'remove_image_ids' => ['nullable', 'array'],
            'remove_image_ids.*' => ['integer'],
            'captions' => ['nullable', 'array'],
            'captions.*' => ['nullable', 'string', 'max:255'],
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.sku' => ['nullable', 'string', 'max:80'],
            'variants.*.size' => ['nullable', 'string', 'max:40'],
            'variants.*.color' => ['nullable', 'string', 'max:40'],
            'variants.*.price' => ['required', 'integer', 'min:0'],
            'variants.*.stock' => ['required', 'integer', 'min:0'],
            'variants.*.is_active' => ['boolean'],
        ]);
    }

    private function payload(array $data): array
    {
        return [
            'shop_category_id' => $data['shop_category_id'] ?: null,
            'name' => $data['name'],
            'excerpt' => $data['excerpt'] ?? null,
            'description_html' => $data['description_html'] ?? null,
            'weight_grams' => (int) $data['weight_grams'],
            'status' => $data['status'],
            'published_at' => $data['published_at'] ?? ($data['status'] === 'published' ? now() : null),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }

    private function applyCover(Request $request, Product $product): void
    {
        if ($request->boolean('remove_cover_image')) {
            $product->cover_image_url = null;
        }

        if ($request->hasFile('cover_image')) {
            $path = $request->file('cover_image')->store('products', 'public');
            $product->cover_image_url = '/storage/'.$path;
        }
    }

    private function syncImages(Request $request, Product $product): void
    {
        $removeIds = collect($request->input('remove_image_ids', []))->map(fn ($id) => (int) $id)->all();
        if ($removeIds !== []) {
            ProductImage::query()
                ->where('product_id', $product->id)
                ->whereIn('id', $removeIds)
                ->delete();
        }

        $captions = $request->input('captions', []);
        if (is_array($captions)) {
            foreach ($captions as $imageId => $caption) {
                ProductImage::query()
                    ->where('product_id', $product->id)
                    ->whereKey((int) $imageId)
                    ->update(['caption' => $caption ?: null]);
            }
        }

        if ($request->hasFile('images')) {
            $maxSort = (int) $product->images()->max('sort_order');
            foreach ($request->file('images') as $file) {
                $maxSort++;
                $path = $file->store('products/photos', 'public');
                $product->images()->create([
                    'image_url' => '/storage/'.$path,
                    'sort_order' => $maxSort,
                ]);
            }
        }

        if (! $product->cover_image_url) {
            $first = $product->images()->orderBy('sort_order')->first();
            if ($first) {
                $product->update(['cover_image_url' => $first->image_url]);
            }
        }
    }

    private function syncVariants(Request $request, Product $product): void
    {
        $rows = collect($request->input('variants', []))
            ->map(function ($row, $index) {
                $sku = trim((string) ($row['sku'] ?? ''));

                return [
                    'id' => isset($row['id']) ? (int) $row['id'] : null,
                    'sku' => $sku !== '' ? $sku : null,
                    'size' => trim((string) ($row['size'] ?? '')) ?: null,
                    'color' => trim((string) ($row['color'] ?? '')) ?: null,
                    'price' => (int) ($row['price'] ?? 0),
                    'stock' => (int) ($row['stock'] ?? 0),
                    'is_active' => filter_var($row['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
                    'sort_order' => $index,
                ];
            })
            ->values();

        $keepIds = [];
        foreach ($rows as $row) {
            $payload = [
                'sku' => $row['sku'],
                'size' => $row['size'],
                'color' => $row['color'],
                'price' => $row['price'],
                'stock' => $row['stock'],
                'is_active' => $row['is_active'],
                'sort_order' => $row['sort_order'],
            ];

            if ($row['id']) {
                $variant = $product->variants()->whereKey($row['id'])->first();
                if ($variant) {
                    $variant->update($payload);
                    $keepIds[] = $variant->id;
                    continue;
                }
            }

            $created = $product->variants()->create($payload);
            $keepIds[] = $created->id;
        }

        $product->variants()->whereNotIn('id', $keepIds)->delete();
    }

    private function categoryOptions(): array
    {
        return ShopCategory::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (ShopCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
            ])
            ->values()
            ->all();
    }
}
