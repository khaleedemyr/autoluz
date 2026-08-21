<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\CommunityConversation;
use App\Models\CommunityFollow;
use App\Models\CommunityGroup;
use App\Models\CommunityGroupMember;
use App\Models\CommunityLike;
use App\Models\CommunityMessage;
use App\Models\CommunityNotification;
use App\Models\CommunityPost;
use App\Models\Event;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\CommunityNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CommunityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $viewer = $request->user();
        $viewerId = $viewer?->id;

        $posts = CommunityPost::query()
            ->visible()
            ->roots()
            ->whereNull('group_id')
            ->with(['user', 'article', 'event', 'vehicle.brand'])
            ->when($viewerId, fn ($q) => $q->with(['likes' => fn ($q) => $q->where('user_id', $viewerId)]))
            ->latest()
            ->paginate(20)
            ->through(fn (CommunityPost $post) => $post->toCardArray($viewerId));

        $myGroups = collect();
        if ($viewer) {
            $myGroups = CommunityGroup::query()
                ->whereHas('memberships', fn ($q) => $q->where('user_id', $viewer->id))
                ->orderBy('name')
                ->limit(12)
                ->get()
                ->map(fn (CommunityGroup $g) => $g->toCardArray($viewer))
                ->values();
        }

        $discoverGroups = CommunityGroup::query()
            ->when($myGroups->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $myGroups->pluck('id')->all()))
            ->orderByDesc('members_count')
            ->limit(8)
            ->get()
            ->map(fn (CommunityGroup $g) => $g->toCardArray($viewer))
            ->values();

        return response()->json([
            'posts' => $posts,
            'my_groups' => $myGroups,
            'discover_groups' => $discoverGroups,
        ]);
    }

    public function show(Request $request, CommunityPost $post): JsonResponse
    {
        abort_if($post->is_hidden || $post->parent_id !== null, 404);
        $viewerId = $request->user()?->id;

        $post->load([
            'user',
            'article',
            'event',
            'vehicle.brand',
            'replies' => fn ($q) => $q->visible()->with(['user', 'parent.user', 'article', 'event', 'vehicle.brand'])->oldest(),
        ]);

        if ($viewerId) {
            $post->load(['likes' => fn ($q) => $q->where('user_id', $viewerId)]);
        }

        $replies = $post->replies->map(fn (CommunityPost $reply) => $reply->toCardArray($viewerId, 1))->values();
        $post->unsetRelation('replies');

        return response()->json([
            'post' => $post->toCardArray($viewerId),
            'replies' => $replies,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:500'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'video' => ['nullable', 'file', 'mimes:mp4,webm,mov,qt', 'max:25600'],
            'group_id' => ['nullable', 'integer', 'exists:community_groups,id'],
            'article_id' => ['nullable', 'integer', 'exists:articles,id'],
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
        ]);

        $user = $request->user();
        $group = null;
        if (! empty($data['group_id'])) {
            $group = CommunityGroup::query()->findOrFail($data['group_id']);
            abort_unless($group->isMember($user), 403, 'Gabung grup dulu untuk posting.');
        }

        [$imagePath, $videoPath] = $this->storeMedia($request);
        $post = DB::transaction(function () use ($user, $data, $imagePath, $videoPath, $group) {
            $post = CommunityPost::create([
                'user_id' => $user->id,
                'group_id' => $group?->id,
                'article_id' => $data['article_id'] ?? null,
                'event_id' => $data['event_id'] ?? null,
                'vehicle_id' => $data['vehicle_id'] ?? null,
                'body' => trim($data['body']),
                'image_path' => $imagePath,
                'video_path' => $videoPath,
            ]);
            if ($group) {
                $group->increment('posts_count');
            }

            return $post;
        });

        CommunityNotifier::log('post', $user, $post);

        return response()->json([
            'post' => $post->load(['user', 'article', 'event', 'vehicle.brand'])->toCardArray($user->id),
            'message' => 'Post berhasil dipublikasikan.',
        ], 201);
    }

    public function reply(Request $request, CommunityPost $post): JsonResponse
    {
        abort_if($post->is_hidden, 404);
        $parent = $post;
        $root = $post->parent_id === null ? $post : CommunityPost::query()->findOrFail($post->rootId());
        abort_if($root->is_hidden, 404);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:500'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'video' => ['nullable', 'file', 'mimes:mp4,webm,mov,qt', 'max:25600'],
        ]);

        [$imagePath, $videoPath] = $this->storeMedia($request);
        $reply = null;
        DB::transaction(function () use ($request, $parent, $root, $data, $imagePath, $videoPath, &$reply) {
            $reply = CommunityPost::create([
                'user_id' => $request->user()->id,
                'parent_id' => $parent->id,
                'group_id' => $root->group_id,
                'body' => trim($data['body']),
                'image_path' => $imagePath,
                'video_path' => $videoPath,
            ]);
            $parent->increment('replies_count');
            if ($parent->id !== $root->id) {
                $root->increment('replies_count');
            }
        });

        CommunityNotifier::replied($request->user(), $parent, $reply, $root);

        return response()->json([
            'reply' => $reply->load('user')->toCardArray($request->user()->id, 1),
            'message' => 'Balasan terkirim.',
        ]);
    }

    public function like(Request $request, CommunityPost $post): JsonResponse
    {
        abort_if($post->is_hidden, 404);
        $user = $request->user();
        $liked = false;

        DB::transaction(function () use ($post, $user, &$liked) {
            $existing = CommunityLike::query()->where('user_id', $user->id)->where('post_id', $post->id)->first();
            if ($existing) {
                $existing->delete();
                $post->decrement('likes_count');
                $liked = false;
            } else {
                CommunityLike::create(['user_id' => $user->id, 'post_id' => $post->id]);
                $post->increment('likes_count');
                $liked = true;
                CommunityNotifier::liked($user, $post);
            }
        });

        return response()->json([
            'liked' => $liked,
            'likes_count' => (int) $post->fresh()->likes_count,
        ]);
    }

    public function destroy(Request $request, CommunityPost $post): JsonResponse
    {
        $user = $request->user();
        abort_unless($post->user_id === $user->id || $user->is_admin, 403);

        DB::transaction(function () use ($post) {
            if ($post->image_path && ! str_starts_with($post->image_path, 'http')) {
                Storage::disk('public')->delete($post->image_path);
            }
            if ($post->video_path && ! str_starts_with($post->video_path, 'http')) {
                Storage::disk('public')->delete($post->video_path);
            }
            $post->delete();
        });

        return response()->json(['ok' => true]);
    }

    public function profile(Request $request, string $username): JsonResponse
    {
        $user = User::query()->where('username', $username)->firstOrFail();
        $viewer = $request->user();
        $viewerId = $viewer?->id;

        $posts = CommunityPost::query()
            ->visible()
            ->roots()
            ->where('user_id', $user->id)
            ->with(['user', 'article', 'event', 'vehicle.brand'])
            ->when($viewerId, fn ($q) => $q->with(['likes' => fn ($q) => $q->where('user_id', $viewerId)]))
            ->latest()
            ->paginate(20)
            ->through(fn (CommunityPost $post) => $post->toCardArray($viewerId));

        return response()->json([
            'profile' => [
                ...$user->toPublicArray(true),
                'posts_count' => CommunityPost::query()->visible()->roots()->where('user_id', $user->id)->count(),
                'followers_count' => $user->followers()->count(),
                'following_count' => $user->following()->count(),
                'is_self' => $viewer?->id === $user->id,
                'is_following' => $viewer ? $viewer->isFollowing($user) : false,
                'is_mutual_friend' => $viewer ? $viewer->isMutualFriendWith($user) : false,
                'can_message' => $viewer && $viewer->id !== $user->id && filled($user->username),
                'can_live_chat' => $viewer && $viewer->isMutualFriendWith($user),
            ],
            'posts' => $posts,
        ]);
    }

    public function follow(Request $request, string $username): JsonResponse
    {
        $target = User::query()->where('username', $username)->firstOrFail();
        $actor = $request->user();
        abort_if($actor->id === $target->id, 422, 'Tidak bisa mengikuti diri sendiri.');

        $existing = CommunityFollow::query()
            ->where('follower_id', $actor->id)
            ->where('following_id', $target->id)
            ->first();

        $following = false;
        if ($existing) {
            $existing->delete();
        } else {
            CommunityFollow::create([
                'follower_id' => $actor->id,
                'following_id' => $target->id,
            ]);
            CommunityNotifier::followed($actor, $target);
            $following = true;
        }

        return response()->json([
            'following' => $following,
            'is_mutual_friend' => $actor->fresh()->isMutualFriendWith($target),
        ]);
    }

    public function groups(Request $request): JsonResponse
    {
        $groups = CommunityGroup::query()
            ->orderByDesc('members_count')
            ->paginate(24)
            ->through(fn (CommunityGroup $g) => $g->toCardArray($request->user()));

        return response()->json($groups);
    }

    public function createGroup(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:80'],
            'description' => ['nullable', 'string', 'max:300'],
            'cover' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        $coverPath = $request->hasFile('cover')
            ? $request->file('cover')->store('community/groups', 'public')
            : null;

        $user = $request->user();
        $group = DB::transaction(function () use ($user, $data, $coverPath) {
            $group = CommunityGroup::create([
                'creator_id' => $user->id,
                'name' => trim($data['name']),
                'slug' => CommunityGroup::uniqueSlug($data['name']),
                'description' => $data['description'] ?? null,
                'cover_path' => $coverPath,
                'members_count' => 1,
            ]);
            CommunityGroupMember::create([
                'group_id' => $group->id,
                'user_id' => $user->id,
                'role' => 'owner',
            ]);

            return $group;
        });

        return response()->json(['group' => $group->toCardArray($user)], 201);
    }

    public function showGroup(Request $request, string $slug): JsonResponse
    {
        $group = CommunityGroup::query()->where('slug', $slug)->firstOrFail();
        $viewer = $request->user();
        $viewerId = $viewer?->id;

        $posts = CommunityPost::query()
            ->visible()
            ->roots()
            ->where('group_id', $group->id)
            ->with(['user', 'group', 'article', 'event', 'vehicle.brand'])
            ->when($viewerId, fn ($q) => $q->with(['likes' => fn ($q) => $q->where('user_id', $viewerId)]))
            ->latest()
            ->paginate(20)
            ->through(fn (CommunityPost $post) => $post->toCardArray($viewerId));

        $members = $group->memberships()
            ->with('user')
            ->orderByRaw("CASE role WHEN 'owner' THEN 0 WHEN 'admin' THEN 1 ELSE 2 END")
            ->limit(30)
            ->get()
            ->map(fn (CommunityGroupMember $m) => [
                ...($m->user?->toPublicArray() ?? []),
                'role' => $m->role,
            ])
            ->filter(fn ($m) => ! empty($m['id']))
            ->values();

        return response()->json([
            'group' => [
                ...$group->toCardArray($viewer),
                'is_owner' => $viewer && $group->creator_id === $viewer->id,
                'can_post' => $viewer && $group->isMember($viewer),
            ],
            'members' => $members,
            'posts' => $posts,
        ]);
    }

    public function joinGroup(Request $request, string $slug): JsonResponse
    {
        $group = CommunityGroup::query()->where('slug', $slug)->firstOrFail();
        $user = $request->user();
        $joined = false;

        DB::transaction(function () use ($group, $user, &$joined) {
            $existing = CommunityGroupMember::query()
                ->where('group_id', $group->id)
                ->where('user_id', $user->id)
                ->first();
            if ($existing) {
                if ($existing->role !== 'owner') {
                    $existing->delete();
                    $group->decrement('members_count');
                }
                $joined = false;
            } else {
                CommunityGroupMember::create([
                    'group_id' => $group->id,
                    'user_id' => $user->id,
                    'role' => 'member',
                ]);
                $group->increment('members_count');
                $joined = true;
            }
        });

        return response()->json([
            'joined' => $joined,
            'group' => $group->fresh()->toCardArray($user),
        ]);
    }

    public function inbox(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $conversations = CommunityConversation::query()
            ->where(function ($q) use ($userId) {
                $q->where('user_one_id', $userId)->orWhere('user_two_id', $userId);
            })
            ->with(['userOne', 'userTwo', 'latestMessage.sender'])
            ->orderByDesc('last_message_at')
            ->paginate(30)
            ->through(fn (CommunityConversation $c) => $c->toInboxArray($userId));

        return response()->json($conversations);
    }

    public function startMessage(Request $request, string $username): JsonResponse
    {
        $other = User::query()->where('username', $username)->firstOrFail();
        $me = $request->user();
        abort_if($me->id === $other->id, 422, 'Tidak bisa mengirim pesan ke diri sendiri.');
        $conversation = CommunityConversation::findOrCreateBetween($me, $other);

        return response()->json(['conversation_id' => $conversation->id]);
    }

    public function messages(Request $request, CommunityConversation $conversation): JsonResponse
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
            ->through(fn (CommunityMessage $m) => $m->toFeedArray($user->id));

        return response()->json([
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

    public function sendMessage(Request $request, CommunityConversation $conversation): JsonResponse
    {
        $user = $request->user();
        abort_unless($conversation->involves($user->id), 403);
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);
        $user->touchPresence();

        $message = DB::transaction(function () use ($conversation, $user, $data) {
            $message = CommunityMessage::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $user->id,
                'body' => trim($data['body']),
            ]);
            $conversation->forceFill(['last_message_at' => now()])->save();

            return $message->load('sender');
        });

        return response()->json(['message' => $message->toFeedArray($user->id)]);
    }

    public function pollMessages(Request $request, CommunityConversation $conversation): JsonResponse
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

        return response()->json([
            'messages' => $messages->map(fn (CommunityMessage $m) => $m->toFeedArray($user->id))->values(),
            'other_online' => $other?->fresh()?->isOnline() ?? false,
        ]);
    }

    public function liveFriends(Request $request): JsonResponse
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

    public function liveOpen(Request $request, string $username): JsonResponse
    {
        $me = $request->user();
        $friend = User::query()->where('username', $username)->firstOrFail();
        abort_unless($me->isMutualFriendWith($friend), 403, 'Live chat hanya untuk teman yang saling follow.');
        $conversation = CommunityConversation::findOrCreateBetween($me, $friend);

        return response()->json(['conversation_id' => $conversation->id]);
    }

    public function liveSend(Request $request, CommunityConversation $conversation): JsonResponse
    {
        $me = $request->user();
        abort_unless($conversation->involves($me->id), 403);
        $conversation->loadMissing(['userOne', 'userTwo']);
        $friend = $conversation->otherUser($me->id);
        abort_unless($friend && $me->isMutualFriendWith($friend), 403);

        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);
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

        return response()->json(['message' => $message->toFeedArray($me->id)]);
    }

    public function notifications(Request $request): JsonResponse
    {
        $notifications = CommunityNotification::query()
            ->where('user_id', $request->user()->id)
            ->with(['actor', 'post'])
            ->latest()
            ->paginate(30)
            ->through(fn (CommunityNotification $n) => $n->toFeedArray());

        return response()->json($notifications);
    }

    public function markNotificationsRead(Request $request): JsonResponse
    {
        CommunityNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function searchArticles(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $articles = Article::query()
            ->published()
            ->when($q !== '', fn ($query) => $query->search($q))
            ->orderByDesc('published_at')
            ->limit(8)
            ->get()
            ->map->toCardArray()
            ->values();

        return response()->json(['data' => $articles]);
    }

    public function searchEvents(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';
        $events = Event::query()
            ->published()
            ->when($q !== '', fn ($query) => $query->where('title', 'like', $like))
            ->orderByDesc('starts_at')
            ->limit(8)
            ->get()
            ->map->toCardArray()
            ->values();

        return response()->json(['data' => $events]);
    }

    public function searchVehicles(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';
        $vehicles = Vehicle::query()
            ->published()
            ->with('brand')
            ->when($q !== '', function ($query) use ($like) {
                $query->where(function ($inner) use ($like) {
                    $inner->where('name', 'like', $like)
                        ->orWhereHas('brand', fn ($brand) => $brand->where('name', 'like', $like));
                });
            })
            ->limit(8)
            ->get()
            ->map->toCardArray()
            ->values();

        return response()->json(['data' => $vehicles]);
    }

    private function storeMedia(Request $request): array
    {
        $imagePath = null;
        $videoPath = null;
        if ($request->hasFile('video')) {
            $videoPath = $request->file('video')->store('community/videos', 'public');
        } elseif ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('community/posts', 'public');
        }

        return [$imagePath, $videoPath];
    }
}
