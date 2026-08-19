<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $store = $request->user()->ownedStore()->firstOrFail();

        return Inertia::render('Seller/Dashboard', [
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

    public static function storeOf(Request $request): Store
    {
        return $request->user()->ownedStore()->firstOrFail();
    }
}
