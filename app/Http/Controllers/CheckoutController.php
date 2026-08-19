<?php

namespace App\Http\Controllers;

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
        ]);

        $cart = $this->carts->current($request->user(), $request);
        $summary = $this->carts->summary($cart);

        if ($summary['items'] === []) {
            return response()->json(['message' => 'Keranjang kosong.', 'data' => []], 422);
        }

        try {
            $options = $this->rajaongkir->costs($data['city_id'], max(1, $summary['weight_grams']));
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage(), 'data' => []], 422);
        }

        return response()->json(['data' => $options]);
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
            'courier' => ['required', 'string', 'max:20'],
            'service' => ['required', 'string', 'max:40'],
            'description' => ['nullable', 'string', 'max:80'],
            'cost' => ['required', 'integer', 'min:0'],
            'etd' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();
        $address = $this->resolveAddress($request, $data);

        $cart = $this->carts->current($user, $request);
        $summary = $this->carts->summary($cart);
        $this->assertShipping($address->city_id, $summary['weight_grams'], $data);

        try {
            $order = $this->orders->checkout($user, $cart, $address, [
                'courier' => $data['courier'],
                'service' => $data['service'],
                'description' => $data['description'] ?? $data['service'],
                'cost' => (int) $data['cost'],
                'etd' => $data['etd'] ?? null,
            ], $data['notes'] ?? null);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['payment' => $e->getMessage()]);
        }

        return redirect()
            ->route('shop.orders.show', $order->number)
            ->with('snap_token', $order->snap_token)
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
     * @param  array<string, mixed>  $data
     */
    private function assertShipping(string $cityId, int $weight, array $data): void
    {
        try {
            $options = $this->rajaongkir->costs($cityId, max(1, $weight));
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['shipping' => $e->getMessage()]);
        }

        $match = collect($options)->first(fn ($row) => $row['courier'] === $data['courier']
            && $row['service'] === $data['service']
            && (int) $row['cost'] === (int) $data['cost']);

        if (! $match) {
            throw ValidationException::withMessages(['shipping' => 'Opsi ongkir sudah berubah. Hitung ulang.']);
        }
    }
}
