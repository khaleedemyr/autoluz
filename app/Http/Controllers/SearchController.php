<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));

        $articles = Article::query()
            ->with('category')
            ->withCardStats()
            ->published()
            ->search($q)
            ->orderByDesc('published_at')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Article $article) => $article->toCardArray());

        return Inertia::render('Search', [
            'q' => $q,
            'articles' => $articles,
        ]);
    }
}
