<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunityPost extends Model
{
    protected $fillable = [
        'user_id',
        'parent_id',
        'group_id',
        'article_id',
        'event_id',
        'vehicle_id',
        'body',
        'image_path',
        'likes_count',
        'replies_count',
        'is_hidden',
    ];

    protected function casts(): array
    {
        return [
            'likes_count' => 'integer',
            'replies_count' => 'integer',
            'is_hidden' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(CommunityGroup::class, 'group_id');
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('created_at');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(CommunityLike::class, 'post_id');
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_hidden', false);
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function imageUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        if (str_starts_with($this->image_path, 'http://') || str_starts_with($this->image_path, 'https://')) {
            return $this->image_path;
        }

        return url('/storage/'.$this->image_path);
    }

    public function rootId(): int
    {
        $current = $this;
        $guard = 0;

        while ($current->parent_id && $guard < 20) {
            $parent = $current->relationLoaded('parent')
                ? $current->parent
                : self::query()->find($current->parent_id);

            if (! $parent) {
                break;
            }

            $current = $parent;
            $guard++;
        }

        return (int) $current->id;
    }

    public function toCardArray(?int $viewerId = null, int $depth = 0): array
    {
        $this->loadMissing(['user', 'parent.user', 'group', 'article', 'event', 'vehicle.brand']);

        $rootId = $this->parent_id ? $this->rootId() : $this->id;

        $children = [];
        if ($this->relationLoaded('replies')) {
            $children = $this->replies
                ->map(fn (self $reply) => $reply->toCardArray($viewerId, $depth + 1))
                ->values()
                ->all();
        }

        $article = null;
        if ($this->article && ($this->article->status ?? null) === 'published') {
            $article = [
                'id' => $this->article->id,
                'title' => $this->article->title,
                'slug' => $this->article->slug,
                'excerpt' => $this->article->excerpt,
                'featured_image_url' => $this->article->toCardArray()['featured_image_url'] ?? null,
                'url' => route('articles.show', $this->article->slug),
            ];
        }

        $event = null;
        if ($this->event && ($this->event->status ?? null) === 'published') {
            $event = [
                'id' => $this->event->id,
                'title' => $this->event->title,
                'slug' => $this->event->slug,
                'excerpt' => $this->event->excerpt,
                'cover_image_url' => $this->event->toCardArray()['cover_image_url'] ?? null,
                'starts_at_label' => optional($this->event->starts_at)?->translatedFormat('d M Y'),
                'url' => route('events.show', $this->event->slug),
            ];
        }

        $vehicle = null;
        if ($this->vehicle && ($this->vehicle->status ?? null) === 'published') {
            $card = $this->vehicle->toCardArray();
            $vehicle = [
                'id' => $this->vehicle->id,
                'name' => $this->vehicle->name,
                'slug' => $this->vehicle->slug,
                'cover_image_url' => $card['cover_image_url'] ?? null,
                'price_label' => $card['price_label'] ?? null,
                'brand_name' => $card['brand']['name'] ?? null,
                'url' => $card['url'] ?? null,
            ];
        }

        return [
            'id' => $this->id,
            'body' => $this->body,
            'image_url' => $this->imageUrl(),
            'likes_count' => (int) $this->likes_count,
            'replies_count' => (int) $this->replies_count,
            'liked_by_me' => $viewerId && $this->relationLoaded('likes')
                ? $this->likes->contains('user_id', $viewerId)
                : false,
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'created_at_label' => optional($this->created_at)?->diffForHumans(),
            'url' => route('community.show', $rootId).'#post-'.$this->id,
            'root_id' => $rootId,
            'can_delete' => $viewerId
                ? ($this->user_id === $viewerId || (auth()->user()?->is_admin ?? false))
                : false,
            'user' => $this->user ? $this->user->toPublicArray() : null,
            'group' => $this->group ? [
                'id' => $this->group->id,
                'name' => $this->group->name,
                'slug' => $this->group->slug,
                'url' => route('community.groups.show', $this->group->slug),
            ] : null,
            'article' => $article,
            'event' => $event,
            'vehicle' => $vehicle,
            'parent_id' => $this->parent_id,
            'parent_user' => $this->parent?->user
                ? $this->parent->user->toPublicArray()
                : null,
            'depth' => $depth,
            'replies' => $children,
        ];
    }
}
