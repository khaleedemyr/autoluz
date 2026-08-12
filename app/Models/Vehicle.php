<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Vehicle extends Model
{
    protected $fillable = [
        'brand_id',
        'name',
        'slug',
        'body_type',
        'model_year',
        'excerpt',
        'description_html',
        'specs',
        'cover_image_url',
        'price_from',
        'price_currency',
        'status',
        'published_at',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'specs' => 'array',
            'price_from' => 'integer',
            'published_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(VehicleImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', 'published')
            ->where(function (Builder $q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    public function toCardArray(): array
    {
        $specs = collect($this->specs ?? [])
            ->filter(fn ($row) => filled($row['label'] ?? null) && filled($row['value'] ?? null))
            ->values()
            ->all();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'body_type' => $this->body_type,
            'model_year' => $this->model_year,
            'excerpt' => $this->excerpt,
            'cover_image_url' => $this->absoluteMediaUrl($this->cover_image_url)
                ?: $this->absoluteMediaUrl($this->relationLoaded('images') ? $this->images->first()?->image_url : null),
            'price_from' => $this->price_from,
            'price_currency' => $this->price_currency ?: 'IDR',
            'price_label' => $this->priceLabel(),
            'specs' => $specs,
            'highlight_specs' => array_slice($specs, 0, 4),
            'images_count' => (int) ($this->images_count ?? ($this->relationLoaded('images') ? $this->images->count() : 0)),
            'url' => $this->brand
                ? route('brands.vehicles.show', [$this->brand->slug, $this->slug])
                : null,
            'brand' => $this->relationLoaded('brand') && $this->brand ? [
                'id' => $this->brand->id,
                'name' => $this->brand->name,
                'slug' => $this->brand->slug,
                'logo_url' => $this->brand->toCardArray()['logo_url'] ?? null,
            ] : null,
        ];
    }

    public function toDetailArray(): array
    {
        return [
            ...$this->toCardArray(),
            'description_html' => $this->description_html,
            'specs' => collect($this->specs ?? [])
                ->filter(fn ($row) => filled($row['label'] ?? null) || filled($row['value'] ?? null))
                ->values()
                ->all(),
            'images' => $this->images->map(fn (VehicleImage $image) => $image->toArrayPublic())->values()->all(),
            'published_at' => optional($this->published_at)?->toIso8601String(),
        ];
    }

    public function priceLabel(): ?string
    {
        if (! $this->price_from) {
            return null;
        }

        return 'Rp '.number_format($this->price_from, 0, ',', '.');
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'vehicle';
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
