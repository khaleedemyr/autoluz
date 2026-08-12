<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CreditSimulationController extends Controller
{
    public function index(Request $request): Response
    {
        $vehicleId = (int) $request->query('vehicle', 0);
        $vehicle = null;

        if ($vehicleId > 0) {
            $found = Vehicle::query()
                ->published()
                ->with('brand')
                ->whereKey($vehicleId)
                ->first();

            if ($found) {
                $vehicle = [
                    'id' => $found->id,
                    'name' => $found->name,
                    'body_type' => $found->body_type,
                    'model_year' => $found->model_year,
                    'price_from' => $found->price_from,
                    'price_label' => $found->priceLabel(),
                    'cover_image_url' => $found->toCardArray()['cover_image_url'],
                    'brand' => [
                        'name' => $found->brand?->name,
                        'slug' => $found->brand?->slug,
                    ],
                    'url' => $found->brand
                        ? route('brands.vehicles.show', [$found->brand->slug, $found->slug])
                        : null,
                ];
            }
        }

        return Inertia::render('Credit/Simulate', [
            'vehicle' => $vehicle,
            'defaults' => [
                'dp_percent' => (float) config('credit.default_dp_percent', 20),
                'tenor' => (int) config('credit.default_tenor', 36),
                'rate' => (float) config('credit.default_rate', 5.5),
                'method' => (string) config('credit.default_method', 'flat'),
                'tenor_options' => array_values(config('credit.tenor_options', [12, 24, 36, 48, 60])),
            ],
        ]);
    }
}
