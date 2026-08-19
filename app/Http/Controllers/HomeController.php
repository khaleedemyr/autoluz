<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Event;
use App\Models\Gallery;
use App\Models\Product;
use App\Support\YoutubeFeed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        // Plain PHP arrays only — never cache Eloquent/Support collections.
        $payload = Cache::remember('autoluz.home.page.v5', 120, fn () => $this->buildPayload());

        // If a bad/empty cache slipped in, rebuild once.
        if (! is_array($payload) || (empty($payload['featured']['main']) && empty($payload['popular']))) {
            Cache::forget('autoluz.home.page.v5');
            $payload = $this->buildPayload();
            if (! empty($payload['featured']['main']) || ! empty($payload['popular'])) {
                Cache::put('autoluz.home.page.v5', $payload, 120);
            }
        }

        return Inertia::render('Home', $payload);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildPayload(): array
    {
        $homeVideoLimit = max(4, min(24, (int) config('youtube.homepage_limit', 8)));
        $playlist = YoutubeFeed::playlist();
        $videos = array_values(array_slice($playlist, 0, $homeVideoLimit));
        $videosTotal = count($playlist);

        $featuredArticles = Article::query()
            ->with('category')
            ->withCardStats()
            ->published()
            ->featured()
            ->orderByDesc('published_at')
            ->limit(5)
            ->get();

        if ($featuredArticles->isEmpty()) {
            $featuredArticles = Article::query()
                ->with('category')
                ->withCardStats()
                ->published()
                ->orderByDesc('published_at')
                ->limit(5)
                ->get();
        }

        $featuredMain = $featuredArticles->first()?->toCardArray();
        $featuredSide = $featuredArticles->slice(1, 3)->values()->map->toCardArray()->all();

        $excludeIds = $featuredArticles->pluck('id')->all();

        $popular = Article::query()
            ->with('category')
            ->withCardStats()
            ->published()
            ->when($excludeIds, fn ($q) => $q->whereNotIn('id', $excludeIds))
            ->orderByDesc('published_at')
            ->limit(5)
            ->get()
            ->map->toCardArray()
            ->values()
            ->all();

        $reviewCategory = Category::query()
            ->active()
            ->where(function ($q) {
                $q->where('slug', 'reviews')
                    ->orWhere('slug', 'like', '%review%')
                    ->orWhere('name', 'like', '%Review%');
            })
            ->orderByRaw("CASE WHEN slug = 'reviews' THEN 0 ELSE 1 END")
            ->first();

        $latestReviewsQuery = Article::query()
            ->with('category')
            ->withCardStats()
            ->published()
            ->orderByDesc('published_at')
            ->limit(5);

        if ($reviewCategory) {
            $latestReviewsQuery->where('category_id', $reviewCategory->id);
        }

        $latestReviews = $latestReviewsQuery->get()->map->toCardArray()->values()->all();

        $ticker = Article::query()
            ->published()
            ->orderByDesc('published_at')
            ->limit(8)
            ->get(['id', 'slug', 'title'])
            ->map(fn (Article $a) => [
                'id' => $a->id,
                'slug' => $a->slug,
                'title' => $a->title,
            ])
            ->values()
            ->all();

        $upcomingEvents = Event::query()
            ->published()
            ->upcoming()
            ->orderBy('starts_at')
            ->limit(3)
            ->get()
            ->map->toCardArray()
            ->values()
            ->all();

        $cars = Brand::query()
            ->active()
            ->cars()
            ->withCount([
                'articles' => fn ($q) => $q->published(),
                'vehicles' => fn ($q) => $q->published(),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(8)
            ->get()
            ->map->toCardArray()
            ->values()
            ->all();

        $motos = Brand::query()
            ->active()
            ->motos()
            ->withCount([
                'articles' => fn ($q) => $q->published(),
                'vehicles' => fn ($q) => $q->published(),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(8)
            ->get()
            ->map->toCardArray()
            ->values()
            ->all();

        $recentGalleries = Gallery::query()
            ->published()
            ->withCount('images')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(4)
            ->get()
            ->map->toCardArray()
            ->values()
            ->all();

        $shopProducts = [];
        if (Schema::hasTable('products')) {
            $shopProducts = Product::query()
                ->published()
                ->with(['category', 'variants', 'images'])
                ->orderByDesc('featured')
                ->orderByDesc('published_at')
                ->limit(4)
                ->get()
                ->map->toCardArray()
                ->values()
                ->all();
        }

        return [
            'videos' => $videos,
            'videosMeta' => [
                'total' => $videosTotal,
                'initial' => count($videos),
                'page_size' => $homeVideoLimit,
                'has_more' => $videosTotal > count($videos),
            ],
            'youtubeChannel' => [
                'id' => config('youtube.channel_id'),
                'name' => config('youtube.channel_name'),
                'url' => config('youtube.channel_url'),
            ],
            'featured' => [
                'main' => $featuredMain,
                'side' => $featuredSide,
            ],
            'popular' => $popular,
            'latestReviews' => $latestReviews,
            'ticker' => $ticker,
            'stageBackgrounds' => [],
            'upcomingEvents' => $upcomingEvents,
            'brands' => [
                'cars' => $cars,
                'motos' => $motos,
            ],
            'recentGalleries' => $recentGalleries,
            'shopProducts' => $shopProducts,
        ];
    }

    public function videosFeed(Request $request): JsonResponse
    {
        $offset = max(0, (int) $request->query('offset', 0));
        $defaultLimit = max(4, min(24, (int) config('youtube.homepage_limit', 8)));
        $limit = max(1, min(24, (int) $request->query('limit', $defaultLimit)));

        $playlist = YoutubeFeed::playlist();
        $total = count($playlist);
        $data = array_values(array_slice($playlist, $offset, $limit));
        $nextOffset = $offset + count($data);

        return response()->json([
            'data' => $data,
            'offset' => $offset,
            'next_offset' => $nextOffset,
            'limit' => $limit,
            'total' => $total,
            'has_more' => $nextOffset < $total,
        ]);
    }
}
