<?php

namespace App\Http\Middleware;

use App\Support\NavBuilder;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'username' => $request->user()->username,
                    'email' => $request->user()->email,
                    'bio' => $request->user()->bio,
                    'avatar_url' => $request->user()->avatarUrl(),
                    'is_admin' => $request->user()->canAccessAdmin(),
                    'is_seller' => (bool) $request->user()->ownedStore,
                    'store' => $request->user()->ownedStore ? [
                        'id' => $request->user()->ownedStore->id,
                        'name' => $request->user()->ownedStore->name,
                        'slug' => $request->user()->ownedStore->slug,
                        'status' => $request->user()->ownedStore->status,
                    ] : null,
                    'is_seller' => (bool) $request->user()->ownedStore,
                    'store' => $request->user()->ownedStore ? [
                        'id' => $request->user()->ownedStore->id,
                        'name' => $request->user()->ownedStore->name,
                        'slug' => $request->user()->ownedStore->slug,
                        'status' => $request->user()->ownedStore->status,
                    ] : null,
                    'permissions' => $request->user()->loadMissing('role')->adminPermissionKeys(),
                    'role_name' => $request->user()->role?->name,
                    'unread_notifications' => $request->user()->communityNotifications()
                        ->whereNull('read_at')
                        ->count(),
                    'notifications_preview' => $request->user()->communityNotifications()
                        ->with(['actor', 'post'])
                        ->latest()
                        ->limit(5)
                        ->get()
                        ->map(fn ($n) => $n->toFeedArray())
                        ->values()
                        ->all(),
                    'notifications_total' => $request->user()->communityNotifications()->count(),
                    'unread_messages' => \App\Models\CommunityMessage::query()
                        ->whereNull('read_at')
                        ->where('sender_id', '!=', $request->user()->id)
                        ->whereHas('conversation', function ($q) use ($request) {
                            $uid = $request->user()->id;
                            $q->where(function ($q) use ($uid) {
                                $q->where('user_one_id', $uid)->orWhere('user_two_id', $uid);
                            });
                        })
                        ->count(),
                ] : null,
            ],
            'locale' => app()->getLocale(),
            'translations' => fn () => [
                'site' => trans('site'),
                'pagination' => [
                    'previous' => __('pagination.previous'),
                    'next' => __('pagination.next'),
                ],
            ],
            'nav' => fn () => NavBuilder::build(),
            'navCategories' => fn () => NavBuilder::build()['primary'] ?? [],
            'social' => [
                'youtube' => config('social.youtube'),
                'instagram' => config('social.instagram'),
                'facebook' => config('social.facebook'),
                'tiktok' => config('social.tiktok'),
            ],
            'youtubeChannel' => [
                'id' => config('youtube.channel_id'),
                'name' => config('youtube.channel_name'),
                'url' => config('youtube.channel_url'),
            ],
            'webpush' => [
                'vapidPublicKey' => config('webpush.vapid.public_key'),
                'enabled' => filled(config('webpush.vapid.public_key'))
                    && filled(config('webpush.vapid.private_key')),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
            ],
            'cart' => function () use ($request) {
                try {
                    return app(\App\Services\CartService::class)
                        ->summaryForRequest($request->user(), $request);
                } catch (\Throwable) {
                    return [
                        'count' => 0,
                        'subtotal' => 0,
                        'subtotal_label' => 'Rp 0',
                        'weight_grams' => 0,
                        'items' => [],
                        'groups' => [],
                    ];
                }
            },
        ];
    }
}
