<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Comment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ArticleController extends Controller
{
    public function index(): Response
    {
        $articles = Article::query()
            ->with('category')
            ->withCardStats()
            ->published()
            ->orderByDesc('published_at')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Article $article) => $article->toCardArray());

        return Inertia::render('Articles/Index', [
            'articles' => $articles,
        ]);
    }

    public function show(Request $request, string $slug): Response
    {
        $article = Article::query()
            ->with(['category', 'brands'])
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        $viewKey = 'viewed_article_'.$article->id;
        if (! $request->session()->has($viewKey)) {
            $article->increment('views_count');
            $article->refresh();
            $request->session()->put($viewKey, true);
        }

        $related = Article::query()
            ->with('category')
            ->withCardStats()
            ->published()
            ->where('id', '!=', $article->id)
            ->when($article->category_id, fn ($q) => $q->where('category_id', $article->category_id))
            ->orderByDesc('published_at')
            ->limit(4)
            ->get()
            ->map->toCardArray()
            ->values();

        $comments = Comment::query()
            ->where('article_id', $article->id)
            ->visible()
            ->orderByDesc('created_at')
            ->get()
            ->map->toPublicArray()
            ->values();

        return Inertia::render('Articles/Show', [
            'article' => $article->toDetailArray(),
            'related' => $related,
            'comments' => $comments,
        ]);
    }
}
