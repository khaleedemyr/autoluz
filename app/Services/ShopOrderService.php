<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
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
     * @param  array<string, mixed>  $shipping
     */
    public function checkout(User $user, Cart $cart, UserAddress $address, array $shipping, ?string $notes = null): Order
    {
        $summary = $this->carts->summary($cart);
        if ($summary['items'] === []) {
            throw ValidationException::withMessages(['cart' => 'Keranjang kosong.']);
        }

        $cost = (int) ($shipping['cost'] ?? 0);
        if ($cost < 0) {
            throw ValidationException::withMessages(['shipping' => 'Ongkir tidak valid.']);
        }

        return DB::transaction(function () use ($user, $cart, $address, $shipping, $notes, $summary, $cost) {
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
            }

            $order = Order::query()->create([
                'user_id' => $user->id,
                'number' => Order::nextNumber(),
                'status' => Order::STATUS_PENDING,
                'subtotal' => $summary['subtotal'],
                'shipping_cost' => $cost,
                'grand_total' => $summary['subtotal'] + $cost,
                'weight_grams' => $summary['weight_grams'],
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
                'midtrans_order_id' => Order::makeMidtransOrderId(),
                'notes' => $notes,
            ]);

            foreach ($cart->items as $item) {
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
            $this->carts->clear($cart);

            $snap = $this->midtrans->createSnapToken($order->fresh(['items', 'user']));
            $order->update(['snap_token' => $snap['token']]);

            return $order->fresh(['items']);
        });
    }

    public function ensureSnapToken(Order $order): string
    {
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

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handleNotification(array $payload): ?Order
    {
        if (! $this->midtrans->signatureValid($payload)) {
            throw new RuntimeException('Signature Midtrans tidak valid.');
        }

        $midtransId = (string) ($payload['order_id'] ?? '');
        $order = Order::query()->where('midtrans_order_id', $midtransId)->first();
        if (! $order) {
            return null;
        }

        $transaction = (string) ($payload['transaction_status'] ?? '');
        $fraud = (string) ($payload['fraud_status'] ?? '');
        $paymentType = $payload['payment_type'] ?? null;

        return DB::transaction(function () use ($order, $transaction, $fraud, $paymentType) {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->first();

            if (in_array($transaction, ['capture', 'settlement'], true)) {
                if ($transaction === 'capture' && $fraud === 'challenge') {
                    return $locked;
                }
                $this->markPaid($locked, $paymentType);

                return $locked->fresh();
            }

            if (in_array($transaction, ['cancel', 'deny'], true)) {
                $this->releaseIfPending($locked, Order::STATUS_CANCELLED);

                return $locked->fresh();
            }

            if ($transaction === 'expire') {
                $this->releaseIfPending($locked, Order::STATUS_EXPIRED);

                return $locked->fresh();
            }

            return $locked;
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
}
