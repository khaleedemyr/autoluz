<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Event extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'excerpt',
        'body_html',
        'cover_image_url',
        'location',
        'venue',
        'city',
        'starts_at',
        'ends_at',
        'registration_url',
        'is_featured',
        'status',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('starts_at', '>=', now()->startOfDay())
            ->orderBy('starts_at');
    }

    public function scopePast(Builder $query): Builder
    {
        return $query->where('starts_at', '<', now()->startOfDay())
            ->orderByDesc('starts_at');
    }

    public function isUpcoming(): bool
    {
        return $this->starts_at && $this->starts_at->gte(now()->startOfDay());
    }

    public function toCardArray(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'cover_image_url' => $this->absoluteMediaUrl($this->cover_image_url),
            'location' => $this->location,
            'venue' => $this->venue,
            'city' => $this->city,
            'starts_at' => optional($this->starts_at)?->toIso8601String(),
            'ends_at' => optional($this->ends_at)?->toIso8601String(),
            'registration_url' => $this->registration_url,
            'is_featured' => (bool) $this->is_featured,
            'is_upcoming' => $this->isUpcoming(),
            'url' => route('events.show', $this->slug),
        ];
    }

    public function toDetailArray(): array
    {
        $html = $this->body_html;
        $html = preg_replace_callback(
            '/(<img[^>]+src=["\'])(\/storage\/[^"\']+)(["\'])/i',
            fn (array $m) => $m[1].url($m[2]).$m[3],
            $html ?? ''
        ) ?? $html;

        return [
            ...$this->toCardArray(),
            'body_html' => $html,
            'status' => $this->status,
        ];
    }

    public function toAdminArray(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'body_html' => $this->body_html,
            'cover_image_url' => $this->absoluteMediaUrl($this->cover_image_url),
            'location' => $this->location,
            'venue' => $this->venue,
            'city' => $this->city,
            'starts_at' => optional($this->starts_at)?->format('Y-m-d\TH:i'),
            'ends_at' => optional($this->ends_at)?->format('Y-m-d\TH:i'),
            'registration_url' => $this->registration_url,
            'is_featured' => (bool) $this->is_featured,
            'status' => $this->status,
            'sort_order' => $this->sort_order,
        ];
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'event';
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
