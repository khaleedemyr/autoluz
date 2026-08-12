<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Brand;
use App\Models\Category;
use App\Services\WebPushNotifier;
use App\Support\NavBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ArticleController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $categoryId = (int) $request->query('category_id', 0);
        $perPage = $this->perPage($request);

        $articles = Article::query()
            ->with('category')
            ->when($q !== '', fn ($query) => $query->search($q))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($categoryId > 0, fn ($query) => $query->where('category_id', $categoryId))
            ->orderByDesc('updated_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Article $article) => [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'status' => $article->status,
                'is_featured' => $article->is_featured,
                'featured_image_url' => $article->toCardArray()['featured_image_url'],
                'category' => $article->category ? [
                    'id' => $article->category->id,
                    'name' => $article->category->name,
                ] : null,
                'published_at' => optional($article->published_at)?->format('Y-m-d H:i'),
                'updated_at' => optional($article->updated_at)?->format('Y-m-d H:i'),
            ]);

        $selectedCategory = $categoryId > 0
            ? Category::query()->whereKey($categoryId)->first(['id', 'name', 'slug'])
            : null;

        return Inertia::render('Admin/Articles/Index', [
            'articles' => $articles,
            'categories' => $this->categoryOptions(),
            'filters' => [
                'q' => $q,
                'status' => $status,
                'category_id' => $categoryId > 0 ? $categoryId : '',
                'per_page' => $perPage,
            ],
            'selectedCategory' => $selectedCategory ? [
                'id' => $selectedCategory->id,
                'name' => $selectedCategory->name,
                'slug' => $selectedCategory->slug,
            ] : null,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Articles/Form', [
            'article' => null,
            'categories' => $this->categoryOptions(),
            'brands' => $this->brandOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $article = $this->persist(new Article(), $data, $request);

        NavBuilder::clearCache();
        $this->maybeNotifyPush($article, wasPublished: false);

        return redirect()
            ->route('admin.articles.edit', $article)
            ->with('success', 'Artikel berhasil dibuat.');
    }

    public function edit(Article $article): Response
    {
        $article->load(['category', 'brands']);

        return Inertia::render('Admin/Articles/Form', [
            'article' => [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'excerpt' => $article->excerpt,
                'content_html' => $article->content_html,
                'featured_image_url' => $article->toCardArray()['featured_image_url'],
                'category_id' => $article->category_id,
                'brand_ids' => $article->brands->pluck('id')->values()->all(),
                'status' => $article->status,
                'is_featured' => $article->is_featured,
                'published_at' => optional($article->published_at)?->format('Y-m-d\TH:i'),
                'meta_title' => $article->meta_title,
                'meta_description' => $article->meta_description,
                'focus_keyword' => $article->focus_keyword,
                'canonical_url' => $article->canonical_url,
                'og_title' => $article->og_title,
                'og_description' => $article->og_description,
            ],
            'categories' => $this->categoryOptions(),
            'brands' => $this->brandOptions(),
        ]);
    }

    public function update(Request $request, Article $article): RedirectResponse
    {
        $wasPublished = $article->status === 'published';
        $data = $this->validated($request, $article);
        $this->persist($article, $data, $request);

        NavBuilder::clearCache();
        $this->maybeNotifyPush($article, $wasPublished);

        return redirect()
            ->route('admin.articles.edit', $article)
            ->with('success', 'Artikel berhasil disimpan.');
    }

    public function destroy(Article $article): RedirectResponse
    {
        $article->delete();
        NavBuilder::clearCache();

        return redirect()
            ->route('admin.articles.index')
            ->with('success', 'Artikel dihapus.');
    }

    public function uploadImage(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'max:5120'],
        ]);

        $path = $request->file('image')->store('articles/content', 'public');

        return response()->json([
            'url' => url('/storage/'.$path),
        ]);
    }

    protected function validated(Request $request, ?Article $article = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:220',
                Rule::unique('articles', 'slug')->ignore($article?->id),
            ],
            'excerpt' => ['nullable', 'string'],
            'content_html' => ['required', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'brand_ids' => ['nullable', 'array'],
            'brand_ids.*' => ['integer', 'exists:brands,id'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'is_featured' => ['boolean'],
            'published_at' => ['nullable', 'date'],
            'featured_image' => ['nullable', 'image', 'max:5120'],
            'remove_featured_image' => ['boolean'],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:180'],
            'focus_keyword' => ['nullable', 'string', 'max:120'],
            'canonical_url' => ['nullable', 'string', 'max:500'],
            'og_title' => ['nullable', 'string', 'max:120'],
            'og_description' => ['nullable', 'string', 'max:200'],
        ]);
    }

    protected function persist(Article $article, array $data, Request $request): Article
    {
        $slug = trim((string) ($data['slug'] ?? ''));
        if ($slug === '') {
            $slug = Str::slug($data['title']);
        }

        $article->fill([
            'title' => $data['title'],
            'slug' => $slug,
            'excerpt' => $data['excerpt'] ?? null,
            'content_html' => $data['content_html'],
            'category_id' => $data['category_id'] ?? null,
            'status' => $data['status'],
            'is_featured' => (bool) ($data['is_featured'] ?? false),
            'published_at' => $data['published_at'] ?? ($data['status'] === 'published' ? now() : null),
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'focus_keyword' => $data['focus_keyword'] ?? null,
            'canonical_url' => $data['canonical_url'] ?? null,
            'og_title' => $data['og_title'] ?? null,
            'og_description' => $data['og_description'] ?? null,
        ]);

        if ($request->boolean('remove_featured_image')) {
            $article->featured_image_url = null;
        }

        if ($request->hasFile('featured_image')) {
            $path = $request->file('featured_image')->store('articles', 'public');
            $article->featured_image_url = '/storage/'.$path;
        }

        $article->save();

        DB::table('article_category')->where('article_id', $article->id)->delete();
        if ($article->category_id) {
            DB::table('article_category')->insert([
                'article_id' => $article->id,
                'category_id' => $article->category_id,
                'is_primary' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $article->brands()->sync(collect($data['brand_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->unique()->all());

        return $article;
    }

    protected function maybeNotifyPush(Article $article, bool $wasPublished): void
    {
        $article->refresh();
        if ($article->status !== 'published') {
            return;
        }

        // Only notify on first publish (create as published, or draft → published).
        if ($wasPublished) {
            return;
        }

        try {
            app(WebPushNotifier::class)->notifyArticle($article);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function brandOptions(): array
    {
        return Brand::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'type'])
            ->map(fn (Brand $b) => [
                'id' => $b->id,
                'name' => $b->name.' ('.$b->typeLabel().')',
                'slug' => $b->slug,
                'type' => $b->type,
            ])
            ->values()
            ->all();
    }

    protected function categoryOptions(): array
    {
        return Category::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->map(fn (Category $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
            ])
            ->values()
            ->all();
    }

    protected function perPage(Request $request): int
    {
        $allowed = [10, 15, 25, 50, 100];
        $perPage = (int) $request->query('per_page', 15);

        return in_array($perPage, $allowed, true) ? $perPage : 15;
    }
}
