<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityNotification extends Model
{
    protected $fillable = [
        'user_id',
        'actor_id',
        'post_id',
        'type',
        'message',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(CommunityPost::class, 'post_id');
    }

    public function toFeedArray(): array
    {
        $post = $this->relationLoaded('post') ? $this->post : null;
        $rootId = $post?->parent_id ?: $post?->id;

        $url = route('community.index');
        if ($this->type === 'follow' && $this->relationLoaded('actor') && $this->actor?->username) {
            $url = route('community.profile', $this->actor->username);
        } elseif ($rootId) {
            $url = route('community.show', $rootId);
        }

        return [
            'id' => $this->id,
            'type' => $this->type,
            'message' => $this->message,
            'read_at' => optional($this->read_at)?->toIso8601String(),
            'is_read' => $this->read_at !== null,
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'created_at_label' => optional($this->created_at)?->diffForHumans(),
            'url' => $url,
            'actor' => $this->relationLoaded('actor') && $this->actor
                ? $this->actor->toPublicArray()
                : null,
        ];
    }
}
