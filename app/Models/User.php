<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable(['name', 'username', 'email', 'password', 'is_admin', 'bio', 'avatar_path'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'last_seen_at' => 'datetime',
        ];
    }

    public const ONLINE_THRESHOLD_SECONDS = 120;

    public function communityPosts(): HasMany
    {
        return $this->hasMany(CommunityPost::class);
    }

    public function communityLikes(): HasMany
    {
        return $this->hasMany(CommunityLike::class);
    }

    public function communityNotifications(): HasMany
    {
        return $this->hasMany(CommunityNotification::class);
    }

    public function following(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'community_follows', 'follower_id', 'following_id')
            ->withTimestamps();
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'community_follows', 'following_id', 'follower_id')
            ->withTimestamps();
    }

    public function isFollowing(self $user): bool
    {
        if ($this->id === $user->id) {
            return false;
        }

        return CommunityFollow::query()
            ->where('follower_id', $this->id)
            ->where('following_id', $user->id)
            ->exists();
    }

    public function isMutualFriendWith(self $user): bool
    {
        return $this->id !== $user->id
            && $this->isFollowing($user)
            && $user->isFollowing($this);
    }

    public function isOnline(): bool
    {
        if (! $this->last_seen_at) {
            return false;
        }

        return $this->last_seen_at->gte(now()->subSeconds(self::ONLINE_THRESHOLD_SECONDS));
    }

    public function touchPresence(): void
    {
        $this->forceFill(['last_seen_at' => now()])->saveQuietly();
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    public function mutualFriends()
    {
        $followingIds = CommunityFollow::query()
            ->where('follower_id', $this->id)
            ->pluck('following_id');

        $followerIds = CommunityFollow::query()
            ->where('following_id', $this->id)
            ->pluck('follower_id');

        $mutualIds = $followingIds->intersect($followerIds)->values();

        if ($mutualIds->isEmpty()) {
            return collect();
        }

        return self::query()
            ->whereIn('id', $mutualIds)
            ->whereNotNull('username')
            ->orderBy('name')
            ->get();
    }

    public function onlineMutualFriends()
    {
        return $this->mutualFriends()
            ->filter(fn (self $user) => $user->isOnline())
            ->values();
    }

    public function avatarUrl(): ?string
    {
        if (! $this->avatar_path) {
            return null;
        }

        if (str_starts_with($this->avatar_path, 'http://') || str_starts_with($this->avatar_path, 'https://')) {
            return $this->avatar_path;
        }

        return url('/storage/'.$this->avatar_path);
    }

    public function toPublicArray(bool $withPresence = false): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'bio' => $this->bio,
            'avatar_url' => $this->avatarUrl(),
            'url' => $this->username
                ? route('community.profile', $this->username)
                : null,
        ];

        if ($withPresence) {
            $data['is_online'] = $this->isOnline();
            $data['last_seen_at'] = optional($this->last_seen_at)?->toIso8601String();
        }

        return $data;
    }

    public static function uniqueUsername(string $seed, ?int $ignoreId = null): string
    {
        $base = Str::slug($seed) ?: 'user';
        $base = Str::limit($base, 30, '');
        $username = $base;
        $i = 2;

        while (
            static::query()
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where('username', $username)
                ->exists()
        ) {
            $username = Str::limit($base, 28, '').$i;
            $i++;
        }

        return $username;
    }
}
