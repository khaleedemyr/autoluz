<?php

namespace App\Http\Controllers;

use App\Models\CommunityGroup;
use App\Models\CommunityGroupMember;
use App\Models\CommunityPost;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CommunityGroupController extends Controller
{
    public function index(Request $request): Response
    {
        $viewer = $request->user();

        $groups = CommunityGroup::query()
            ->orderByDesc('members_count')
            ->orderByDesc('created_at')
            ->paginate(24)
            ->withQueryString()
            ->through(fn (CommunityGroup $g) => $g->toCardArray($viewer));

        return Inertia::render('Community/Groups/Index', [
            'groups' => $groups,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Community/Groups/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:80'],
            'description' => ['nullable', 'string', 'max:300'],
            'cover' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        $user = $request->user();
        $coverPath = null;
        if ($request->hasFile('cover')) {
            $coverPath = $request->file('cover')->store('community/groups', 'public');
        }

        $group = DB::transaction(function () use ($user, $data, $coverPath) {
            $group = CommunityGroup::create([
                'creator_id' => $user->id,
                'name' => trim($data['name']),
                'slug' => CommunityGroup::uniqueSlug($data['name']),
                'description' => isset($data['description']) ? trim($data['description']) : null,
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

        return redirect()
            ->route('community.groups.show', $group->slug)
            ->with('success', __('Grup berhasil dibuat.'));
    }

    public function show(Request $request, CommunityGroup $group): Response
    {
        $viewer = $request->user();
        $viewerId = $viewer?->id;

        $posts = CommunityPost::query()
            ->visible()
            ->roots()
            ->where('group_id', $group->id)
            ->with(['user', 'group'])
            ->when(
                $viewerId,
                fn ($q) => $q->with(['likes' => fn ($q) => $q->where('user_id', $viewerId)]),
            )
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (CommunityPost $post) => $post->toCardArray($viewerId));

        $members = $group->memberships()
            ->with('user')
            ->orderByRaw("CASE role WHEN 'owner' THEN 0 WHEN 'admin' THEN 1 ELSE 2 END")
            ->orderBy('created_at')
            ->limit(30)
            ->get()
            ->map(fn (CommunityGroupMember $m) => [
                ...($m->user?->toPublicArray() ?? []),
                'role' => $m->role,
            ])
            ->filter(fn ($m) => ! empty($m['id']))
            ->values();

        return Inertia::render('Community/Groups/Show', [
            'group' => [
                ...$group->toCardArray($viewer),
                'is_owner' => $viewer && $group->creator_id === $viewer->id,
                'can_post' => $viewer && $group->isMember($viewer),
                'can_add_member' => $viewer && $group->canManageMembers($viewer),
                'can_edit' => $viewer && $group->canManageMembers($viewer),
            ],
            'members' => $members,
            'posts' => $posts,
        ]);
    }

    public function edit(Request $request, CommunityGroup $group): Response
    {
        abort_unless($group->canManageMembers($request->user()), 403);

        return Inertia::render('Community/Groups/Edit', [
            'group' => $group->toCardArray($request->user()),
        ]);
    }

    public function update(Request $request, CommunityGroup $group): RedirectResponse
    {
        abort_unless($group->canManageMembers($request->user()), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:80'],
            'description' => ['nullable', 'string', 'max:300'],
            'cover' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'remove_cover' => ['nullable', 'boolean'],
        ]);

        $name = trim($data['name']);
        $description = array_key_exists('description', $data)
            ? (filled($data['description'] ?? null) ? trim($data['description']) : null)
            : $group->description;

        $payload = [
            'name' => $name,
            'description' => $description,
        ];

        if ($name !== $group->name) {
            $payload['slug'] = CommunityGroup::uniqueSlug($name, $group->id);
        }

        if ($request->boolean('remove_cover') && $group->cover_path) {
            if (! str_starts_with($group->cover_path, 'http')) {
                Storage::disk('public')->delete($group->cover_path);
            }
            $payload['cover_path'] = null;
        }

        if ($request->hasFile('cover')) {
            if ($group->cover_path && ! str_starts_with($group->cover_path, 'http')) {
                Storage::disk('public')->delete($group->cover_path);
            }
            $payload['cover_path'] = $request->file('cover')->store('community/groups', 'public');
        }

        $group->update($payload);

        return redirect()
            ->route('community.groups.show', $group->fresh()->slug)
            ->with('success', __('Pengaturan grup berhasil disimpan.'));
    }

    public function searchUsers(Request $request, CommunityGroup $group): JsonResponse
    {
        $viewer = $request->user();
        abort_unless($group->canManageMembers($viewer), 403);

        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['users' => []]);
        }

        $memberIds = CommunityGroupMember::query()
            ->where('group_id', $group->id)
            ->pluck('user_id');

        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';

        $users = User::query()
            ->whereNotIn('id', $memberIds)
            ->where(function ($query) use ($like) {
                $query->where('name', 'like', $like)
                    ->orWhere('username', 'like', $like)
                    ->orWhere('email', 'like', $like);
            })
            ->orderBy('name')
            ->limit(12)
            ->get()
            ->map(fn (User $u) => [
                ...$u->toPublicArray(),
                'email' => $u->email,
            ])
            ->values();

        return response()->json(['users' => $users]);
    }

    public function addMember(Request $request, CommunityGroup $group): RedirectResponse|JsonResponse
    {
        $viewer = $request->user();
        abort_unless($group->canManageMembers($viewer), 403, 'Hanya owner/admin yang bisa menambah anggota.');

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $target = User::query()->findOrFail($data['user_id']);
        abort_if($target->id === $viewer->id, 422, 'Tidak bisa menambahkan diri sendiri.');

        $added = false;

        DB::transaction(function () use ($group, $target, &$added) {
            $exists = CommunityGroupMember::query()
                ->where('group_id', $group->id)
                ->where('user_id', $target->id)
                ->exists();

            if ($exists) {
                return;
            }

            CommunityGroupMember::create([
                'group_id' => $group->id,
                'user_id' => $target->id,
                'role' => 'member',
            ]);
            $group->increment('members_count');
            $added = true;
        });

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'added' => $added,
                'user' => [
                    ...$target->toPublicArray(),
                    'role' => 'member',
                ],
                'members_count' => (int) $group->fresh()->members_count,
            ]);
        }

        return back()->with(
            'success',
            $added ? __('Anggota berhasil ditambahkan.') : __('User sudah menjadi anggota.')
        );
    }

    public function join(Request $request, CommunityGroup $group): RedirectResponse
    {
        $user = $request->user();
        $action = 'joined';

        DB::transaction(function () use ($group, $user, &$action) {
            $existing = CommunityGroupMember::query()
                ->where('group_id', $group->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existing) {
                $action = 'left';

                if ($existing->role === 'owner') {
                    $successor = CommunityGroupMember::query()
                        ->where('group_id', $group->id)
                        ->where('user_id', '!=', $user->id)
                        ->orderBy('created_at')
                        ->first();

                    if ($successor) {
                        $successor->forceFill(['role' => 'owner'])->save();
                        $group->forceFill(['creator_id' => $successor->user_id])->save();
                        $existing->delete();
                        $group->decrement('members_count');
                    } else {
                        $group->delete();
                        $action = 'deleted';
                    }

                    return;
                }

                $existing->delete();
                if ($group->members_count > 0) {
                    $group->decrement('members_count');
                }
            } else {
                CommunityGroupMember::create([
                    'group_id' => $group->id,
                    'user_id' => $user->id,
                    'role' => 'member',
                ]);
                $group->increment('members_count');
            }
        });

        if ($action === 'deleted') {
            return redirect()
                ->route('community.groups.index')
                ->with('success', __('Grup dihapus karena tidak ada anggota tersisa.'));
        }

        return back()->with(
            'success',
            $action === 'left' ? __('Berhasil keluar dari grup.') : __('Berhasil bergabung ke grup.')
        );
    }
}
