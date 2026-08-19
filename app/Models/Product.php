<?php

namespace App\Models;

use App\Support\MediaUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = [
        'shop_category_id',
        'store_id',
        'name',
        'slug',
        'excerpt',
        'description_html',
        'cover_image_url',
        'weight_grams',
        'featured',
        'status',
        'published_at',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'weight_grams' => 'integer',
            'featured' => 'boolean',
            'published_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ShopCategory::class, 'shop_category_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order')->orderBy('id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function scopeWithRating(Builder $query): Builder
    {
        if (! Schema::hasTable('product_reviews')) {
            return $query;
        }

        return $query->withAvg('reviews', 'rating')->withCount('reviews');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', 'published')
            ->where(function (Builder $q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->whereHas('store', fn (Builder $q) => $q->where('status', Store::STATUS_APPROVED));
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('featured', true);
    }

    public function minPrice(): ?int
    {
        $variants = $this->relationLoaded('variants')
            ? $this->variants->where('is_active', true)
            : $this->variants()->active()->get();

        if ($variants->isEmpty()) {
            return null;
        }

        return (int) $variants->min('price');
    }

    public function totalStock(): int
    {
        $variants = $this->relationLoaded('variants')
            ? $this->variants->where('is_active', true)
            : $this->variants()->active()->get();

        return (int) $variants->sum('stock');
    }

    public function coverUrl(): ?string
    {
        return MediaUrl::absolute($this->cover_image_url)
            ?: MediaUrl::absolute($this->relationLoaded('images') ? $this->images->first()?->image_url : null);
    }

    public function defaultVariant(): ?ProductVariant
    {
        $variants = $this->relationLoaded('variants')
            ? $this->variants->where('is_active', true)->values()
            : $this->variants()->active()->orderBy('sort_order')->orderBy('id')->get();

        return $variants->first(fn (ProductVariant $variant) => (int) $variant->stock > 0)
            ?: $variants->first();
    }

    public function toCardArray(): array
    {
        $price = $this->minPrice();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'cover_image_url' => $this->coverUrl(),
            'price_from' => $price,
            'price_label' => $price !== null ? MediaUrl::formatRupiah($price) : null,
            'in_stock' => $this->totalStock() > 0,
            'default_variant_id' => $this->defaultVariant()?->id,
            'featured' => $this->featured,
            'rating_avg' => $this->reviews_count
                ? round((float) ($this->reviews_avg_rating ?? 0), 1)
                : null,
            'reviews_count' => (int) ($this->reviews_count ?? 0),
            'url' => route('shop.show', $this->slug),
            'store' => $this->relationLoaded('store') && $this->store ? $this->store->toCardArray() : null,
            'category' => $this->relationLoaded('category') && $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null,
        ];
    }

    public function toDetailArray(): array
    {
        $activeVariants = $this->variants->where('is_active', true)->values();

        return [
            ...$this->toCardArray(),
            'description_html' => $this->description_html,
            'weight_grams' => $this->weight_grams,
            'images' => $this->images->map->toArrayPublic()->values()->all(),
            'variants' => $activeVariants->map->toArrayPublic()->values()->all(),
            'sizes' => $activeVariants->pluck('size')->filter()->unique()->values()->all(),
            'colors' => $activeVariants->pluck('color')->filter()->unique()->values()->all(),
            'published_at' => optional($this->published_at)?->toIso8601String(),
        ];
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'produk';
        $slug = $base;
        $i = 2;

        while (
            static::query()
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
