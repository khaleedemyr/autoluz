<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Event;
use App\Models\Gallery;
use App\Support\YoutubeFeed;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        $payload = Cache::remember('autoluz.home.page.v2', 120, function () {
            $homeVideoLimit = max(4, min(24, (int) config('youtube.homepage_limit', 8)));
            $videos = array_slice(YoutubeFeed::playlist(), 0, $homeVideoLimit);

            $featuredArticles = Article::query()
                ->with('category')
                ->withCardStats()
                ->published()
                ->featured()
                ->orderByDesc('published_at')
                ->limit(5)
                ->get();

            // After WP import, is_featured is usually empty — fall back to latest.
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
            $featuredSide = $featuredArticles->slice(1, 3)->values()->map->toCardArray();

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
                ->values();

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

            $latestReviews = $latestReviewsQuery->get()->map->toCardArray()->values();

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
                ->values();

            $upcomingEvents = Event::query()
                ->published()
                ->upcoming()
                ->orderBy('starts_at')
                ->limit(3)
                ->get()
                ->map->toCardArray()
                ->values();

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
                ->values();

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
                ->values();

            $recentGalleries = Gallery::query()
                ->published()
                ->withCount('images')
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->limit(4)
                ->get()
                ->map->toCardArray()
                ->values();

            return [
                'videos' => $videos,
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
                // Decorative stage uses CSS only — avoid shipping multi-MB article PNGs.
                'stageBackgrounds' => [],
                'upcomingEvents' => $upcomingEvents,
                'brands' => [
                    'cars' => $cars,
                    'motos' => $motos,
                ],
                'recentGalleries' => $recentGalleries,
            ];
        });

        return Inertia::render('Home', $payload);
    }
}
