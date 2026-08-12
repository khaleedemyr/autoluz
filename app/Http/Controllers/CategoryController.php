<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function show(string $slug): Response
    {
        $category = Category::query()
            ->active()
            ->where('slug', $slug)
            ->firstOrFail();

        $articles = Article::query()
            ->with('category')
            ->withCardStats()
            ->published()
            ->where('category_id', $category->id)
            ->orderByDesc('published_at')
            ->paginate(12)
            ->through(fn (Article $article) => $article->toCardArray());

        return Inertia::render('Categories/Show', [
            'category' => [
                'id' => $category->id,
                'name' => $category->localizedName(),
                'slug' => $category->slug,
                'description' => $category->description,
            ],
            'articles' => $articles,
        ]);
    }
}
