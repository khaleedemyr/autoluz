<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Vehicle;
use Inertia\Inertia;
use Inertia\Response;

class VehicleController extends Controller
{
    public function show(string $brandSlug, string $vehicleSlug): Response
    {
        $brand = Brand::query()
            ->active()
            ->where('slug', $brandSlug)
            ->firstOrFail();

        $vehicle = Vehicle::query()
            ->published()
            ->with(['brand', 'images'])
            ->where('brand_id', $brand->id)
            ->where('slug', $vehicleSlug)
            ->firstOrFail();

        $related = Vehicle::query()
            ->published()
            ->with('brand')
            ->where('brand_id', $brand->id)
            ->where('id', '!=', $vehicle->id)
            ->orderBy('sort_order')
            ->limit(4)
            ->get()
            ->map->toCardArray()
            ->values();

        return Inertia::render('Vehicles/Show', [
            'brand' => $brand->toCardArray(),
            'vehicle' => $vehicle->toDetailArray(),
            'related' => $related,
            'creditDefaults' => [
                'dp_percent' => (float) config('credit.default_dp_percent', 20),
                'tenor' => (int) config('credit.default_tenor', 36),
                'rate' => (float) config('credit.default_rate', 5.5),
                'method' => (string) config('credit.default_method', 'flat'),
                'tenor_options' => array_values(config('credit.tenor_options', [12, 24, 36, 48, 60])),
            ],
        ]);
    }
}
