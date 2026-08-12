<?php

namespace App\Services;

use App\Models\Article;
use App\Models\PushSubscription;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushNotifier
{
    public function enabled(): bool
    {
        return filled(config('webpush.vapid.public_key'))
            && filled(config('webpush.vapid.private_key'));
    }

    public function notifyArticle(Article $article): void
    {
        if (! $this->enabled()) {
            return;
        }

        $article->loadMissing('brands');
        $brandIds = $article->brands->pluck('id')->map(fn ($id) => (int) $id)->all();

        $subscriptions = PushSubscription::query()
            ->where(function ($query) use ($brandIds) {
                if ($brandIds !== []) {
                    foreach ($brandIds as $brandId) {
                        $query->orWhereJsonContains('brand_ids', $brandId);
                    }
                }
                $query->orWhere('wants_newsletter', true);
            })
            ->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        $brandNames = $article->brands->pluck('name')->filter()->implode(', ');
        $title = $brandNames !== '' ? $brandNames.' · Autoluz' : 'Autoluz';
        $body = $article->title;
        $url = route('articles.show', $article->slug);
        $icon = $article->toCardArray()['featured_image_url'] ?? url('/favicon.ico');

        $this->sendMany($subscriptions, [
            'title' => $title,
            'body' => $body,
            'url' => $url,
            'icon' => $icon,
            'tag' => 'article-'.$article->id,
        ]);
    }

    /**
     * @param  iterable<PushSubscription>  $subscriptions
     * @param  array{title:string,body:string,url?:string,icon?:string,tag?:string}  $payload
     */
    public function sendMany(iterable $subscriptions, array $payload): void
    {
        if (! $this->enabled()) {
            return;
        }

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => config('webpush.vapid.subject'),
                'publicKey' => config('webpush.vapid.public_key'),
                'privateKey' => config('webpush.vapid.private_key'),
            ],
        ]);

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        foreach ($subscriptions as $subscription) {
            $webPush->queueNotification(
                Subscription::create([
                    'endpoint' => $subscription->endpoint,
                    'publicKey' => $subscription->public_key,
                    'authToken' => $subscription->auth_token,
                    'contentEncoding' => $subscription->content_encoding ?: 'aesgcm',
                ]),
                $json
            );
        }

        foreach ($webPush->flush() as $report) {
            $endpoint = $report->getRequest()->getUri()->__toString();

            if ($report->isSuccess()) {
                PushSubscription::query()
                    ->where('endpoint', $endpoint)
                    ->update(['last_used_at' => now()]);
                continue;
            }

            $code = $report->getResponse()?->getStatusCode();
            if (in_array($code, [404, 410], true)) {
                PushSubscription::query()->where('endpoint', $endpoint)->delete();
                continue;
            }

            Log::warning('Web push failed', [
                'endpoint' => $endpoint,
                'reason' => $report->getReason(),
                'status' => $code,
            ]);
        }
    }
}
