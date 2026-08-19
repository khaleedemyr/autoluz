<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\User;
use App\Services\RajaOngkirService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class StoreController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));

        $stores = Store::query()
            ->with('owner:id,name,email')
            ->withCount(['products', 'orders'])
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('name', 'like', $like)
                        ->orWhere('slug', 'like', $like)
                        ->orWhereHas('owner', fn ($owner) => $owner->where('email', 'like', $like)->orWhere('name', 'like', $like));
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderByDesc('is_official')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Store $store) => [
                ...$store->toSettingsArray(),
                'products_count' => $store->products_count,
                'orders_count' => $store->orders_count,
            ]);

        return Inertia::render('Admin/Stores/Index', [
            'stores' => $stores,
            'filters' => [
                'q' => $q,
                'status' => $status,
            ],
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function create(RajaOngkirService $rajaongkir): Response
    {
        return Inertia::render('Admin/Stores/Form', [
            'store' => null,
            'users' => $this->userOptions(),
            'provinces' => $this->provinces($rajaongkir),
            'cities' => [],
            'courierOptions' => Store::courierOptions(),
            'statuses' => $this->statusOptions(),
            'rajaongkir_error' => $this->rajaongkirError($rajaongkir),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $store = new Store($this->payload($data));
        $store->slug = Store::uniqueSlug($data['slug'] ?: $data['name']);
        $this->applyLogo($request, $store);
        $this->applyOfficial($store, (bool) ($data['is_official'] ?? false));
        $store->save();

        return redirect()
            ->route('admin.stores.edit', $store)
            ->with('success', 'Toko partner dibuat.');
    }

    public function edit(Store $store, RajaOngkirService $rajaongkir): Response
    {
        $store->load('owner:id,name,email');
        $cities = [];
        if ($store->origin_province_id && $rajaongkir->configured()) {
            try {
                $cities = $rajaongkir->cities($store->origin_province_id);
            } catch (RuntimeException) {
                $cities = [];
            }
        }

        return Inertia::render('Admin/Stores/Form', [
            'store' => $store->toSettingsArray(),
            'users' => $this->userOptions($store->user_id),
            'provinces' => $this->provinces($rajaongkir),
            'cities' => $cities,
            'courierOptions' => Store::courierOptions(),
            'statuses' => $this->statusOptions(),
            'rajaongkir_error' => $this->rajaongkirError($rajaongkir),
        ]);
    }

    public function update(Request $request, Store $store): RedirectResponse
    {
        $data = $this->validated($request, $store);
        $store->fill($this->payload($data));
        if (! empty($data['slug'])) {
            $store->slug = Store::uniqueSlug($data['slug'], $store->id);
        }
        $this->applyLogo($request, $store);
        $this->applyOfficial($store, (bool) ($data['is_official'] ?? false));
        $store->save();

        return back()->with('success', 'Toko partner disimpan.');
    }

    public function destroy(Store $store): RedirectResponse
    {
        if ($store->is_official) {
            return back()->with('success', 'Toko resmi tidak bisa dihapus.');
        }

        if ($store->products()->exists() || $store->orders()->exists()) {
            return back()->with('success', 'Toko masih punya produk atau pesanan. Tangguhkan saja.');
        }

        $store->delete();

        return redirect()
            ->route('admin.stores.index')
            ->with('success', 'Toko dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Store $store = null): array
    {
        return $request->validate([
            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
                Rule::unique('stores', 'user_id')->ignore($store?->id),
            ],
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:180', Rule::unique('stores', 'slug')->ignore($store?->id)],
            'tagline' => ['nullable', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:4000'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'pickup_address' => ['nullable', 'string', 'max:500'],
            'origin_province_id' => ['nullable', 'string', 'max:20'],
            'origin_province_name' => ['nullable', 'string', 'max:120'],
            'origin_city_id' => ['nullable', 'string', 'max:20'],
            'origin_city_name' => ['nullable', 'string', 'max:120'],
            'couriers' => ['nullable', 'array'],
            'couriers.*' => ['string', 'max:20'],
            'status' => ['required', Rule::in([Store::STATUS_PENDING, Store::STATUS_APPROVED, Store::STATUS_SUSPENDED])],
            'is_official' => ['boolean'],
            'logo' => ['nullable', 'image', 'max:5120'],
            'remove_logo' => ['boolean'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(array $data): array
    {
        return [
            'user_id' => $data['user_id'] ?: null,
            'name' => $data['name'],
            'tagline' => $data['tagline'] ?? null,
            'description' => $data['description'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'pickup_address' => $data['pickup_address'] ?? null,
            'origin_province_id' => $data['origin_province_id'] ?? null,
            'origin_province_name' => $data['origin_province_name'] ?? null,
            'origin_city_id' => $data['origin_city_id'] ?? null,
            'origin_city_name' => $data['origin_city_name'] ?? null,
            'couriers' => array_values($data['couriers'] ?? []),
            'status' => $data['status'],
        ];
    }

    private function applyLogo(Request $request, Store $store): void
    {
        if ($request->boolean('remove_logo')) {
            $store->logo_path = null;
        }

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('stores', 'public');
            $store->logo_path = '/storage/'.$path;
        }
    }

    private function applyOfficial(Store $store, bool $official): void
    {
        if ($official) {
            Store::query()->where('id', '!=', $store->id ?? 0)->update(['is_official' => false]);
            $store->is_official = true;
            $store->status = Store::STATUS_APPROVED;

            return;
        }

        if ($store->exists && $store->getOriginal('is_official')) {
            $store->is_official = true;

            return;
        }

        $store->is_official = false;
    }

    /**
     * @return list<array{id: int, name: string, email: string}>
     */
    private function userOptions(?int $currentId = null): array
    {
        $taken = Store::query()
            ->when($currentId, fn ($q) => $q->where('user_id', '!=', $currentId))
            ->whereNotNull('user_id')
            ->pluck('user_id');

        return User::query()
            ->whereNotIn('id', $taken)
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    private function provinces(RajaOngkirService $rajaongkir): array
    {
        try {
            return $rajaongkir->configured() ? $rajaongkir->provinces() : [];
        } catch (RuntimeException) {
            return [];
        }
    }

    private function rajaongkirError(RajaOngkirService $rajaongkir): ?string
    {
        if (! $rajaongkir->configured()) {
            return 'RAJAONGKIR_API_KEY belum diisi di .env.';
        }

        return null;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return [
            ['value' => Store::STATUS_PENDING, 'label' => 'Menunggu persetujuan'],
            ['value' => Store::STATUS_APPROVED, 'label' => 'Aktif'],
            ['value' => Store::STATUS_SUSPENDED, 'label' => 'Ditangguhkan'],
        ];
    }
}
