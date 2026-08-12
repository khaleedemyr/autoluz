<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PushSubscription extends Model
{
    protected $fillable = [
        'endpoint',
        'public_key',
        'auth_token',
        'content_encoding',
        'email',
        'brand_ids',
        'wants_newsletter',
        'locale',
        'user_agent',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'brand_ids' => 'array',
            'wants_newsletter' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    public function scopeForBrand(Builder $query, int $brandId): Builder
    {
        return $query->whereJsonContains('brand_ids', $brandId);
    }

    public function scopeNewsletter(Builder $query): Builder
    {
        return $query->where('wants_newsletter', true);
    }

    public function brandIds(): array
    {
        return collect($this->brand_ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }
}
