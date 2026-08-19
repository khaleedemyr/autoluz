<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Support\YoutubeFeed;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'articles' => Article::query()->count(),
                'published' => Article::query()->published()->count(),
                'categories' => Category::query()->count(),
                'videos' => YoutubeFeed::count(),
                'products' => Schema::hasTable('products') ? Product::query()->count() : 0,
                'orders_pending' => Schema::hasTable('orders')
                    ? Order::query()->where('status', Order::STATUS_PENDING)->count()
                    : 0,
                'orders_paid' => Schema::hasTable('orders')
                    ? Order::query()->where('status', Order::STATUS_PAID)->count()
                    : 0,
                'shop_revenue' => Schema::hasTable('orders')
                    ? (int) Order::query()->whereIn('status', [
                        Order::STATUS_PAID,
                        Order::STATUS_PACKED,
                        Order::STATUS_SHIPPED,
                        Order::STATUS_COMPLETED,
                    ])->sum('grand_total')
                    : 0,
            ],
            'latestArticles' => Article::query()
                ->with('category')
                ->orderByDesc('updated_at')
                ->limit(8)
                ->get()
                ->map(fn (Article $article) => [
                    'id' => $article->id,
                    'title' => $article->title,
                    'slug' => $article->slug,
                    'status' => $article->status,
                    'category' => $article->category?->name,
                    'updated_at' => optional($article->updated_at)?->toDateTimeString(),
                ]),
        ]);
    }
}
