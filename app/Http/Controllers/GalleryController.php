<?php

namespace App\Http\Controllers;

use App\Models\Gallery;
use Inertia\Inertia;
use Inertia\Response;

class GalleryController extends Controller
{
    public function index(): Response
    {
        $galleries = Gallery::query()
            ->published()
            ->with(['images' => fn ($q) => $q->limit(1)])
            ->withCount('images')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(12)
            ->through(fn (Gallery $gallery) => $gallery->toCardArray());

        return Inertia::render('Galleries/Index', [
            'galleries' => $galleries,
        ]);
    }

    public function show(string $slug): Response
    {
        $gallery = Gallery::query()
            ->published()
            ->with('images')
            ->where('slug', $slug)
            ->firstOrFail();

        $related = Gallery::query()
            ->published()
            ->withCount('images')
            ->where('id', '!=', $gallery->id)
            ->orderByDesc('published_at')
            ->limit(4)
            ->get()
            ->map->toCardArray()
            ->values();

        return Inertia::render('Galleries/Show', [
            'gallery' => $gallery->toDetailArray(),
            'related' => $related,
        ]);
    }
}
