<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Store;
use App\Services\ShopOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));
        $storeId = (int) $request->query('store_id', 0);

        $orders = Order::query()
            ->with(['user:id,name,email', 'store'])
            ->withCount('items')
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('number', 'like', $like)
                        ->orWhere('recipient_name', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->orWhere('tracking_number', 'like', $like);
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($storeId > 0, fn ($query) => $query->where('store_id', $storeId))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Order $order) => [
                ...$order->toArrayPublic(),
                'user' => $order->user ? [
                    'id' => $order->user->id,
                    'name' => $order->user->name,
                    'email' => $order->user->email,
                ] : null,
                'items_count' => $order->items_count,
            ]);

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
            'filters' => [
                'q' => $q,
                'status' => $status,
                'store_id' => $storeId > 0 ? $storeId : '',
            ],
            'stores' => Store::query()->orderByDesc('is_official')->orderBy('name')->get(['id', 'name'])->map(fn (Store $store) => [
                'id' => $store->id,
                'name' => $store->name,
            ])->values()->all(),
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function show(Order $order): Response
    {
        $order->load(['items', 'user:id,name,email', 'store']);

        return Inertia::render('Admin/Orders/Show', [
            'order' => [
                ...$order->toArrayPublic(),
                'user' => $order->user ? [
                    'id' => $order->user->id,
                    'name' => $order->user->name,
                    'email' => $order->user->email,
                ] : null,
                'notes' => $order->notes,
                'payment_type' => $order->payment_type,
            ],
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function update(Request $request, Order $order, ShopOrderService $orders): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in([
                Order::STATUS_PENDING,
                Order::STATUS_PAID,
                Order::STATUS_PACKED,
                Order::STATUS_SHIPPED,
                Order::STATUS_COMPLETED,
                Order::STATUS_CANCELLED,
                Order::STATUS_EXPIRED,
            ])],
            'tracking_number' => ['nullable', 'string', 'max:80'],
        ]);

        $next = $data['status'];

        if ($order->status === Order::STATUS_PENDING && in_array($next, [Order::STATUS_CANCELLED, Order::STATUS_EXPIRED], true)) {
            $orders->releaseIfPending($order, $next);
        } elseif ($order->status === Order::STATUS_PENDING && $next === Order::STATUS_PAID) {
            $orders->markPaid($order);
        } else {
            $order->status = $next;
        }

        $order->tracking_number = $data['tracking_number'] ?? $order->tracking_number;
        $order->save();

        return back()->with('success', 'Pesanan diperbarui.');
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return [
            ['value' => Order::STATUS_PENDING, 'label' => 'Menunggu pembayaran'],
            ['value' => Order::STATUS_PAID, 'label' => 'Dibayar'],
            ['value' => Order::STATUS_PACKED, 'label' => 'Dikemas'],
            ['value' => Order::STATUS_SHIPPED, 'label' => 'Dikirim'],
            ['value' => Order::STATUS_COMPLETED, 'label' => 'Selesai'],
            ['value' => Order::STATUS_CANCELLED, 'label' => 'Dibatalkan'],
            ['value' => Order::STATUS_EXPIRED, 'label' => 'Kedaluwarsa'],
        ];
    }
}
