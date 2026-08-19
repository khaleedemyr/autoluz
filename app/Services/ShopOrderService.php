<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\ShopCheckout;
use App\Models\Store;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ShopOrderService
{
    public function __construct(
        private CartService $carts,
        private MidtransService $midtrans,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $shippings
     */
    public function checkout(User $user, Cart $cart, UserAddress $address, array $shippings, ?string $notes = null): ShopCheckout
    {
        $summary = $this->carts->summary($cart);
        if ($summary['groups'] === []) {
            throw ValidationException::withMessages(['cart' => 'Keranjang kosong.']);
        }

        $shippingByStore = collect($shippings)->keyBy(fn ($row) => (int) ($row['store_id'] ?? 0));

        foreach ($summary['groups'] as $group) {
            $storeId = (int) ($group['id'] ?? 0);
            $storeName = $group['store']['name'] ?? 'Toko';
            if (! $storeId || empty($group['origin_ready'])) {
                throw ValidationException::withMessages([
                    'shipping' => 'Toko '.$storeName.' belum siap mengirim. Pilih kota asal di pengaturan toko.',
                ]);
            }
            if (! $shippingByStore->has($storeId)) {
                throw ValidationException::withMessages([
                    'shipping' => 'Pilih ongkir untuk '.$storeName.'.',
                ]);
            }
        }

        $shippingTotal = (int) collect($summary['groups'])->sum(function ($group) use ($shippingByStore) {
            return (int) ($shippingByStore->get((int) $group['id'])['cost'] ?? 0);
        });

        return DB::transaction(function () use ($user, $cart, $address, $notes, $summary, $shippingByStore, $shippingTotal) {
            $cart->load(['items.product.store', 'items.variant']);

            foreach ($cart->items as $item) {
                $variant = ProductVariant::query()
                    ->whereKey($item->product_variant_id)
                    ->lockForUpdate()
                    ->first();

                if (! $variant || ! $variant->is_active || $variant->stock < $item->qty) {
                    throw ValidationException::withMessages([
                        'cart' => 'Stok '.($item->product?->name ?? 'produk').' tidak mencukupi.',
                    ]);
                }

                $store = $item->product?->store;
                if (! $store || ! $store->isApproved() || ! $store->originReady()) {
                    throw ValidationException::withMessages([
                        'cart' => 'Produk '.($item->product?->name ?? '').' tidak bisa dipesan dari toko ini.',
                    ]);
                }
            }

            $checkout = ShopCheckout::query()->create([
                'user_id' => $user->id,
                'number' => ShopCheckout::nextNumber(),
                'status' => ShopCheckout::STATUS_PENDING,
                'subtotal' => $summary['subtotal'],
                'shipping_cost' => $shippingTotal,
                'grand_total' => $summary['subtotal'] + $shippingTotal,
                'midtrans_order_id' => ShopCheckout::makeMidtransOrderId(),
                'notes' => $notes,
            ]);

            $grouped = $cart->items->groupBy(fn ($item) => (int) $item->product->store_id);

            foreach ($grouped as $storeId => $groupItems) {
                $shipping = $shippingByStore->get((int) $storeId) ?? [];
                $subtotal = (int) $groupItems->sum(fn ($item) => $item->lineTotal());
                $weight = (int) $groupItems->sum(fn ($item) => ((int) ($item->product?->weight_grams ?? 0)) * $item->qty);
                $cost = (int) ($shipping['cost'] ?? 0);

                $order = Order::query()->create([
                    'user_id' => $user->id,
                    'store_id' => $storeId,
                    'checkout_id' => $checkout->id,
                    'number' => Order::nextNumber(),
                    'status' => Order::STATUS_PENDING,
                    'subtotal' => $subtotal,
                    'shipping_cost' => $cost,
                    'grand_total' => $subtotal + $cost,
                    'weight_grams' => $weight,
                    'courier' => $shipping['courier'] ?? null,
                    'courier_service' => $shipping['service'] ?? null,
                    'courier_service_name' => $shipping['description'] ?? null,
                    'etd' => $shipping['etd'] ?? null,
                    'recipient_name' => $address->recipient_name,
                    'phone' => $address->phone,
                    'address' => $address->address,
                    'province_id' => $address->province_id,
                    'province_name' => $address->province_name,
                    'city_id' => $address->city_id,
                    'city_name' => $address->city_name,
                    'postal_code' => $address->postal_code,
                    'notes' => $notes,
                ]);

                foreach ($groupItems as $item) {
                    $variant = ProductVariant::query()->whereKey($item->product_variant_id)->lockForUpdate()->first();
                    $product = $item->product;

                    OrderItem::query()->create([
                        'order_id' => $order->id,
                        'product_id' => $item->product_id,
                        'product_variant_id' => $item->product_variant_id,
                        'product_name' => $product?->name ?? 'Produk',
                        'variant_label' => $variant?->label(),
                        'sku' => $variant?->sku,
                        'price' => (int) ($variant?->price ?? 0),
                        'qty' => $item->qty,
                        'weight_grams' => (int) ($product?->weight_grams ?? 0) * $item->qty,
                        'image_url' => $product?->cover_image_url,
                    ]);

                    $variant->decrement('stock', $item->qty);
                }

                $order->update(['stock_reserved_at' => now()]);
            }

            $this->carts->clear($cart);

            $fresh = $checkout->fresh(['orders.items', 'orders.store', 'user']);
            $snap = $this->midtrans->createSnapTokenForCheckout($fresh);
            $fresh->update(['snap_token' => $snap['token']]);

            return $fresh->fresh(['orders.items', 'orders.store']);
        });
    }

    public function ensureSnapToken(Order $order): string
    {
        if ($order->checkout_id) {
            $checkout = $order->relationLoaded('checkout') ? $order->checkout : $order->checkout()->first();
            if (! $checkout) {
                throw ValidationException::withMessages(['order' => 'Pembayaran gabungan tidak ditemukan.']);
            }

            return $this->ensureCheckoutSnapToken($checkout);
        }

        if (! $order->canPay()) {
            throw ValidationException::withMessages(['order' => 'Pesanan ini tidak bisa dibayar.']);
        }

        if ($order->snap_token) {
            return $order->snap_token;
        }

        $snap = $this->midtrans->createSnapToken($order->load(['items', 'user']));
        $order->update(['snap_token' => $snap['token']]);

        return $snap['token'];
    }

    public function ensureCheckoutSnapToken(ShopCheckout $checkout): string
    {
        if (! $checkout->canPay()) {
            throw ValidationException::withMessages(['checkout' => 'Pembayaran ini tidak bisa dilanjutkan.']);
        }

        if ($checkout->snap_token) {
            return $checkout->snap_token;
        }

        $snap = $this->midtrans->createSnapTokenForCheckout($checkout->load(['orders.items', 'orders.store', 'user']));
        $checkout->update(['snap_token' => $snap['token']]);

        return $snap['token'];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handleNotification(array $payload): ShopCheckout|Order|null
    {
        if (! $this->midtrans->signatureValid($payload)) {
            throw new RuntimeException('Signature Midtrans tidak valid.');
        }

        $midtransId = (string) ($payload['order_id'] ?? '');
        $transaction = (string) ($payload['transaction_status'] ?? '');
        $fraud = (string) ($payload['fraud_status'] ?? '');
        $paymentType = $payload['payment_type'] ?? null;

        $checkout = ShopCheckout::query()->where('midtrans_order_id', $midtransId)->first();
        if ($checkout) {
            return $this->applyCheckoutNotification($checkout, $transaction, $fraud, $paymentType);
        }

        $order = Order::query()->where('midtrans_order_id', $midtransId)->first();
        if (! $order) {
            return null;
        }

        return DB::transaction(function () use ($order, $transaction, $fraud, $paymentType) {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->first();

            return $this->applyOrderTransaction($locked, $transaction, $fraud, $paymentType);
        });
    }

    public function markPaid(Order $order, ?string $paymentType = null): void
    {
        if ($order->status !== Order::STATUS_PENDING) {
            return;
        }

        $order->update([
            'status' => Order::STATUS_PAID,
            'paid_at' => now(),
            'payment_type' => $paymentType,
        ]);
    }

    public function markCheckoutPaid(ShopCheckout $checkout, ?string $paymentType = null): void
    {
        if ($checkout->status !== ShopCheckout::STATUS_PENDING) {
            return;
        }

        $checkout->update([
            'status' => ShopCheckout::STATUS_PAID,
            'paid_at' => now(),
            'payment_type' => $paymentType,
        ]);

        $checkout->loadMissing('orders');
        foreach ($checkout->orders as $order) {
            $this->markPaid($order, $paymentType);
        }
    }

    public function releaseIfPending(Order $order, string $status): void
    {
        if ($order->status !== Order::STATUS_PENDING) {
            return;
        }

        $this->restoreStock($order);
        $order->update([
            'status' => $status,
            'stock_reserved_at' => null,
        ]);
    }

    public function restoreStock(Order $order): void
    {
        if (! $order->stock_reserved_at) {
            return;
        }

        $order->loadMissing('items');
        foreach ($order->items as $item) {
            if (! $item->product_variant_id) {
                continue;
            }
            ProductVariant::query()
                ->whereKey($item->product_variant_id)
                ->increment('stock', $item->qty);
        }
    }

    /**
     * @param  array<string, mixed>  $shipping
     */
    public function shippingMatchesStore(Store $store, string $cityId, int $weight, array $shipping, RajaOngkirService $rajaongkir): bool
    {
        try {
            $options = $rajaongkir->costs(
                $cityId,
                max(1, $weight),
                $store->courierList(),
                $store->origin_city_id,
            );
        } catch (RuntimeException) {
            return false;
        }

        return (bool) collect($options)->first(fn ($row) => $row['courier'] === ($shipping['courier'] ?? null)
            && $row['service'] === ($shipping['service'] ?? null)
            && (int) $row['cost'] === (int) ($shipping['cost'] ?? -1));
    }

    private function applyCheckoutNotification(ShopCheckout $checkout, string $transaction, string $fraud, ?string $paymentType): ShopCheckout
    {
        return DB::transaction(function () use ($checkout, $transaction, $fraud, $paymentType) {
            $locked = ShopCheckout::query()->whereKey($checkout->id)->lockForUpdate()->first();
            $locked->load('orders');

            if (in_array($transaction, ['capture', 'settlement'], true)) {
                if ($transaction === 'capture' && $fraud === 'challenge') {
                    return $locked;
                }
                $this->markCheckoutPaid($locked, $paymentType);

                return $locked->fresh(['orders']);
            }

            if (in_array($transaction, ['cancel', 'deny'], true)) {
                $this->releaseCheckoutIfPending($locked, ShopCheckout::STATUS_CANCELLED, Order::STATUS_CANCELLED);

                return $locked->fresh(['orders']);
            }

            if ($transaction === 'expire') {
                $this->releaseCheckoutIfPending($locked, ShopCheckout::STATUS_EXPIRED, Order::STATUS_EXPIRED);

                return $locked->fresh(['orders']);
            }

            return $locked;
        });
    }

    private function releaseCheckoutIfPending(ShopCheckout $checkout, string $checkoutStatus, string $orderStatus): void
    {
        if ($checkout->status !== ShopCheckout::STATUS_PENDING) {
            return;
        }

        foreach ($checkout->orders as $order) {
            $this->releaseIfPending($order, $orderStatus);
        }

        $checkout->update(['status' => $checkoutStatus]);
    }

    private function applyOrderTransaction(Order $order, string $transaction, string $fraud, ?string $paymentType): Order
    {
        if (in_array($transaction, ['capture', 'settlement'], true)) {
            if ($transaction === 'capture' && $fraud === 'challenge') {
                return $order;
            }
            $this->markPaid($order, $paymentType);

            return $order->fresh();
        }

        if (in_array($transaction, ['cancel', 'deny'], true)) {
            $this->releaseIfPending($order, Order::STATUS_CANCELLED);

            return $order->fresh();
        }

        if ($transaction === 'expire') {
            $this->releaseIfPending($order, Order::STATUS_EXPIRED);

            return $order->fresh();
        }

        return $order;
    }
}
