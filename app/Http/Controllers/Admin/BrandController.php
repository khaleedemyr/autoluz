<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BrandController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));

        $brands = Brand::query()
            ->withCount('articles')
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('name', 'like', $like)->orWhere('slug', 'like', $like);
                });
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Brand $brand) => [
                ...$brand->toCardArray(),
                'is_active' => $brand->is_active,
                'sort_order' => $brand->sort_order,
                'type' => $brand->type,
            ]);

        return Inertia::render('Admin/Brands/Index', [
            'brands' => $brands,
            'filters' => ['q' => $q],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $brand = new Brand($this->payload($data));
        $brand->slug = Brand::uniqueSlug($data['name']);
        $this->applyLogo($request, $brand);
        $brand->save();

        return back()->with('success', 'Merek ditambahkan.');
    }

    public function update(Request $request, Brand $brand): RedirectResponse
    {
        $data = $this->validated($request, $brand);
        $brand->fill($this->payload($data));
        if (! empty($data['slug'])) {
            $brand->slug = Brand::uniqueSlug($data['slug'], $brand->id);
        }
        $this->applyLogo($request, $brand);
        $brand->save();

        return back()->with('success', 'Merek disimpan.');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        $brand->delete();

        return back()->with('success', 'Merek dihapus.');
    }

    private function validated(Request $request, ?Brand $brand = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:160', Rule::unique('brands', 'slug')->ignore($brand?->id)],
            'type' => ['required', Rule::in(['car', 'moto', 'both'])],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'remove_logo' => ['boolean'],
        ]);
    }

    private function payload(array $data): array
    {
        return [
            'name' => $data['name'],
            'type' => $data['type'],
            'description' => $data['description'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }

    private function applyLogo(Request $request, Brand $brand): void
    {
        if ($request->boolean('remove_logo')) {
            $brand->logo_url = null;
        }

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('brands', 'public');
            $brand->logo_url = '/storage/'.$path;
        }
    }
}
