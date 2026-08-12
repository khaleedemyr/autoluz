<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CommunityConversation extends Model
{
    protected $fillable = [
        'user_one_id',
        'user_two_id',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function userOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function userTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CommunityMessage::class, 'conversation_id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(CommunityMessage::class, 'conversation_id')->latestOfMany();
    }

    public function otherUser(int $viewerId): ?User
    {
        if ($this->user_one_id === $viewerId) {
            return $this->relationLoaded('userTwo') ? $this->userTwo : $this->userTwo()->first();
        }

        return $this->relationLoaded('userOne') ? $this->userOne : $this->userOne()->first();
    }

    public function involves(int $userId): bool
    {
        return $this->user_one_id === $userId || $this->user_two_id === $userId;
    }

    public static function pairIds(int $a, int $b): array
    {
        return $a < $b
            ? ['user_one_id' => $a, 'user_two_id' => $b]
            : ['user_one_id' => $b, 'user_two_id' => $a];
    }

    public static function findOrCreateBetween(User $a, User $b): self
    {
        $pair = self::pairIds($a->id, $b->id);

        return self::query()->firstOrCreate($pair);
    }

    public function unreadCountFor(int $userId): int
    {
        return $this->messages()
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->count();
    }

    public function toInboxArray(int $viewerId): array
    {
        $other = $this->otherUser($viewerId);
        $latest = $this->relationLoaded('latestMessage')
            ? $this->latestMessage
            : $this->latestMessage()->first();

        return [
            'id' => $this->id,
            'url' => route('community.messages.show', $this->id),
            'other_user' => $other?->toPublicArray(),
            'last_message' => $latest?->toFeedArray($viewerId),
            'last_message_at' => optional($this->last_message_at)?->toIso8601String(),
            'last_message_at_label' => optional($this->last_message_at)?->diffForHumans(),
            'unread_count' => $this->unreadCountFor($viewerId),
        ];
    }
}
