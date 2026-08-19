<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ShopCheckout;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MidtransService
{
    public function configured(): bool
    {
        return filled(config('shop.midtrans.server_key')) && filled(config('shop.midtrans.client_key'));
    }

    public function clientKey(): ?string
    {
        return config('shop.midtrans.client_key');
    }

    public function snapScriptUrl(): string
    {
        return config('shop.midtrans.is_production')
            ? 'https://app.midtrans.com/snap/snap.js'
            : 'https://app.sandbox.midtrans.com/snap/snap.js';
    }

    /**
     * @return array{token: string, redirect_url: string|null}
     */
    public function createSnapToken(Order $order): array
    {
        if (! $this->configured()) {
            throw new RuntimeException('Midtrans belum dikonfigurasi.');
        }

        $order->loadMissing(['items', 'user']);

        $itemDetails = $order->items->map(fn ($item) => [
            'id' => 'ITEM-'.$item->id,
            'price' => (int) $item->price,
            'quantity' => (int) $item->qty,
            'name' => mb_substr(trim($item->product_name.' '.$item->variant_label), 0, 50),
        ])->values()->all();

        if ($order->shipping_cost > 0) {
            $itemDetails[] = [
                'id' => 'SHIPPING',
                'price' => (int) $order->shipping_cost,
                'quantity' => 1,
                'name' => 'Ongkir '.$order->courier.' '.$order->courier_service,
            ];
        }

        $payload = [
            'transaction_details' => [
                'order_id' => $order->midtrans_order_id,
                'gross_amount' => (int) $order->grand_total,
            ],
            'item_details' => $itemDetails,
            'customer_details' => [
                'first_name' => $order->recipient_name,
                'email' => $order->user?->email,
                'phone' => $order->phone,
                'shipping_address' => [
                    'first_name' => $order->recipient_name,
                    'phone' => $order->phone,
                    'address' => $order->address,
                    'city' => $order->city_name,
                    'postal_code' => $order->postal_code,
                    'country_code' => 'IDN',
                ],
            ],
            'callbacks' => [
                'finish' => route('shop.orders.show', $order->number),
            ],
        ];

        $response = Http::timeout(25)
            ->withBasicAuth((string) config('shop.midtrans.server_key'), '')
            ->acceptJson()
            ->post($this->snapBase().'/snap/v1/transactions', $payload);

        if (! $response->successful() || empty($response->json('token'))) {
            throw new RuntimeException('Gagal membuat token pembayaran Midtrans.');
        }

        return [
            'token' => (string) $response->json('token'),
            'redirect_url' => $response->json('redirect_url'),
        ];
    }

    /**
     * @return array{token: string, redirect_url: string|null}
     */
    public function createSnapTokenForCheckout(ShopCheckout $checkout): array
    {
        if (! $this->configured()) {
            throw new RuntimeException('Midtrans belum dikonfigurasi.');
        }

        $checkout->loadMissing(['orders.items', 'orders.store', 'user']);

        $itemDetails = [];
        foreach ($checkout->orders as $order) {
            foreach ($order->items as $item) {
                $itemDetails[] = [
                    'id' => 'ITEM-'.$item->id,
                    'price' => (int) $item->price,
                    'quantity' => (int) $item->qty,
                    'name' => mb_substr(trim($item->product_name.' '.$item->variant_label), 0, 50),
                ];
            }

            if ($order->shipping_cost > 0) {
                $storeName = $order->store?->name ?: 'Toko';
                $itemDetails[] = [
                    'id' => 'SHIP-'.$order->id,
                    'price' => (int) $order->shipping_cost,
                    'quantity' => 1,
                    'name' => mb_substr('Ongkir '.$storeName.' '.$order->courier.' '.$order->courier_service, 0, 50),
                ];
            }
        }

        $first = $checkout->orders->first();

        $payload = [
            'transaction_details' => [
                'order_id' => $checkout->midtrans_order_id,
                'gross_amount' => (int) $checkout->grand_total,
            ],
            'item_details' => $itemDetails,
            'customer_details' => [
                'first_name' => $first?->recipient_name ?: $checkout->user?->name,
                'email' => $checkout->user?->email,
                'phone' => $first?->phone,
                'shipping_address' => [
                    'first_name' => $first?->recipient_name,
                    'phone' => $first?->phone,
                    'address' => $first?->address,
                    'city' => $first?->city_name,
                    'postal_code' => $first?->postal_code,
                    'country_code' => 'IDN',
                ],
            ],
            'callbacks' => [
                'finish' => route('shop.checkouts.show', $checkout->number),
            ],
        ];

        $response = Http::timeout(25)
            ->withBasicAuth((string) config('shop.midtrans.server_key'), '')
            ->acceptJson()
            ->post($this->snapBase().'/snap/v1/transactions', $payload);

        if (! $response->successful() || empty($response->json('token'))) {
            throw new RuntimeException('Gagal membuat token pembayaran Midtrans.');
        }

        return [
            'token' => (string) $response->json('token'),
            'redirect_url' => $response->json('redirect_url'),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function signatureValid(array $payload): bool
    {
        $orderId = (string) ($payload['order_id'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');
        $signature = (string) ($payload['signature_key'] ?? '');

        if ($orderId === '' || $signature === '') {
            return false;
        }

        $expected = hash('sha512', $orderId.$statusCode.$grossAmount.config('shop.midtrans.server_key'));

        return hash_equals($expected, $signature);
    }

    public function snapBase(): string
    {
        return config('shop.midtrans.is_production')
            ? 'https://app.midtrans.com'
            : 'https://app.sandbox.midtrans.com';
    }
}
