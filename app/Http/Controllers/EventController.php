<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(): Response
    {
        $upcoming = Event::query()
            ->published()
            ->upcoming()
            ->limit(20)
            ->get()
            ->map->toCardArray()
            ->values();

        $past = Event::query()
            ->published()
            ->past()
            ->limit(8)
            ->get()
            ->map->toCardArray()
            ->values();

        return Inertia::render('Events/Index', [
            'upcoming' => $upcoming,
            'past' => $past,
            'hero' => $upcoming->first(),
        ]);
    }

    public function show(string $slug): Response
    {
        $event = Event::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        $related = Event::query()
            ->published()
            ->where('id', '!=', $event->id)
            ->orderByRaw('CASE WHEN starts_at >= ? THEN 0 ELSE 1 END', [now()->startOfDay()])
            ->orderBy('starts_at')
            ->limit(3)
            ->get()
            ->map->toCardArray()
            ->values();

        return Inertia::render('Events/Show', [
            'event' => $event->toDetailArray(),
            'related' => $related,
        ]);
    }
}
