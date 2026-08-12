<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Article;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BrandController extends Controller
{
    public function index(): Response
    {
        $map = fn (Brand $brand) => $brand->toCardArray();

        $cars = Brand::query()
            ->active()
            ->cars()
            ->withCount([
                'articles' => fn ($q) => $q->published(),
                'vehicles' => fn ($q) => $q->published(),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map($map)
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
            ->get()
            ->map($map)
            ->values();

        return Inertia::render('Brands/Index', [
            'cars' => $cars,
            'motos' => $motos,
        ]);
    }

    public function show(Request $request, string $slug): Response
    {
        $brand = Brand::query()
            ->active()
            ->withCount([
                'articles as articles_count' => fn ($q) => $q->published(),
                'vehicles as vehicles_count' => fn ($q) => $q->published(),
            ])
            ->where('slug', $slug)
            ->firstOrFail();

        $tab = $request->query('tab') === 'articles' ? 'articles' : 'lineup';

        $vehicles = Vehicle::query()
            ->with('brand')
            ->withCount('images')
            ->published()
            ->where('brand_id', $brand->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map->toCardArray()
            ->values();

        $articles = Article::query()
            ->with(['category', 'brands'])
            ->withCardStats()
            ->published()
            ->whereHas('brands', fn ($q) => $q->where('brands.id', $brand->id))
            ->orderByDesc('published_at')
            ->paginate(9)
            ->withQueryString()
            ->through(fn (Article $article) => $article->toCardArray());

        return Inertia::render('Brands/Show', [
            'brand' => $brand->toCardArray(),
            'vehicles' => $vehicles,
            'articles' => $articles,
            'tab' => $tab,
        ]);
    }

    public function feed(Request $request): Response
    {
        $ids = collect(explode(',', (string) $request->query('ids', '')))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->take(20)
            ->values()
            ->all();

        $brands = Brand::query()
            ->active()
            ->when($ids !== [], fn ($q) => $q->whereIn('id', $ids))
            ->when($ids === [], fn ($q) => $q->whereRaw('1 = 0'))
            ->orderBy('name')
            ->get()
            ->map->toCardArray()
            ->values();

        $articles = Article::query()
            ->with(['category', 'brands'])
            ->withCardStats()
            ->published()
            ->when($ids !== [], fn ($q) => $q->whereHas('brands', fn ($b) => $b->whereIn('brands.id', $ids)))
            ->when($ids === [], fn ($q) => $q->whereRaw('1 = 0'))
            ->orderByDesc('published_at')
            ->limit(8)
            ->get()
            ->map->toCardArray()
            ->values();

        return Inertia::render('Brands/Following', [
            'brands' => $brands,
            'articles' => $articles,
            'ids' => $ids,
        ]);
    }
}
