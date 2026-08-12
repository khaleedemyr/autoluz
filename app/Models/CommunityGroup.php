<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CommunityGroup extends Model
{
    protected $fillable = [
        'creator_id',
        'name',
        'slug',
        'description',
        'cover_path',
        'members_count',
        'posts_count',
    ];

    protected function casts(): array
    {
        return [
            'members_count' => 'integer',
            'posts_count' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'community_group_members', 'group_id', 'user_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(CommunityGroupMember::class, 'group_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(CommunityPost::class, 'group_id');
    }

    public function coverUrl(): ?string
    {
        if (! $this->cover_path) {
            return null;
        }

        if (str_starts_with($this->cover_path, 'http://') || str_starts_with($this->cover_path, 'https://')) {
            return $this->cover_path;
        }

        return url('/storage/'.$this->cover_path);
    }

    public function isMember(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return CommunityGroupMember::query()
            ->where('group_id', $this->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    public function memberRole(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        return CommunityGroupMember::query()
            ->where('group_id', $this->id)
            ->where('user_id', $user->id)
            ->value('role');
    }

    public function canManageMembers(?User $user): bool
    {
        $role = $this->memberRole($user);

        return in_array($role, ['owner', 'admin'], true);
    }

    public function toCardArray(?User $viewer = null): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'cover_url' => $this->coverUrl(),
            'members_count' => (int) $this->members_count,
            'posts_count' => (int) $this->posts_count,
            'is_member' => $this->isMember($viewer),
            'url' => route('community.groups.show', $this->slug),
        ];
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'group';
        $base = Str::limit($base, 80, '');
        $slug = $base;
        $i = 2;

        while (
            static::query()
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = Str::limit($base, 70, '').'-'.$i;
            $i++;
        }

        return $slug;
    }
}
