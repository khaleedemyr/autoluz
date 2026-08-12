<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'url', 'max:500'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'contentEncoding' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:160'],
            'brand_ids' => ['nullable', 'array'],
            'brand_ids.*' => ['integer'],
            'wants_newsletter' => ['sometimes', 'boolean'],
        ]);

        $subscription = PushSubscription::query()->firstOrNew([
            'endpoint' => $data['endpoint'],
        ]);

        $subscription->public_key = $data['keys']['p256dh'];
        $subscription->auth_token = $data['keys']['auth'];
        $subscription->content_encoding = $data['contentEncoding'] ?? 'aes128gcm';
        $subscription->locale = app()->getLocale();
        $subscription->user_agent = substr((string) $request->userAgent(), 0, 500);
        $subscription->last_used_at = now();

        if ($request->has('brand_ids')) {
            $subscription->brand_ids = collect($data['brand_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values()
                ->all();
        }

        if ($request->has('wants_newsletter')) {
            $subscription->wants_newsletter = (bool) $data['wants_newsletter'];
        }

        if ($request->filled('email')) {
            $subscription->email = strtolower(trim((string) $data['email']));
        }

        $subscription->save();

        return response()->json([
            'ok' => true,
            'id' => $subscription->id,
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'url', 'max:500'],
        ]);

        PushSubscription::query()->where('endpoint', $data['endpoint'])->delete();

        return response()->json(['ok' => true]);
    }
}
