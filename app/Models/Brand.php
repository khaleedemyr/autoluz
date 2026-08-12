<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Brand extends Model
{
    public const TYPE_CAR = 'car';

    public const TYPE_MOTO = 'moto';

    public const TYPE_BOTH = 'both';

    protected $fillable = [
        'name',
        'slug',
        'type',
        'logo_url',
        'description',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class)->withTimestamps();
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class)->orderBy('sort_order')->orderBy('name');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeCars(Builder $query): Builder
    {
        return $query->whereIn('type', [self::TYPE_CAR, self::TYPE_BOTH]);
    }

    public function scopeMotos(Builder $query): Builder
    {
        return $query->whereIn('type', [self::TYPE_MOTO, self::TYPE_BOTH]);
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_MOTO => 'Motor',
            self::TYPE_BOTH => 'Mobil & Motor',
            default => 'Mobil',
        };
    }

    public function toCardArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type ?: self::TYPE_CAR,
            'type_label' => $this->typeLabel(),
            'logo_url' => $this->absoluteMediaUrl($this->logo_url),
            'description' => $this->description,
            'url' => route('brands.show', $this->slug),
            'articles_count' => (int) ($this->articles_count ?? 0),
            'vehicles_count' => (int) ($this->vehicles_count ?? 0),
        ];
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'brand';
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
