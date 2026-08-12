<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Vehicle;
use App\Models\VehicleImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class VehicleController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));
        $brandId = (int) $request->query('brand_id', 0);

        $vehicles = Vehicle::query()
            ->with('brand')
            ->withCount('images')
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('name', 'like', $like)->orWhere('body_type', 'like', $like);
                });
            })
            ->when($brandId > 0, fn ($query) => $query->where('brand_id', $brandId))
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Vehicle $vehicle) => [
                ...$vehicle->toCardArray(),
                'status' => $vehicle->status,
                'sort_order' => $vehicle->sort_order,
            ]);

        return Inertia::render('Admin/Vehicles/Index', [
            'vehicles' => $vehicles,
            'brands' => $this->brandOptions(),
            'filters' => [
                'q' => $q,
                'brand_id' => $brandId > 0 ? $brandId : '',
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Vehicles/Form', [
            'vehicle' => null,
            'brands' => $this->brandOptions(),
            'bodyTypes' => $this->bodyTypes(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $vehicle = new Vehicle($this->payload($data));
        $vehicle->slug = Vehicle::uniqueSlug($data['name']);
        $this->applyCover($request, $vehicle);
        $vehicle->save();
        $this->syncImages($request, $vehicle);

        return redirect()
            ->route('admin.vehicles.edit', $vehicle)
            ->with('success', 'Kendaraan dibuat.');
    }

    public function edit(Vehicle $vehicle): Response
    {
        $vehicle->load(['brand', 'images']);

        return Inertia::render('Admin/Vehicles/Form', [
            'vehicle' => [
                'id' => $vehicle->id,
                'brand_id' => $vehicle->brand_id,
                'name' => $vehicle->name,
                'slug' => $vehicle->slug,
                'body_type' => $vehicle->body_type,
                'model_year' => $vehicle->model_year,
                'excerpt' => $vehicle->excerpt,
                'description_html' => $vehicle->description_html,
                'specs' => $vehicle->specs ?: [['label' => '', 'value' => '']],
                'cover_image_url' => $vehicle->toCardArray()['cover_image_url'],
                'price_from' => $vehicle->price_from,
                'status' => $vehicle->status,
                'published_at' => optional($vehicle->published_at)?->format('Y-m-d\TH:i'),
                'sort_order' => $vehicle->sort_order,
                'images' => $vehicle->images->map->toArrayPublic()->values()->all(),
            ],
            'brands' => $this->brandOptions(),
            'bodyTypes' => $this->bodyTypes(),
        ]);
    }

    public function update(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $data = $this->validated($request, $vehicle);
        $vehicle->fill($this->payload($data));
        if (! empty($data['slug'])) {
            $vehicle->slug = Vehicle::uniqueSlug($data['slug'], $vehicle->id);
        }
        $this->applyCover($request, $vehicle);
        $vehicle->save();
        $this->syncImages($request, $vehicle);

        return redirect()
            ->route('admin.vehicles.edit', $vehicle)
            ->with('success', 'Kendaraan disimpan.');
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        $vehicle->delete();

        return redirect()
            ->route('admin.vehicles.index')
            ->with('success', 'Kendaraan dihapus.');
    }

    public function uploadImage(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'max:5120'],
        ]);

        $path = $request->file('image')->store('vehicles/content', 'public');

        return response()->json([
            'url' => url('/storage/'.$path),
        ]);
    }

    private function validated(Request $request, ?Vehicle $vehicle = null): array
    {
        return $request->validate([
            'brand_id' => ['required', 'integer', 'exists:brands,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:220', Rule::unique('vehicles', 'slug')->ignore($vehicle?->id)],
            'body_type' => ['nullable', 'string', 'max:80'],
            'model_year' => ['nullable', 'string', 'max:20'],
            'excerpt' => ['nullable', 'string', 'max:2000'],
            'description_html' => ['nullable', 'string'],
            'specs' => ['nullable', 'array'],
            'specs.*.label' => ['nullable', 'string', 'max:120'],
            'specs.*.value' => ['nullable', 'string', 'max:255'],
            'price_from' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'published_at' => ['nullable', 'date'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'cover_image' => ['nullable', 'image', 'max:5120'],
            'remove_cover_image' => ['boolean'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'max:5120'],
            'remove_image_ids' => ['nullable', 'array'],
            'remove_image_ids.*' => ['integer'],
            'captions' => ['nullable', 'array'],
            'captions.*' => ['nullable', 'string', 'max:255'],
        ]);
    }

    private function payload(array $data): array
    {
        $specs = collect($data['specs'] ?? [])
            ->map(fn ($row) => [
                'label' => trim((string) ($row['label'] ?? '')),
                'value' => trim((string) ($row['value'] ?? '')),
            ])
            ->filter(fn ($row) => $row['label'] !== '' || $row['value'] !== '')
            ->values()
            ->all();

        return [
            'brand_id' => (int) $data['brand_id'],
            'name' => $data['name'],
            'body_type' => $data['body_type'] ?? null,
            'model_year' => $data['model_year'] ?? null,
            'excerpt' => $data['excerpt'] ?? null,
            'description_html' => $data['description_html'] ?? null,
            'specs' => $specs,
            'price_from' => $data['price_from'] ?? null,
            'status' => $data['status'],
            'published_at' => $data['published_at'] ?? ($data['status'] === 'published' ? now() : null),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }

    private function applyCover(Request $request, Vehicle $vehicle): void
    {
        if ($request->boolean('remove_cover_image')) {
            $vehicle->cover_image_url = null;
        }

        if ($request->hasFile('cover_image')) {
            $path = $request->file('cover_image')->store('vehicles', 'public');
            $vehicle->cover_image_url = '/storage/'.$path;
        }
    }

    private function syncImages(Request $request, Vehicle $vehicle): void
    {
        $removeIds = collect($request->input('remove_image_ids', []))->map(fn ($id) => (int) $id)->all();
        if ($removeIds !== []) {
            VehicleImage::query()
                ->where('vehicle_id', $vehicle->id)
                ->whereIn('id', $removeIds)
                ->delete();
        }

        $captions = $request->input('captions', []);
        if (is_array($captions)) {
            foreach ($captions as $imageId => $caption) {
                VehicleImage::query()
                    ->where('vehicle_id', $vehicle->id)
                    ->whereKey((int) $imageId)
                    ->update(['caption' => $caption ?: null]);
            }
        }

        if ($request->hasFile('images')) {
            $maxSort = (int) $vehicle->images()->max('sort_order');
            foreach ($request->file('images') as $file) {
                $maxSort++;
                $path = $file->store('vehicles/photos', 'public');
                $vehicle->images()->create([
                    'image_url' => '/storage/'.$path,
                    'sort_order' => $maxSort,
                ]);
            }
        }

        if (! $vehicle->cover_image_url) {
            $first = $vehicle->images()->orderBy('sort_order')->first();
            if ($first) {
                $vehicle->update(['cover_image_url' => $first->image_url]);
            }
        }
    }

    private function brandOptions(): array
    {
        return Brand::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'type'])
            ->map(fn (Brand $b) => [
                'id' => $b->id,
                'name' => $b->name.' ('.$b->typeLabel().')',
            ])
            ->values()
            ->all();
    }

    private function bodyTypes(): array
    {
        return [
            'SUV', 'MPV', 'Sedan', 'Hatchback', 'Crossover', 'Pickup', 'Wagon', 'Coupe', 'Convertible',
            'Sport', 'Naked', 'Scooter', 'Matic', 'Adventure', 'Cruiser', 'Touring', 'Trail', 'EV', 'Lainnya',
        ];
    }
}
