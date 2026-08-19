<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventShareController extends Controller
{
    public function store(Request $request, string $slug): JsonResponse
    {
        $request->validate([
            'channel' => ['nullable', 'string', 'max:40'],
        ]);

        $event = Event::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        $event->increment('shares_count');

        return response()->json([
            'shares_count' => (int) $event->fresh()->shares_count,
        ]);
    }
}
