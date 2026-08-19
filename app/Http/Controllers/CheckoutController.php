<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\UserAddress;
use App\Services\CartService;
use App\Services\MidtransService;
use App\Services\RajaOngkirService;
use App\Services\ShopOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class CheckoutController extends Controller
{
    public function __construct(
        private CartService $carts,
        private RajaOngkirService $rajaongkir,
        private ShopOrderService $orders,
        private MidtransService $midtrans,
    ) {}

    public function show(Request $request): Response|RedirectResponse
    {
        $cart = $this->carts->current($request->user(), $request);
        $summary = $this->carts->summary($cart);

        if ($summary['items'] === []) {
            return redirect()->route('shop.cart')->with('success', 'Keranjang masih kosong.');
        }

        $addresses = $request->user()
            ->addresses()
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get()
            ->map->toArrayPublic()
            ->values()
            ->all();

        $provinces = [];
        $shippingError = null;
        try {
            if ($this->rajaongkir->configured()) {
                $provinces = $this->rajaongkir->provinces();
            } else {
                $shippingError = 'Pengiriman belum dikonfigurasi. Hubungi admin.';
            }
        } catch (RuntimeException $e) {
            $shippingError = $e->getMessage();
        }

        return Inertia::render('Shop/Checkout', [
            'cart' => $summary,
            'addresses' => $addresses,
            'provinces' => $provinces,
            'shipping_error' => $shippingError,
            'midtrans' => [
                'client_key' => $this->midtrans->clientKey(),
                'snap_url' => $this->midtrans->snapScriptUrl(),
                'configured' => $this->midtrans->configured(),
            ],
        ]);
    }

    public function cities(Request $request): JsonResponse
    {
        $provinceId = trim((string) $request->query('province_id', ''));
        if ($provinceId === '') {
            return response()->json(['data' => []]);
        }

        try {
            return response()->json(['data' => $this->rajaongkir->cities($provinceId)]);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage(), 'data' => []], 422);
        }
    }

    public function quote(Request $request): JsonResponse
    {
        $data = $request->validate([
            'city_id' => ['required', 'string', 'max:20'],
            'store_id' => ['nullable', 'integer'],
        ]);

        $cart = $this->carts->current($request->user(), $request);
        $summary = $this->carts->summary($cart);

        if ($summary['groups'] === []) {
            return response()->json(['message' => 'Keranjang kosong.', 'data' => []], 422);
        }

        $groups = collect($summary['groups']);
        if (! empty($data['store_id'])) {
            $groups = $groups->where('id', (int) $data['store_id'])->values();
        }

        $result = [];
        foreach ($groups as $group) {
            $storeId = (int) ($group['id'] ?? 0);
            $store = $storeId ? Store::query()->find($storeId) : null;
            $row = [
                'store_id' => $storeId,
                'origin_ready' => (bool) ($group['origin_ready'] ?? false),
                'error' => null,
                'options' => [],
            ];

            if (! $store || ! $store->originReady()) {
                $row['error'] = 'Toko belum mengatur kota asal pengiriman.';
                $result[] = $row;
                continue;
            }

            try {
                $row['options'] = $this->rajaongkir->costs(
                    $data['city_id'],
                    max(1, (int) $group['weight_grams']),
                    $store->courierList(),
                    $store->origin_city_id,
                );
                if ($row['options'] === []) {
                    $row['error'] = 'Tidak ada opsi ongkir dari toko ini.';
                }
            } catch (RuntimeException $e) {
                $row['error'] = $e->getMessage();
            }

            $result[] = $row;
        }

        return response()->json(['data' => $result]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'address_id' => ['nullable', 'integer'],
            'recipient_name' => ['required_without:address_id', 'nullable', 'string', 'max:120'],
            'phone' => ['required_without:address_id', 'nullable', 'string', 'max:30'],
            'address' => ['required_without:address_id', 'nullable', 'string', 'max:500'],
            'province_id' => ['required_without:address_id', 'nullable', 'string', 'max:20'],
            'province_name' => ['required_without:address_id', 'nullable', 'string', 'max:120'],
            'city_id' => ['required_without:address_id', 'nullable', 'string', 'max:20'],
            'city_name' => ['required_without:address_id', 'nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:12'],
            'save_address' => ['boolean'],
            'shippings' => ['required', 'array', 'min:1'],
            'shippings.*.store_id' => ['required', 'integer', 'exists:stores,id'],
            'shippings.*.courier' => ['required', 'string', 'max:20'],
            'shippings.*.service' => ['required', 'string', 'max:40'],
            'shippings.*.description' => ['nullable', 'string', 'max:80'],
            'shippings.*.cost' => ['required', 'integer', 'min:0'],
            'shippings.*.etd' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();
        $address = $this->resolveAddress($request, $data);

        $cart = $this->carts->current($user, $request);
        $summary = $this->carts->summary($cart);
        $this->assertShippings($address->city_id, $summary['groups'], $data['shippings']);

        try {
            $checkout = $this->orders->checkout($user, $cart, $address, $data['shippings'], $data['notes'] ?? null);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['payment' => $e->getMessage()]);
        }

        return redirect()
            ->route('shop.checkouts.show', $checkout->number)
            ->with('snap_token', $checkout->snap_token)
            ->with('success', 'Pesanan dibuat. Lanjutkan pembayaran.');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveAddress(Request $request, array $data): UserAddress
    {
        $user = $request->user();

        if (! empty($data['address_id'])) {
            return $user->addresses()->whereKey($data['address_id'])->firstOrFail();
        }

        $address = new UserAddress([
            'recipient_name' => $data['recipient_name'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'province_id' => $data['province_id'],
            'province_name' => $data['province_name'],
            'city_id' => $data['city_id'],
            'city_name' => $data['city_name'],
            'postal_code' => $data['postal_code'] ?? null,
        ]);

        if ($request->boolean('save_address')) {
            if ($user->addresses()->count() === 0) {
                $address->is_default = true;
            }
            $address->user_id = $user->id;
            $address->save();
        }

        $address->user_id = $user->id;

        return $address;
    }

    /**
     * @param  list<array<string, mixed>>  $groups
     * @param  list<array<string, mixed>>  $shippings
     */
    private function assertShippings(string $cityId, array $groups, array $shippings): void
    {
        $byStore = collect($shippings)->keyBy(fn ($row) => (int) $row['store_id']);

        foreach ($groups as $group) {
            $storeId = (int) ($group['id'] ?? 0);
            $store = $storeId ? Store::query()->find($storeId) : null;
            $name = $group['store']['name'] ?? 'Toko';
            $shipping = $byStore->get($storeId);

            if (! $store || ! $store->originReady() || ! $shipping) {
                throw ValidationException::withMessages([
                    'shipping' => 'Ongkir untuk '.$name.' belum lengkap.',
                ]);
            }

            if (! $this->orders->shippingMatchesStore($store, $cityId, (int) $group['weight_grams'], $shipping, $this->rajaongkir)) {
                throw ValidationException::withMessages([
                    'shipping' => 'Opsi ongkir '.$name.' sudah berubah. Hitung ulang.',
                ]);
            }
        }
    }
}
