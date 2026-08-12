<?php

namespace App\Http\Controllers;

use App\Models\CommunityNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommunityNotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $notifications = CommunityNotification::query()
            ->where('user_id', $request->user()->id)
            ->with(['actor', 'post'])
            ->latest()
            ->paginate(30)
            ->withQueryString()
            ->through(fn (CommunityNotification $n) => $n->toFeedArray());

        return Inertia::render('Community/Notifications', [
            'notifications' => $notifications,
        ]);
    }

    public function markRead(Request $request, CommunityNotification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        if ($notification->read_at === null) {
            $notification->forceFill(['read_at' => now()])->save();
        }

        $notification->loadMissing(['post', 'actor']);
        $url = $notification->toFeedArray()['url'];

        return redirect($url);
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        CommunityNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('success', __('Semua notifikasi ditandai dibaca.'));
    }
}
