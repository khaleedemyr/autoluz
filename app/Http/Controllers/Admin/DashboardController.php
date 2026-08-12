<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Support\YoutubeFeed;
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
