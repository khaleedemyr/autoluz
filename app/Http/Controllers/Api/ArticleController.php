<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function index(): JsonResponse
    {
        $articles = Article::query()
            ->with('category')
            ->withCardStats()
            ->published()
            ->orderByDesc('published_at')
            ->paginate(12)
            ->through(fn (Article $article) => $article->toCardArray());

        return response()->json($articles);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $article = Article::query()
            ->with(['category', 'brands'])
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        $article->increment('views_count');
        $article->refresh();

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

        return response()->json([
            'article' => $article->toDetailArray(),
            'related' => $related,
            'comments' => $comments,
        ]);
    }

    public function comments(Request $request, string $slug): JsonResponse
    {
        if (filled($request->input('website'))) {
            return response()->json(['ok' => true]);
        }

        $article = Article::query()->published()->where('slug', $slug)->firstOrFail();
        $user = $request->user();

        $data = $request->validate([
            'name' => [$user ? 'nullable' : 'required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:160'],
            'body' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        Comment::query()->create([
            'article_id' => $article->id,
            'name' => $data['name'] ?? $user?->name,
            'email' => $data['email'] ?? $user?->email,
            'body' => $data['body'],
            'is_visible' => false,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Komentar terkirim dan menunggu moderasi admin.',
        ]);
    }

    public function category(string $slug): JsonResponse
    {
        $category = Category::query()->active()->where('slug', $slug)->firstOrFail();

        $articles = Article::query()
            ->with('category')
            ->withCardStats()
            ->published()
            ->where('category_id', $category->id)
            ->orderByDesc('published_at')
            ->paginate(12)
            ->through(fn (Article $article) => $article->toCardArray());

        return response()->json([
            'category' => [
                'id' => $category->id,
                'name' => $category->localizedName(),
                'slug' => $category->slug,
                'description' => $category->description,
            ],
            'articles' => $articles,
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        $articles = Article::query()
            ->with('category')
            ->withCardStats()
            ->published()
            ->search($q)
            ->orderByDesc('published_at')
            ->paginate(12)
            ->through(fn (Article $article) => $article->toCardArray());

        return response()->json([
            'q' => $q,
            'articles' => $articles,
        ]);
    }
}
