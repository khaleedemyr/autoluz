<?php

namespace App\Http\Controllers;

use App\Models\CommunityFollow;
use App\Models\User;
use App\Support\CommunityNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommunityFollowController extends Controller
{
    public function toggle(Request $request, string $username): RedirectResponse
    {
        $target = User::query()->where('username', $username)->firstOrFail();
        $actor = $request->user();

        abort_if($actor->id === $target->id, 422, 'Tidak bisa mengikuti diri sendiri.');

        $existing = CommunityFollow::query()
            ->where('follower_id', $actor->id)
            ->where('following_id', $target->id)
            ->first();

        if ($existing) {
            $existing->delete();
            CommunityNotifier::log('unfollow', $actor, null, [
                'following_id' => $target->id,
            ]);

            return back()->with('success', __('Berhenti mengikuti :name.', ['name' => $target->name]));
        }

        CommunityFollow::create([
            'follower_id' => $actor->id,
            'following_id' => $target->id,
        ]);

        CommunityNotifier::followed($actor, $target);

        return back()->with('success', __('Mengikuti :name.', ['name' => $target->name]));
    }
}
