<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VehicleCompareController extends Controller
{
    private const MAX = 3;

    public function index(Request $request): Response
    {
        $ids = collect(explode(',', (string) $request->query('ids', '')))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->take(self::MAX)
            ->values();

        $vehicles = Vehicle::query()
            ->published()
            ->with('brand')
            ->whereIn('id', $ids->all())
            ->get()
            ->sortBy(fn (Vehicle $vehicle) => $ids->search($vehicle->id))
            ->values()
            ->map(fn (Vehicle $vehicle) => $vehicle->toDetailArray())
            ->all();

        $labels = collect($vehicles)
            ->flatMap(fn (array $vehicle) => collect($vehicle['specs'] ?? [])->pluck('label'))
            ->filter()
            ->unique()
            ->values()
            ->all();

        // Prefer a stable row order for common fields.
        $preferred = [
            'Mesin', 'Tenaga maks.', 'Torsi maks.', 'Transmisi', 'Penggerak', 'BBM',
            'Baterai', 'Jarak tempuh', 'Kapasitas', 'Dimensi (P×L×T)', 'Wheelbase',
            'Ground clearance', 'Tanki / baterai', 'Ban', '0–100 km/jam', 'Fitur',
        ];
        $ordered = collect($preferred)
            ->filter(fn ($label) => in_array($label, $labels, true))
            ->merge(collect($labels)->reject(fn ($label) => in_array($label, $preferred, true)))
            ->values()
            ->all();

        return Inertia::render('Vehicles/Compare', [
            'vehicles' => $vehicles,
            'spec_labels' => $ordered,
            'ids' => $ids->all(),
            'max' => self::MAX,
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $exclude = collect(explode(',', (string) $request->query('exclude', '')))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->all();

        $vehicles = Vehicle::query()
            ->published()
            ->with('brand')
            ->when($exclude !== [], fn ($query) => $query->whereNotIn('id', $exclude))
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('name', 'like', $like)
                        ->orWhere('body_type', 'like', $like)
                        ->orWhereHas('brand', fn ($brand) => $brand->where('name', 'like', $like));
                });
            })
            ->orderBy('name')
            ->limit(12)
            ->get()
            ->map(fn (Vehicle $vehicle) => [
                'id' => $vehicle->id,
                'name' => $vehicle->name,
                'body_type' => $vehicle->body_type,
                'model_year' => $vehicle->model_year,
                'price_label' => $vehicle->priceLabel(),
                'cover_image_url' => $vehicle->toCardArray()['cover_image_url'],
                'brand' => [
                    'name' => $vehicle->brand?->name,
                    'slug' => $vehicle->brand?->slug,
                ],
                'url' => $vehicle->brand
                    ? route('brands.vehicles.show', [$vehicle->brand->slug, $vehicle->slug])
                    : null,
            ])
            ->values();

        return response()->json(['data' => $vehicles]);
    }
}
