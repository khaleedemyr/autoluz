<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityMessage extends Model
{
    protected $fillable = [
        'conversation_id',
        'sender_id',
        'body',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(CommunityConversation::class, 'conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function toFeedArray(?int $viewerId = null): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'is_mine' => $viewerId ? $this->sender_id === $viewerId : false,
            'read_at' => optional($this->read_at)?->toIso8601String(),
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'created_at_label' => optional($this->created_at)?->diffForHumans(),
            'sender' => $this->relationLoaded('sender') && $this->sender
                ? $this->sender->toPublicArray()
                : null,
        ];
    }
}
