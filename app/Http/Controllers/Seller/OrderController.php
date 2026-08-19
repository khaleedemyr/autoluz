<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $store = $request->user()->ownedStore()->firstOrFail();
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
                        ->orWhere('recipient_name', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->orWhere('tracking_number', 'like', $like);
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
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
            'mode' => 'seller',
            'orders' => $orders,
            'filters' => [
                'q' => $q,
                'status' => $status,
            ],
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function show(Request $request, Order $order): Response
    {
        $this->authorizeStore($request, $order);
        $order->load(['items', 'user:id,name,email', 'store']);

        return Inertia::render('Admin/Orders/Show', [
            'mode' => 'seller',
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

    public function update(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeStore($request, $order);

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
            return back()->with('success', 'Pesanan masih menunggu pembayaran pembeli.');
        }

        $allowed = [
            Order::STATUS_PAID => [Order::STATUS_PACKED],
            Order::STATUS_PACKED => [Order::STATUS_SHIPPED],
            Order::STATUS_SHIPPED => [Order::STATUS_COMPLETED],
            Order::STATUS_COMPLETED => [],
        ];

        $next = $data['status'];
        if ($next !== $order->status && ! in_array($next, $allowed[$order->status] ?? [], true)) {
            return back()->with('success', 'Status pesanan tidak valid.');
        }

        $order->status = $next;
        $order->tracking_number = $data['tracking_number'] ?? $order->tracking_number;
        $order->save();

        return back()->with('success', 'Pesanan diperbarui.');
    }

    private function authorizeStore(Request $request, Order $order): void
    {
        abort_unless($order->store_id === $request->user()->ownedStore()->value('id'), 403);
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
