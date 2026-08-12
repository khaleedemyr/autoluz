<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:160'],
            'name' => ['nullable', 'string', 'max:120'],
        ]);

        $subscriber = NewsletterSubscriber::query()->firstOrNew([
            'email' => strtolower(trim($data['email'])),
        ]);

        $subscriber->name = $data['name'] ?? $subscriber->name;
        $subscriber->is_active = true;
        $subscriber->subscribed_at = now();
        $subscriber->unsubscribed_at = null;
        $subscriber->save();

        return back()->with('success', __('site.newsletter_success'));
    }
}
