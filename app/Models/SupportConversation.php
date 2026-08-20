<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportConversation extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'user_id',
        'session_id',
        'visitor_name',
        'visitor_email',
        'status',
        'last_body',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class, 'conversation_id');
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function unreadVisitorCount(): int
    {
        return $this->messages()
            ->where('sender_type', SupportMessage::SENDER_VISITOR)
            ->whereNull('read_at')
            ->count();
    }

    public function toVisitorArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'visitor_name' => $this->visitor_name,
            'visitor_email' => $this->visitor_email,
        ];
    }

    public function toAdminArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'visitor_name' => $this->displayName(),
            'visitor_email' => $this->visitor_email ?: $this->user?->email,
            'user_id' => $this->user_id,
            'unread' => (int) ($this->unread_count ?? 0),
            'last_message' => $this->last_body,
            'last_message_at' => optional($this->last_message_at)?->toIso8601String(),
            'last_message_label' => optional($this->last_message_at)?->diffForHumans(),
        ];
    }

    public function displayName(): string
    {
        return $this->visitor_name
            ?: $this->user?->name
            ?: 'Pengunjung';
    }
}
