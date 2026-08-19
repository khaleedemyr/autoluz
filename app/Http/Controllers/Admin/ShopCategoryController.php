<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShopCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ShopCategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));

        $categories = ShopCategory::query()
            ->withCount('products')
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('name', 'like', $like)->orWhere('slug', 'like', $like);
                });
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (ShopCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'sort_order' => $category->sort_order,
                'is_active' => $category->is_active,
                'products_count' => $category->products_count,
            ]);

        return Inertia::render('Admin/ShopCategories/Index', [
            'categories' => $categories,
            'filters' => ['q' => $q],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        ShopCategory::query()->create([
            'name' => $data['name'],
            'slug' => ShopCategory::uniqueSlug($data['slug'] ?: $data['name']),
            'description' => $data['description'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return back()->with('success', 'Kategori toko ditambahkan.');
    }

    public function update(Request $request, ShopCategory $shopCategory): RedirectResponse
    {
        $data = $this->validated($request, $shopCategory);
        $slug = trim((string) ($data['slug'] ?? '')) ?: Str::slug($data['name']);

        $shopCategory->update([
            'name' => $data['name'],
            'slug' => ShopCategory::uniqueSlug($slug, $shopCategory->id),
            'description' => $data['description'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return back()->with('success', 'Kategori toko disimpan.');
    }

    public function destroy(ShopCategory $shopCategory): RedirectResponse
    {
        $shopCategory->delete();

        return back()->with('success', 'Kategori toko dihapus.');
    }

    protected function validated(Request $request, ?ShopCategory $category = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => [
                'nullable',
                'string',
                'max:180',
                Rule::unique('shop_categories', 'slug')->ignore($category?->id),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);
    }
}
