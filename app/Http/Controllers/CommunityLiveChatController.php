<?php

namespace App\Http\Controllers;

use App\Models\CommunityConversation;
use App\Models\CommunityMessage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CommunityLiveChatController extends Controller
{
    public function index(Request $request): Response
    {
        $me = $request->user();
        $me->touchPresence();

        $friends = $me->mutualFriends()
            ->map(fn (User $u) => $u->toPublicArray(true))
            ->sortByDesc('is_online')
            ->values();

        $onlineCount = $friends->where('is_online', true)->count();

        return Inertia::render('Community/LiveChat', [
            'friends' => $friends,
            'online_count' => $onlineCount,
            'active_conversation_id' => null,
            'active_friend' => null,
            'messages' => [],
        ]);
    }

    public function open(Request $request, string $username): Response|\Illuminate\Http\RedirectResponse
    {
        $me = $request->user();
        $friend = User::query()->where('username', $username)->firstOrFail();

        abort_unless($me->isMutualFriendWith($friend), 403, 'Live chat hanya untuk teman yang saling follow.');

        $me->touchPresence();
        $conversation = CommunityConversation::findOrCreateBetween($me, $friend);

        return redirect()->route('community.live-chat.show', $conversation->id);
    }

    public function show(Request $request, CommunityConversation $conversation): Response
    {
        $me = $request->user();
        abort_unless($conversation->involves($me->id), 403);

        $me->touchPresence();
        $conversation->load(['userOne', 'userTwo']);
        $friend = $conversation->otherUser($me->id);
        abort_unless($friend && $me->isMutualFriendWith($friend), 403, 'Live chat hanya untuk teman yang saling follow.');

        CommunityMessage::query()
            ->where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $me->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = CommunityMessage::query()
            ->where('conversation_id', $conversation->id)
            ->with('sender')
            ->oldest()
            ->limit(100)
            ->get()
            ->map(fn (CommunityMessage $m) => $m->toFeedArray($me->id))
            ->values();

        $friends = $me->mutualFriends()
            ->map(fn (User $u) => $u->toPublicArray(true))
            ->sortByDesc('is_online')
            ->values();

        return Inertia::render('Community/LiveChat', [
            'friends' => $friends,
            'online_count' => $friends->where('is_online', true)->count(),
            'active_conversation_id' => $conversation->id,
            'active_friend' => $friend->toPublicArray(true),
            'messages' => $messages,
        ]);
    }

    public function heartbeat(Request $request): JsonResponse
    {
        $request->user()->touchPresence();

        return response()->json([
            'ok' => true,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    public function friends(Request $request): JsonResponse
    {
        $me = $request->user();
        $me->touchPresence();

        $friends = $me->mutualFriends()
            ->map(fn (User $u) => $u->toPublicArray(true))
            ->sortByDesc('is_online')
            ->values();

        return response()->json([
            'friends' => $friends,
            'online_count' => $friends->where('is_online', true)->count(),
        ]);
    }

    public function poll(Request $request, CommunityConversation $conversation): JsonResponse
    {
        $me = $request->user();
        abort_unless($conversation->involves($me->id), 403);

        $me->touchPresence();
        $conversation->loadMissing(['userOne', 'userTwo']);
        $friend = $conversation->otherUser($me->id);
        abort_unless($friend && $me->isMutualFriendWith($friend), 403);

        $afterId = (int) $request->query('after_id', 0);

        $messages = CommunityMessage::query()
            ->where('conversation_id', $conversation->id)
            ->when($afterId > 0, fn ($q) => $q->where('id', '>', $afterId))
            ->with('sender')
            ->oldest()
            ->limit(50)
            ->get();

        CommunityMessage::query()
            ->where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $me->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'messages' => $messages->map(fn (CommunityMessage $m) => $m->toFeedArray($me->id))->values(),
            'other_online' => $friend->fresh()?->isOnline() ?? false,
            'friend' => $friend->fresh()?->toPublicArray(true),
        ]);
    }

    public function send(Request $request, CommunityConversation $conversation): JsonResponse
    {
        $me = $request->user();
        abort_unless($conversation->involves($me->id), 403);

        $conversation->loadMissing(['userOne', 'userTwo']);
        $friend = $conversation->otherUser($me->id);
        abort_unless($friend && $me->isMutualFriendWith($friend), 403);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $me->touchPresence();

        $message = DB::transaction(function () use ($conversation, $me, $data) {
            $message = CommunityMessage::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $me->id,
                'body' => trim($data['body']),
            ]);

            $conversation->forceFill(['last_message_at' => now()])->save();

            return $message->load('sender');
        });

        return response()->json([
            'message' => $message->toFeedArray($me->id),
        ]);
    }
}
