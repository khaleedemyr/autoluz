<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\YoutubeFeed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VideoController extends Controller
{
    public function index(): Response
    {
        $videos = YoutubeFeed::playlist();

        return Inertia::render('Admin/Videos/Index', [
            'videos' => $videos,
            'channel' => [
                'id' => config('youtube.channel_id'),
                'name' => config('youtube.channel_name'),
                'url' => config('youtube.channel_url'),
            ],
            'cacheTtlMinutes' => (int) ceil(max(60, (int) config('youtube.cache_ttl', 1800)) / 60),
        ]);
    }

    public function refresh(Request $request): RedirectResponse
    {
        $videos = YoutubeFeed::refresh();

        if ($videos === []) {
            return back()->withErrors([
                'feed' => 'Gagal mengambil feed YouTube. Coba lagi nanti.',
            ]);
        }

        return back()->with('success', 'Playlist YouTube diperbarui ('.count($videos).' video). Video baru langsung muncul di homepage.');
    }
}
