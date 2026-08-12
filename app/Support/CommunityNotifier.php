<?php

namespace App\Support;

use App\Models\CommunityActivityLog;
use App\Models\CommunityNotification;
use App\Models\CommunityPost;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CommunityNotifier
{
    public static function liked(User $actor, CommunityPost $post): void
    {
        self::log('like', $actor, $post);

        if ($post->user_id === $actor->id) {
            return;
        }

        $snippet = self::snippet($post->body);
        $label = $actor->username ? '@'.$actor->username : $actor->name;

        CommunityNotification::create([
            'user_id' => $post->user_id,
            'actor_id' => $actor->id,
            'post_id' => $post->id,
            'type' => 'like',
            'message' => "{$label} menyukai postinganmu: \"{$snippet}\"",
        ]);
    }

    public static function replied(User $actor, CommunityPost $parent, CommunityPost $reply, ?CommunityPost $root = null): void
    {
        $root = $root ?: ($parent->parent_id ? self::resolveRoot($parent) : $parent);

        self::log('reply', $actor, $reply, [
            'parent_post_id' => $parent->id,
            'root_post_id' => $root?->id,
        ]);

        if ($parent->user_id === $actor->id) {
            return;
        }

        $snippet = self::snippet($reply->body);
        $label = $actor->username ? '@'.$actor->username : $actor->name;
        $isNested = $parent->parent_id !== null;

        CommunityNotification::create([
            'user_id' => $parent->user_id,
            'actor_id' => $actor->id,
            'post_id' => $reply->id,
            'type' => 'reply',
            'message' => $isNested
                ? "{$label} membalas komentarmu: \"{$snippet}\""
                : "{$label} membalas postinganmu: \"{$snippet}\"",
        ]);
    }

    private static function resolveRoot(CommunityPost $post): CommunityPost
    {
        $current = $post;
        $guard = 0;

        while ($current->parent_id && $guard < 20) {
            $parent = $current->relationLoaded('parent')
                ? $current->parent
                : CommunityPost::query()->find($current->parent_id);

            if (! $parent) {
                break;
            }

            $current = $parent;
            $guard++;
        }

        return $current;
    }

    public static function followed(User $actor, User $target): void
    {
        self::log('follow', $actor, null, [
            'following_id' => $target->id,
        ]);

        if ($target->id === $actor->id) {
            return;
        }

        $label = $actor->username ? '@'.$actor->username : $actor->name;

        CommunityNotification::create([
            'user_id' => $target->id,
            'actor_id' => $actor->id,
            'post_id' => null,
            'type' => 'follow',
            'message' => "{$label} mulai mengikuti Anda",
        ]);
    }

    public static function log(string $action, ?User $actor, ?CommunityPost $post, array $meta = []): void
    {
        CommunityActivityLog::create([
            'actor_id' => $actor?->id,
            'post_id' => $post?->id,
            'action' => $action,
            'meta' => $meta ?: null,
        ]);

        Log::info('community.'.$action, [
            'actor_id' => $actor?->id,
            'post_id' => $post?->id,
            'meta' => $meta,
        ]);
    }

    private static function snippet(string $body): string
    {
        return Str::limit(trim(preg_replace('/\s+/', ' ', $body) ?? ''), 80);
    }
}
