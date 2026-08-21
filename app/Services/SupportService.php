<?php

namespace App\Services;

use App\Models\Role;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Models\User;
use App\Support\GuestSession;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SupportService
{
    public function findForRequest(Request $request): ?SupportConversation
    {
        $user = $request->user();
        $sessionId = GuestSession::id($request);

        $query = SupportConversation::query();

        if ($user) {
            $query->where(function ($inner) use ($user, $sessionId) {
                $inner->where('user_id', $user->id)
                    ->orWhere('session_id', $sessionId);
            });
        } else {
            $query->where('session_id', $sessionId);
        }

        return $query->latest('id')->first();
    }

    public function findOrCreate(Request $request, array $visitor = []): SupportConversation
    {
        $existing = $this->findForRequest($request);
        $user = $request->user();

        if ($existing) {
            $updates = [];
            if ($user && ! $existing->user_id) {
                $updates['user_id'] = $user->id;
            }
            if (! empty($visitor['name'])) {
                $updates['visitor_name'] = $visitor['name'];
            } elseif ($user && ! $existing->visitor_name) {
                $updates['visitor_name'] = $user->name;
            }
            if (! empty($visitor['email'])) {
                $updates['visitor_email'] = $visitor['email'];
            } elseif ($user && ! $existing->visitor_email) {
                $updates['visitor_email'] = $user->email;
            }
            if ($updates) {
                $existing->fill($updates)->save();
            }

            return $existing;
        }

        return SupportConversation::query()->create([
            'user_id' => $user?->id,
            'session_id' => GuestSession::id($request),
            'visitor_name' => $visitor['name'] ?? $user?->name,
            'visitor_email' => $visitor['email'] ?? $user?->email,
            'status' => SupportConversation::STATUS_OPEN,
        ]);
    }

    public function addMessage(SupportConversation $conversation, string $senderType, string $body, ?User $user = null): SupportMessage
    {
        $message = $conversation->messages()->create([
            'user_id' => $user?->id,
            'sender_type' => $senderType,
            'body' => $body,
        ]);

        $preview = mb_strlen($body) > 160 ? mb_substr($body, 0, 157).'…' : $body;

        $conversation->forceFill([
            'status' => SupportConversation::STATUS_OPEN,
            'last_body' => $preview,
            'last_message_at' => now(),
        ])->save();

        return $message;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function messagesAfter(SupportConversation $conversation, int $afterId, string $viewer): Collection
    {
        return $conversation->messages()
            ->with('user')
            ->when($afterId > 0, fn ($q) => $q->where('id', '>', $afterId))
            ->orderBy('id')
            ->limit(100)
            ->get()
            ->map(fn (SupportMessage $message) => $message->toFeedArray($viewer))
            ->values();
    }

    public function markRead(SupportConversation $conversation, string $senderType): void
    {
        $conversation->messages()
            ->where('sender_type', $senderType)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function agentsOnline(): bool
    {
        return User::query()
            ->where('last_seen_at', '>=', now()->subSeconds(User::ONLINE_THRESHOLD_SECONDS))
            ->where(function ($q) {
                $q->where('is_admin', true)
                    ->orWhereHas('role', function ($role) {
                        $role->where('type', Role::TYPE_ADMIN)
                            ->orWhere('is_super', true);
                    });
            })
            ->exists();
    }

    public function unreadCount(): int
    {
        return SupportMessage::query()
            ->where('sender_type', SupportMessage::SENDER_VISITOR)
            ->whereNull('read_at')
            ->count();
    }
}
