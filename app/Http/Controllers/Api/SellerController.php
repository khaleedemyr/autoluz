<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ShopCategory;
use App\Models\Store;
use App\Services\RajaOngkirService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use RuntimeException;

class SellerController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $store = $request->user()->ownedStore()->firstOrFail();

        return response()->json([
            'store' => $store->toSettingsArray(),
            'stats' => [
                'products' => Product::query()->where('store_id', $store->id)->count(),
                'published' => Product::query()->where('store_id', $store->id)->where('status', 'published')->count(),
                'orders_pending' => Order::query()->where('store_id', $store->id)->where('status', Order::STATUS_PENDING)->count(),
                'orders_paid' => Order::query()->where('store_id', $store->id)->where('status', Order::STATUS_PAID)->count(),
                'orders_ship' => Order::query()->where('store_id', $store->id)->whereIn('status', [Order::STATUS_PAID, Order::STATUS_PACKED])->count(),
                'revenue' => (int) Order::query()
                    ->where('store_id', $store->id)
                    ->whereIn('status', [
                        Order::STATUS_PAID,
                        Order::STATUS_PACKED,
                        Order::STATUS_SHIPPED,
                        Order::STATUS_COMPLETED,
                    ])
                    ->sum('grand_total'),
            ],
        ]);
    }

    public function products(Request $request): JsonResponse
    {
        $store = $this->store($request);
        $q = trim((string) $request->query('q', ''));
        $categoryId = (int) $request->query('shop_category_id', 0);

        $products = Product::query()
            ->where('store_id', $store->id)
            ->with(['category', 'store', 'variants'])
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('name', 'like', $like)->orWhere('excerpt', 'like', $like);
                });
            })
            ->when($categoryId > 0, fn ($query) => $query->where('shop_category_id', $categoryId))
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->through(fn (Product $product) => [
                ...$product->toCardArray(),
                'status' => $product->status,
                'stock' => $product->totalStock(),
                'variants_count' => $product->variants->count(),
            ]);

        return response()->json([
            'products' => $products,
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function productForm(Request $request, ?int $id = null): JsonResponse
    {
        $store = $this->store($request);
        $product = null;
        if ($id) {
            $model = Product::query()->where('store_id', $store->id)->whereKey($id)->firstOrFail();
            $model->load(['category', 'images', 'variants']);
            $product = [
                'id' => $model->id,
                'shop_category_id' => $model->shop_category_id,
                'name' => $model->name,
                'slug' => $model->slug,
                'excerpt' => $model->excerpt,
                'description_html' => $model->description_html,
                'cover_image_url' => $model->coverUrl(),
                'weight_grams' => $model->weight_grams,
                'status' => $model->status,
                'images' => $model->images->map->toArrayPublic()->values()->all(),
                'variants' => $model->variants->map(fn (ProductVariant $variant) => [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'size' => $variant->size,
                    'color' => $variant->color,
                    'price' => $variant->price,
                    'stock' => $variant->stock,
                    'is_active' => $variant->is_active,
                ])->values()->all(),
            ];
        }

        return response()->json([
            'product' => $product,
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function storeProduct(Request $request): JsonResponse
    {
        $store = $this->store($request);
        $data = $this->validatedProduct($request);
        $product = new Product($this->productPayload($data));
        $product->store_id = $store->id;
        $product->featured = false;
        $product->slug = Product::uniqueSlug($data['slug'] ?: $data['name']);
        $this->applyCover($request, $product);
        $product->save();
        $this->syncImages($request, $product);
        $this->syncVariants($request, $product);
        Cache::forget('autoluz.home.page.v5');

        return response()->json(['product' => $product->fresh()->toCardArray(), 'message' => 'Produk dibuat.'], 201);
    }

    public function updateProduct(Request $request, int $id): JsonResponse
    {
        $store = $this->store($request);
        $product = Product::query()->where('store_id', $store->id)->whereKey($id)->firstOrFail();
        $data = $this->validatedProduct($request, $product);
        $product->fill($this->productPayload($data));
        if (! empty($data['slug'])) {
            $product->slug = Product::uniqueSlug($data['slug'], $product->id);
        }
        $this->applyCover($request, $product);
        $product->save();
        $this->syncImages($request, $product);
        $this->syncVariants($request, $product);
        Cache::forget('autoluz.home.page.v5');

        return response()->json(['product' => $product->fresh()->toCardArray(), 'message' => 'Produk disimpan.']);
    }

    public function destroyProduct(Request $request, int $id): JsonResponse
    {
        $store = $this->store($request);
        $product = Product::query()->where('store_id', $store->id)->whereKey($id)->firstOrFail();
        $product->delete();
        Cache::forget('autoluz.home.page.v5');

        return response()->json(['ok' => true]);
    }

    public function orders(Request $request): JsonResponse
    {
        $store = $this->store($request);
        $q = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));

        $orders = Order::query()
            ->where('store_id', $store->id)
            ->with(['user:id,name,email', 'store'])
            ->withCount('items')
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('number', 'like', $like)
                        ->orWhere('recipient_name', 'like', $like);
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderByDesc('id')
            ->paginate(20)
            ->through(fn (Order $order) => [
                ...$order->toArrayPublic(),
                'user' => $order->user ? [
                    'id' => $order->user->id,
                    'name' => $order->user->name,
                    'email' => $order->user->email,
                ] : null,
                'items_count' => $order->items_count,
            ]);

        return response()->json(['orders' => $orders]);
    }

    public function orderShow(Request $request, string $number): JsonResponse
    {
        $store = $this->store($request);
        $order = Order::query()->where('number', $number)->where('store_id', $store->id)->firstOrFail();
        $order->load(['items', 'user:id,name,email', 'store']);

        return response()->json([
            'order' => [
                ...$order->toArrayPublic(),
                'user' => $order->user ? [
                    'id' => $order->user->id,
                    'name' => $order->user->name,
                    'email' => $order->user->email,
                ] : null,
                'notes' => $order->notes,
            ],
        ]);
    }

    public function orderUpdate(Request $request, string $number): JsonResponse
    {
        $store = $this->store($request);
        $order = Order::query()->where('number', $number)->where('store_id', $store->id)->firstOrFail();

        $data = $request->validate([
            'status' => ['required', Rule::in([
                Order::STATUS_PAID,
                Order::STATUS_PACKED,
                Order::STATUS_SHIPPED,
                Order::STATUS_COMPLETED,
            ])],
            'tracking_number' => ['nullable', 'string', 'max:80'],
        ]);

        if ($order->status === Order::STATUS_PENDING) {
            return response()->json(['message' => 'Pesanan masih menunggu pembayaran pembeli.'], 422);
        }

        $allowed = [
            Order::STATUS_PAID => [Order::STATUS_PACKED],
            Order::STATUS_PACKED => [Order::STATUS_SHIPPED],
            Order::STATUS_SHIPPED => [Order::STATUS_COMPLETED],
            Order::STATUS_COMPLETED => [],
        ];

        $next = $data['status'];
        if ($next !== $order->status && ! in_array($next, $allowed[$order->status] ?? [], true)) {
            return response()->json(['message' => 'Status pesanan tidak valid.'], 422);
        }

        $order->status = $next;
        $order->tracking_number = $data['tracking_number'] ?? $order->tracking_number;
        $order->save();

        return response()->json(['order' => $order->fresh()->toArrayPublic(), 'message' => 'Pesanan diperbarui.']);
    }

    public function settings(Request $request, RajaOngkirService $rajaongkir): JsonResponse
    {
        $store = $this->store($request);
        $provinces = [];
        $error = null;
        try {
            if ($rajaongkir->configured()) {
                $provinces = $rajaongkir->provinces();
            } else {
                $error = 'RAJAONGKIR_API_KEY belum diisi.';
            }
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        }

        return response()->json([
            'store' => $store->toSettingsArray(),
            'provinces' => $provinces,
            'courier_options' => Store::courierOptions(),
            'rajaongkir_error' => $error,
            'rajaongkir_configured' => $rajaongkir->configured(),
        ]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $store = $this->store($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'tagline' => ['nullable', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:4000'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'pickup_address' => ['nullable', 'string', 'max:500'],
            'origin_province_id' => ['nullable', 'string', 'max:20'],
            'origin_province_name' => ['nullable', 'string', 'max:120'],
            'origin_city_id' => ['nullable', 'string', 'max:20'],
            'origin_city_name' => ['nullable', 'string', 'max:120'],
            'origin_district_id' => ['nullable', 'string', 'max:20'],
            'origin_district_name' => ['nullable', 'string', 'max:120'],
            'couriers' => ['required', 'array', 'min:1'],
            'couriers.*' => ['string', 'max:20'],
            'logo' => ['nullable', 'image', 'max:5120'],
            'remove_logo' => ['boolean'],
        ]);

        $store->fill([
            'name' => $data['name'],
            'tagline' => $data['tagline'] ?? null,
            'description' => $data['description'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'pickup_address' => $data['pickup_address'] ?? null,
            'origin_province_id' => $data['origin_province_id'] ?? null,
            'origin_province_name' => $data['origin_province_name'] ?? null,
            'origin_city_id' => $data['origin_city_id'] ?? null,
            'origin_city_name' => $data['origin_city_name'] ?? null,
            'origin_district_id' => $data['origin_district_id'] ?? null,
            'origin_district_name' => $data['origin_district_name'] ?? null,
            'couriers' => array_values($data['couriers']),
        ]);

        if ($request->boolean('remove_logo')) {
            $store->logo_path = null;
        }
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('stores', 'public');
            $store->logo_path = '/storage/'.$path;
        }
        $store->save();

        return response()->json(['store' => $store->fresh()->toSettingsArray(), 'message' => 'Pengaturan toko disimpan.']);
    }

    private function store(Request $request): Store
    {
        return $request->user()->ownedStore()->firstOrFail();
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

    private function validatedProduct(Request $request, ?Product $product = null): array
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
            'cover_image' => ['nullable', 'image', 'max:5120'],
            'remove_cover_image' => ['boolean'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'max:5120'],
            'remove_image_ids' => ['nullable', 'array'],
            'remove_image_ids.*' => ['integer'],
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

    private function productPayload(array $data): array
    {
        return [
            'shop_category_id' => $data['shop_category_id'] ?: null,
            'name' => $data['name'],
            'excerpt' => $data['excerpt'] ?? null,
            'description_html' => $data['description_html'] ?? null,
            'weight_grams' => (int) $data['weight_grams'],
            'status' => $data['status'],
            'published_at' => $data['published_at'] ?? ($data['status'] === 'published' ? now() : null),
            'sort_order' => 0,
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
            ProductImage::query()->where('product_id', $product->id)->whereIn('id', $removeIds)->delete();
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
    }

    private function syncVariants(Request $request, Product $product): void
    {
        $rows = collect($request->input('variants', []))->values();
        $keepIds = [];
        foreach ($rows as $index => $row) {
            $payload = [
                'sku' => trim((string) ($row['sku'] ?? '')) ?: null,
                'size' => trim((string) ($row['size'] ?? '')) ?: null,
                'color' => trim((string) ($row['color'] ?? '')) ?: null,
                'price' => (int) ($row['price'] ?? 0),
                'stock' => (int) ($row['stock'] ?? 0),
                'is_active' => filter_var($row['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'sort_order' => $index,
            ];
            if (! empty($row['id'])) {
                $variant = $product->variants()->whereKey((int) $row['id'])->first();
                if ($variant) {
                    $variant->update($payload);
                    $keepIds[] = $variant->id;
                    continue;
                }
            }
            $keepIds[] = $product->variants()->create($payload)->id;
        }
        $product->variants()->whereNotIn('id', $keepIds)->delete();
    }
}
