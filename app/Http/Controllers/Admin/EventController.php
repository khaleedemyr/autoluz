<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));
        $perPage = max(10, min(50, (int) $request->query('per_page', 15)));

        $events = Event::query()
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('title', 'like', $like)
                        ->orWhere('location', 'like', $like)
                        ->orWhere('city', 'like', $like)
                        ->orWhere('venue', 'like', $like);
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderByDesc('starts_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Event $event) => [
                ...$event->toCardArray(),
                'status' => $event->status,
                'is_featured' => $event->is_featured,
            ]);

        return Inertia::render('Admin/Events/Index', [
            'events' => $events,
            'filters' => [
                'q' => $q,
                'status' => $status,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Events/Form', [
            'event' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $event = new Event($this->payload($data));
        $event->slug = Event::uniqueSlug($data['title']);
        $this->applyCover($request, $event);
        $event->save();

        return redirect()
            ->route('admin.events.index')
            ->with('success', 'Event ditambahkan.');
    }

    public function edit(Event $event): Response
    {
        return Inertia::render('Admin/Events/Form', [
            'event' => $event->toAdminArray(),
        ]);
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $data = $this->validated($request, $event);
        $event->fill($this->payload($data));

        if (! empty($data['slug'])) {
            $event->slug = Event::uniqueSlug($data['slug'], $event->id);
        }

        $this->applyCover($request, $event);
        $event->save();

        return redirect()
            ->route('admin.events.index')
            ->with('success', 'Event disimpan.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        $event->delete();

        return back()->with('success', 'Event dihapus.');
    }

    public function uploadImage(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'max:5120'],
        ]);

        $path = $request->file('image')->store('events/content', 'public');

        return response()->json([
            'url' => url('/storage/'.$path),
        ]);
    }

    private function validated(Request $request, ?Event $event = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:220', Rule::unique('events', 'slug')->ignore($event?->id)],
            'excerpt' => ['nullable', 'string', 'max:2000'],
            'body_html' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'venue' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'registration_url' => ['nullable', 'url', 'max:600'],
            'is_featured' => ['boolean'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'cover_image' => ['nullable', 'image', 'max:5120'],
            'remove_cover_image' => ['boolean'],
        ]);
    }

    private function payload(array $data): array
    {
        return [
            'title' => $data['title'],
            'excerpt' => $data['excerpt'] ?? null,
            'body_html' => $data['body_html'] ?? null,
            'location' => $data['location'] ?? null,
            'venue' => $data['venue'] ?? null,
            'city' => $data['city'] ?? null,
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'] ?? null,
            'registration_url' => $data['registration_url'] ?? null,
            'is_featured' => (bool) ($data['is_featured'] ?? false),
            'status' => $data['status'],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }

    private function applyCover(Request $request, Event $event): void
    {
        if ($request->boolean('remove_cover_image')) {
            $event->cover_image_url = null;
        }

        if ($request->hasFile('cover_image')) {
            $path = $request->file('cover_image')->store('events', 'public');
            $event->cover_image_url = '/storage/'.$path;
        }
    }
}
