<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportMessage extends Model
{
    public const SENDER_VISITOR = 'visitor';
    public const SENDER_AGENT = 'agent';

    protected $fillable = [
        'conversation_id',
        'user_id',
        'sender_type',
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
        return $this->belongsTo(SupportConversation::class, 'conversation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function toFeedArray(string $viewer): array
    {
        $mine = $viewer === 'agent'
            ? $this->sender_type === self::SENDER_AGENT
            : $this->sender_type === self::SENDER_VISITOR;

        return [
            'id' => $this->id,
            'body' => $this->body,
            'is_mine' => $mine,
            'sender_type' => $this->sender_type,
            'sender_name' => $this->sender_type === self::SENDER_AGENT
                ? ($this->user?->name ?: 'Autoluz')
                : ($this->conversation?->displayName() ?: 'Pengunjung'),
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'created_at_label' => optional($this->created_at)?->diffForHumans(),
        ];
    }
}
