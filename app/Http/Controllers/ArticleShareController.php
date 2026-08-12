<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArticleShareController extends Controller
{
    public function store(Request $request, string $slug): JsonResponse
    {
        $request->validate([
            'channel' => ['nullable', 'string', 'max:40'],
        ]);

        $article = Article::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        $article->increment('shares_count');

        return response()->json([
            'shares_count' => (int) $article->fresh()->shares_count,
        ]);
    }
}
