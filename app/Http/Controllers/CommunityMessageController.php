<?php

namespace App\Http\Controllers;

use App\Models\CommunityConversation;
use App\Models\CommunityMessage;
use App\Models\User;
use App\Support\CommunityNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CommunityMessageController extends Controller
{
    public function index(Request $request): Response
    {
        $userId = $request->user()->id;

        $conversations = CommunityConversation::query()
            ->where(function ($q) use ($userId) {
                $q->where('user_one_id', $userId)->orWhere('user_two_id', $userId);
            })
            ->with(['userOne', 'userTwo', 'latestMessage.sender'])
            ->orderByDesc('last_message_at')
            ->paginate(30)
            ->withQueryString()
            ->through(fn (CommunityConversation $c) => $c->toInboxArray($userId));

        return Inertia::render('Community/Messages/Index', [
            'conversations' => $conversations,
        ]);
    }

    public function show(Request $request, CommunityConversation $conversation): Response
    {
        $user = $request->user();
        abort_unless($conversation->involves($user->id), 403);

        $conversation->load(['userOne', 'userTwo']);

        CommunityMessage::query()
            ->where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = CommunityMessage::query()
            ->where('conversation_id', $conversation->id)
            ->with('sender')
            ->oldest()
            ->paginate(50)
            ->withQueryString()
            ->through(fn (CommunityMessage $m) => $m->toFeedArray($user->id));

        return Inertia::render('Community/Messages/Show', [
            'conversation' => [
                'id' => $conversation->id,
                'other_user' => $conversation->otherUser($user->id)?->toPublicArray(true),
                'is_mutual_friend' => ($other = $conversation->otherUser($user->id))
                    ? $user->isMutualFriendWith($other)
                    : false,
            ],
            'messages' => $messages,
        ]);
    }

    public function poll(Request $request, CommunityConversation $conversation): JsonResponse
    {
        $user = $request->user();
        abort_unless($conversation->involves($user->id), 403);

        $user->touchPresence();
        $conversation->loadMissing(['userOne', 'userTwo']);
        $other = $conversation->otherUser($user->id);
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
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'messages' => $messages->map(fn (CommunityMessage $m) => $m->toFeedArray($user->id))->values(),
            'other_online' => $other?->fresh()?->isOnline() ?? false,
            'other_user' => $other?->fresh()?->toPublicArray(true),
        ]);
    }

    public function start(Request $request, string $username): RedirectResponse
    {
        $other = User::query()->where('username', $username)->firstOrFail();
        $me = $request->user();

        abort_if($me->id === $other->id, 422, 'Tidak bisa mengirim pesan ke diri sendiri.');

        $conversation = CommunityConversation::findOrCreateBetween($me, $other);

        if ($request->boolean('live') && $me->isMutualFriendWith($other)) {
            return redirect()->route('community.live-chat.show', $conversation->id);
        }

        return redirect()->route('community.messages.show', $conversation->id);
    }

    public function store(Request $request, CommunityConversation $conversation): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        abort_unless($conversation->involves($user->id), 403);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $body = trim($data['body']);
        $user->touchPresence();

        $message = DB::transaction(function () use ($conversation, $user, $body) {
            $message = CommunityMessage::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $user->id,
                'body' => $body,
            ]);

            $conversation->forceFill([
                'last_message_at' => now(),
            ])->save();

            return $message->load('sender');
        });

        $other = $conversation->otherUser($user->id);
        if ($other) {
            CommunityNotifier::log('dm', $user, null, [
                'conversation_id' => $conversation->id,
                'recipient_id' => $other->id,
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $message->toFeedArray($user->id),
            ]);
        }

        return back()->with('success', __('Pesan terkirim.'));
    }
}
