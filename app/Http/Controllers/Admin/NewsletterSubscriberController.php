<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NewsletterSubscriberController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');

        $subscribers = NewsletterSubscriber::query()
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('email', 'like', $like)->orWhere('name', 'like', $like);
                });
            })
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderByDesc('subscribed_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (NewsletterSubscriber $s) => [
                'id' => $s->id,
                'email' => $s->email,
                'name' => $s->name,
                'is_active' => $s->is_active,
                'subscribed_at' => optional($s->subscribed_at)?->format('Y-m-d H:i'),
            ]);

        return Inertia::render('Admin/Newsletter/Index', [
            'subscribers' => $subscribers,
            'filters' => ['q' => $q, 'status' => $status],
            'stats' => [
                'total' => NewsletterSubscriber::query()->count(),
                'active' => NewsletterSubscriber::query()->where('is_active', true)->count(),
            ],
        ]);
    }

    public function toggle(NewsletterSubscriber $subscriber): RedirectResponse
    {
        $subscriber->is_active = ! $subscriber->is_active;
        $subscriber->unsubscribed_at = $subscriber->is_active ? null : now();
        if ($subscriber->is_active && ! $subscriber->subscribed_at) {
            $subscriber->subscribed_at = now();
        }
        $subscriber->save();

        return back()->with('success', 'Status subscriber diperbarui.');
    }

    public function destroy(NewsletterSubscriber $subscriber): RedirectResponse
    {
        $subscriber->delete();

        return back()->with('success', 'Subscriber dihapus.');
    }
}
