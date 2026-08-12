<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use App\Models\GalleryImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class GalleryController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));

        $galleries = Gallery::query()
            ->withCount('images')
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';
                $query->where('title', 'like', $like);
            })
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Gallery $gallery) => [
                ...$gallery->toCardArray(),
                'status' => $gallery->status,
            ]);

        return Inertia::render('Admin/Galleries/Index', [
            'galleries' => $galleries,
            'filters' => ['q' => $q],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Galleries/Form', [
            'gallery' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $gallery = new Gallery($this->payload($data));
        $gallery->slug = Gallery::uniqueSlug($data['title']);
        $this->applyCover($request, $gallery);
        $gallery->save();
        $this->syncImages($request, $gallery);

        return redirect()
            ->route('admin.galleries.edit', $gallery)
            ->with('success', 'Galeri dibuat.');
    }

    public function edit(Gallery $gallery): Response
    {
        $gallery->load('images');

        return Inertia::render('Admin/Galleries/Form', [
            'gallery' => [
                'id' => $gallery->id,
                'title' => $gallery->title,
                'slug' => $gallery->slug,
                'excerpt' => $gallery->excerpt,
                'cover_image_url' => $gallery->toCardArray()['cover_image_url'],
                'status' => $gallery->status,
                'published_at' => optional($gallery->published_at)?->format('Y-m-d\TH:i'),
                'sort_order' => $gallery->sort_order,
                'images' => $gallery->images->map->toArrayPublic()->values()->all(),
            ],
        ]);
    }

    public function update(Request $request, Gallery $gallery): RedirectResponse
    {
        $data = $this->validated($request, $gallery);
        $gallery->fill($this->payload($data));
        if (! empty($data['slug'])) {
            $gallery->slug = Gallery::uniqueSlug($data['slug'], $gallery->id);
        }
        $this->applyCover($request, $gallery);
        $gallery->save();
        $this->syncImages($request, $gallery);

        return redirect()
            ->route('admin.galleries.edit', $gallery)
            ->with('success', 'Galeri disimpan.');
    }

    public function destroy(Gallery $gallery): RedirectResponse
    {
        $gallery->delete();

        return redirect()
            ->route('admin.galleries.index')
            ->with('success', 'Galeri dihapus.');
    }

    private function validated(Request $request, ?Gallery $gallery = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:220', Rule::unique('galleries', 'slug')->ignore($gallery?->id)],
            'excerpt' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'published_at' => ['nullable', 'date'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'cover_image' => ['nullable', 'image', 'max:5120'],
            'remove_cover_image' => ['boolean'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'max:5120'],
            'remove_image_ids' => ['nullable', 'array'],
            'remove_image_ids.*' => ['integer'],
            'captions' => ['nullable', 'array'],
            'captions.*' => ['nullable', 'string', 'max:255'],
        ]);
    }

    private function payload(array $data): array
    {
        return [
            'title' => $data['title'],
            'excerpt' => $data['excerpt'] ?? null,
            'status' => $data['status'],
            'published_at' => $data['published_at'] ?? ($data['status'] === 'published' ? now() : null),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }

    private function applyCover(Request $request, Gallery $gallery): void
    {
        if ($request->boolean('remove_cover_image')) {
            $gallery->cover_image_url = null;
        }

        if ($request->hasFile('cover_image')) {
            $path = $request->file('cover_image')->store('galleries', 'public');
            $gallery->cover_image_url = '/storage/'.$path;
        }
    }

    private function syncImages(Request $request, Gallery $gallery): void
    {
        $removeIds = collect($request->input('remove_image_ids', []))->map(fn ($id) => (int) $id)->all();
        if ($removeIds !== []) {
            GalleryImage::query()
                ->where('gallery_id', $gallery->id)
                ->whereIn('id', $removeIds)
                ->delete();
        }

        $captions = $request->input('captions', []);
        if (is_array($captions)) {
            foreach ($captions as $imageId => $caption) {
                GalleryImage::query()
                    ->where('gallery_id', $gallery->id)
                    ->whereKey((int) $imageId)
                    ->update(['caption' => $caption ?: null]);
            }
        }

        if ($request->hasFile('images')) {
            $maxSort = (int) $gallery->images()->max('sort_order');
            foreach ($request->file('images') as $file) {
                $maxSort++;
                $path = $file->store('galleries/photos', 'public');
                $gallery->images()->create([
                    'image_url' => '/storage/'.$path,
                    'sort_order' => $maxSort,
                ]);
            }
        }

        if (! $gallery->cover_image_url) {
            $first = $gallery->images()->orderBy('sort_order')->first();
            if ($first) {
                $gallery->update(['cover_image_url' => $first->image_url]);
            }
        }
    }
}
