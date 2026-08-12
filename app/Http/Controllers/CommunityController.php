<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\CommunityGroup;
use App\Models\CommunityLike;
use App\Models\CommunityPost;
use App\Models\Event;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\CommunityNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CommunityController extends Controller
{
    public function index(Request $request): Response
    {
        $viewer = $request->user();
        $viewerId = $viewer?->id;

        $posts = CommunityPost::query()
            ->visible()
            ->roots()
            ->whereNull('group_id')
            ->with(['user', 'article', 'event', 'vehicle.brand'])
            ->when(
                $viewerId,
                fn ($q) => $q->with(['likes' => fn ($q) => $q->where('user_id', $viewerId)]),
            )
            ->latest()
            ->paginate(20)
            ->withQueryString()
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

        $myGroupIds = $myGroups->pluck('id')->all();

        $discoverGroups = CommunityGroup::query()
            ->when($myGroupIds, fn ($q) => $q->whereNotIn('id', $myGroupIds))
            ->orderByDesc('members_count')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->map(fn (CommunityGroup $g) => $g->toCardArray($viewer))
            ->values();

        return Inertia::render('Community/Index', [
            'posts' => $posts,
            'my_groups' => $myGroups,
            'discover_groups' => $discoverGroups,
            'authRequiredMessage' => session('auth_required'),
        ]);
    }

    public function show(Request $request, CommunityPost $post): Response
    {
        abort_if($post->is_hidden, 404);
        abort_if($post->parent_id !== null, 404);

        $viewerId = $request->user()?->id;

        $post->load([
            'user',
            'article',
            'event',
            'vehicle.brand',
            'replies' => fn ($q) => $q->visible()->with([
                'user',
                'parent.user',
                'article',
                'event',
                'vehicle.brand',
                'replies' => fn ($q) => $q->visible()->with([
                    'user',
                    'parent.user',
                    'article',
                    'event',
                    'vehicle.brand',
                    'replies' => fn ($q) => $q->visible()->with(['user', 'parent.user', 'article', 'event', 'vehicle.brand']),
                ]),
            ])->oldest(),
        ]);

        if ($viewerId) {
            $post->load([
                'likes' => fn ($q) => $q->where('user_id', $viewerId),
                'replies.likes' => fn ($q) => $q->where('user_id', $viewerId),
                'replies.replies.likes' => fn ($q) => $q->where('user_id', $viewerId),
                'replies.replies.replies.likes' => fn ($q) => $q->where('user_id', $viewerId),
            ]);
        }

        $replies = $post->replies
            ->map(fn (CommunityPost $reply) => $reply->toCardArray($viewerId, 1))
            ->values();

        $post->unsetRelation('replies');

        return Inertia::render('Community/Show', [
            'post' => $post->toCardArray($viewerId),
            'replies' => $replies,
        ]);
    }

    public function profile(Request $request, string $username): Response
    {
        $user = User::query()->where('username', $username)->firstOrFail();
        $viewerId = $request->user()?->id;

        $posts = CommunityPost::query()
            ->visible()
            ->roots()
            ->where('user_id', $user->id)
            ->with(['user', 'article', 'event', 'vehicle.brand'])
            ->when(
                $viewerId,
                fn ($q) => $q->with(['likes' => fn ($q) => $q->where('user_id', $viewerId)]),
            )
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (CommunityPost $post) => $post->toCardArray($viewerId));

        $postsCount = CommunityPost::query()
            ->visible()
            ->roots()
            ->where('user_id', $user->id)
            ->count();

        $viewer = $request->user();

        return Inertia::render('Community/Profile', [
            'profile' => [
                ...$user->toPublicArray(),
                'posts_count' => $postsCount,
                'followers_count' => $user->followers()->count(),
                'following_count' => $user->following()->count(),
                'is_self' => $viewer?->id === $user->id,
                'is_following' => $viewer ? $viewer->isFollowing($user) : false,
                'is_mutual_friend' => $viewer ? $viewer->isMutualFriendWith($user) : false,
                'is_online' => $user->isOnline(),
                'can_message' => $viewer && $viewer->id !== $user->id && filled($user->username),
                'can_live_chat' => $viewer && $viewer->isMutualFriendWith($user) && filled($user->username),
            ],
            'posts' => $posts,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:500'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
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

        $tags = $this->resolvePublishedTags($data);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('community/posts', 'public');
        }

        $post = DB::transaction(function () use ($user, $data, $imagePath, $group, $tags) {
            $post = CommunityPost::create([
                'user_id' => $user->id,
                'group_id' => $group?->id,
                'article_id' => $tags['article_id'],
                'event_id' => $tags['event_id'],
                'vehicle_id' => $tags['vehicle_id'],
                'body' => trim($data['body']),
                'image_path' => $imagePath,
            ]);

            if ($group) {
                $group->increment('posts_count');
            }

            return $post;
        });

        CommunityNotifier::log('post', $user, $post);

        if ($group) {
            return redirect()
                ->route('community.groups.show', $group->slug)
                ->with('success', __('Post berhasil dipublikasikan.'));
        }

        return redirect()
            ->route('community.show', $post->id)
            ->with('success', __('Post berhasil dipublikasikan.'));
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
            ->map(fn (Article $article) => [
                'id' => $article->id,
                'title' => $article->title,
                'excerpt' => $article->excerpt,
                'featured_image_url' => $article->toCardArray()['featured_image_url'] ?? null,
                'url' => route('articles.show', $article->slug),
            ])
            ->values();

        return response()->json(['data' => $articles]);
    }

    public function searchEvents(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        $events = Event::query()
            ->published()
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('title', 'like', $like)
                        ->orWhere('location', 'like', $like)
                        ->orWhere('city', 'like', $like)
                        ->orWhere('venue', 'like', $like);
                });
            })
            ->orderByDesc('starts_at')
            ->limit(8)
            ->get()
            ->map(fn (Event $event) => [
                'id' => $event->id,
                'title' => $event->title,
                'excerpt' => $event->excerpt,
                'cover_image_url' => $event->toCardArray()['cover_image_url'] ?? null,
                'starts_at_label' => optional($event->starts_at)?->translatedFormat('d M Y'),
                'url' => route('events.show', $event->slug),
            ])
            ->values();

        return response()->json(['data' => $events]);
    }

    public function searchVehicles(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        $vehicles = Vehicle::query()
            ->published()
            ->with('brand')
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('name', 'like', $like)
                        ->orWhere('body_type', 'like', $like)
                        ->orWhereHas('brand', fn ($brand) => $brand->where('name', 'like', $like));
                });
            })
            ->orderByDesc('published_at')
            ->limit(8)
            ->get()
            ->map(function (Vehicle $vehicle) {
                $card = $vehicle->toCardArray();

                return [
                    'id' => $vehicle->id,
                    'title' => $vehicle->name,
                    'name' => $vehicle->name,
                    'excerpt' => trim(($card['brand']['name'] ?? '').' '.($vehicle->model_year ? (string) $vehicle->model_year : '')),
                    'cover_image_url' => $card['cover_image_url'] ?? null,
                    'price_label' => $card['price_label'] ?? null,
                    'brand_name' => $card['brand']['name'] ?? null,
                    'url' => $card['url'] ?? null,
                ];
            })
            ->values();

        return response()->json(['data' => $vehicles]);
    }

    public function reply(Request $request, CommunityPost $post): RedirectResponse
    {
        abort_if($post->is_hidden, 404);

        $parent = $post;
        $root = $post->parent_id === null ? $post : CommunityPost::query()->findOrFail($post->rootId());
        abort_if($root->is_hidden, 404);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:500'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'article_id' => ['nullable', 'integer', 'exists:articles,id'],
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
        ]);

        $tags = $this->resolvePublishedTags($data);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('community/posts', 'public');
        }

        $reply = null;

        DB::transaction(function () use ($request, $parent, $root, $data, $imagePath, $tags, &$reply) {
            $reply = CommunityPost::create([
                'user_id' => $request->user()->id,
                'parent_id' => $parent->id,
                'group_id' => $root->group_id,
                'article_id' => $tags['article_id'],
                'event_id' => $tags['event_id'],
                'vehicle_id' => $tags['vehicle_id'],
                'body' => trim($data['body']),
                'image_path' => $imagePath,
            ]);

            $parent->increment('replies_count');
            if ($parent->id !== $root->id) {
                $root->increment('replies_count');
            }
        });

        CommunityNotifier::replied($request->user(), $parent, $reply, $root);

        return redirect()
            ->route('community.show', $root->id)
            ->with('success', __('Balasan terkirim.'));
    }

    /**
     * @param  array{article_id?: mixed, event_id?: mixed, vehicle_id?: mixed}  $data
     * @return array{article_id: int|null, event_id: int|null, vehicle_id: int|null}
     */
    private function resolvePublishedTags(array $data): array
    {
        $articleId = null;
        if (! empty($data['article_id'])) {
            $articleId = Article::query()->published()->whereKey($data['article_id'])->value('id');
        }

        $eventId = null;
        if (! empty($data['event_id'])) {
            $eventId = Event::query()->published()->whereKey($data['event_id'])->value('id');
        }

        $vehicleId = null;
        if (! empty($data['vehicle_id'])) {
            $vehicleId = Vehicle::query()->published()->whereKey($data['vehicle_id'])->value('id');
        }

        return [
            'article_id' => $articleId,
            'event_id' => $eventId,
            'vehicle_id' => $vehicleId,
        ];
    }

    public function like(Request $request, CommunityPost $post): RedirectResponse|JsonResponse
    {
        abort_if($post->is_hidden, 404);

        $user = $request->user();
        $userId = $user->id;
        $liked = false;

        DB::transaction(function () use ($post, $user, $userId, &$liked) {
            $existing = CommunityLike::query()
                ->where('user_id', $userId)
                ->where('post_id', $post->id)
                ->first();

            if ($existing) {
                $existing->delete();
                $post->decrement('likes_count');
                $liked = false;
                CommunityNotifier::log('unlike', $user, $post);
            } else {
                CommunityLike::create([
                    'user_id' => $userId,
                    'post_id' => $post->id,
                ]);
                $post->increment('likes_count');
                $liked = true;
                CommunityNotifier::liked($user, $post);
            }
        });

        $post->refresh();

        if ($request->wantsJson()) {
            return response()->json([
                'liked' => $liked,
                'likes_count' => (int) $post->likes_count,
            ]);
        }

        return back();
    }

    public function destroy(Request $request, CommunityPost $post): RedirectResponse
    {
        $user = $request->user();
        abort_unless($post->user_id === $user->id || $user->is_admin, 403);

        $parentId = $post->parent_id;
        $rootId = $post->parent_id ? $post->rootId() : null;
        $redirectTo = $rootId
            ? route('community.show', $rootId)
            : ($parentId ? route('community.show', $parentId) : route('community.index'));

        DB::transaction(function () use ($post, $parentId, $rootId) {
            if ($post->image_path && ! str_starts_with($post->image_path, 'http')) {
                Storage::disk('public')->delete($post->image_path);
            }

            if ($parentId) {
                CommunityPost::query()->whereKey($parentId)->decrement('replies_count');
                if ($rootId && $rootId !== $parentId) {
                    CommunityPost::query()->whereKey($rootId)->decrement('replies_count');
                }
            }

            $post->delete();
        });

        return redirect($redirectTo)->with('success', __('Post dihapus.'));
    }

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        $path = $request->file('image')->store('community/posts', 'public');

        return response()->json([
            'path' => $path,
            'url' => url('/storage/'.$path),
        ]);
    }
}
