<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    protected $fillable = [
        'article_id',
        'name',
        'email',
        'body',
        'is_visible',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
        ];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'body' => $this->body,
            'created_at' => optional($this->created_at)?->toIso8601String(),
        ];
    }

    public function toAdminArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'body' => $this->body,
            'is_visible' => $this->is_visible,
            'ip_address' => $this->ip_address,
            'created_at' => optional($this->created_at)?->format('Y-m-d H:i'),
            'article' => $this->article ? [
                'id' => $this->article->id,
                'title' => $this->article->title,
                'slug' => $this->article->slug,
            ] : null,
        ];
    }
}
