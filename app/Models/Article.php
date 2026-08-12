<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Article extends Model
{
    protected $fillable = [
        'legacy_wp_id',
        'slug',
        'title',
        'excerpt',
        'meta_title',
        'meta_description',
        'focus_keyword',
        'canonical_url',
        'og_title',
        'og_description',
        'content_html',
        'featured_image_url',
        'is_featured',
        'is_slider',
        'slider_image_url',
        'category_id',
        'status',
        'published_at',
        'views_count',
        'shares_count',
    ];

    protected function casts(): array
    {
        return [
            'legacy_wp_id' => 'integer',
            'is_featured' => 'boolean',
            'is_slider' => 'boolean',
            'published_at' => 'datetime',
            'views_count' => 'integer',
            'shares_count' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'article_category')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class)->withTimestamps();
    }

    public function scopeInCategories(Builder $query, array $categoryIds): Builder
    {
        $categoryIds = array_values(array_filter(array_map('intval', $categoryIds)));

        if ($categoryIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $q) use ($categoryIds) {
            $q->whereIn('category_id', $categoryIds)
                ->orWhereHas('categories', fn (Builder $c) => $c->whereIn('categories.id', $categoryIds));
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $term) . '%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('title', 'like', $like)
                ->orWhere('excerpt', 'like', $like)
                ->orWhere('content_html', 'like', $like);
        });
    }

    public function scopeWithCardStats(Builder $query): Builder
    {
        return $query->withCount([
            'comments as comments_count' => fn (Builder $q) => $q->where('is_visible', true),
        ]);
    }

    public function toCardArray(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'featured_image_url' => $this->absoluteMediaUrl($this->featured_image_url),
            'is_featured' => $this->is_featured,
            'published_at' => optional($this->published_at)?->toIso8601String(),
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->localizedName(),
                'slug' => $this->category->slug,
            ] : null,
            'brands' => $this->relationLoaded('brands')
                ? $this->brands->map(fn (Brand $brand) => [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'slug' => $brand->slug,
                    'url' => route('brands.show', $brand->slug),
                ])->values()->all()
                : [],
            'url' => route('articles.show', $this->slug),
            'views_count' => (int) ($this->views_count ?? 0),
            'shares_count' => (int) ($this->shares_count ?? 0),
            'comments_count' => (int) ($this->comments_count ?? 0),
        ];
    }

    public function toDetailArray(): array
    {
        $html = $this->content_html;

        // Make local /storage/... image paths absolute for the frontend.
        $html = preg_replace_callback(
            '/(<img[^>]+src=["\'])(\/storage\/[^"\']+)(["\'])/i',
            fn (array $m) => $m[1].url($m[2]).$m[3],
            $html
        ) ?? $html;

        $metaTitle = $this->meta_title ?: $this->title;
        $metaDescription = $this->meta_description ?: ($this->excerpt ?: $this->title);
        $ogTitle = $this->og_title ?: $metaTitle;
        $ogDescription = $this->og_description ?: $metaDescription;
        $canonical = $this->canonical_url ?: route('articles.show', $this->slug);

        return [
            ...$this->toCardArray(),
            'content_html' => $html,
            'slider_image_url' => $this->absoluteMediaUrl($this->slider_image_url),
            'is_slider' => $this->is_slider,
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
            'seo' => [
                'meta_title' => $metaTitle,
                'meta_description' => $metaDescription,
                'focus_keyword' => $this->focus_keyword,
                'canonical_url' => $canonical,
                'og_title' => $ogTitle,
                'og_description' => $ogDescription,
            ],
        ];
    }

    protected function absoluteMediaUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return url($path);
    }
}
