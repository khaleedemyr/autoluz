<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\RajaOngkirService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class StoreSettingController extends Controller
{
    public function edit(Request $request, RajaOngkirService $rajaongkir): Response
    {
        $store = $request->user()->ownedStore()->firstOrFail();
        $provinces = [];
        $cities = [];
        $error = null;

        try {
            if ($rajaongkir->configured()) {
                $provinces = $rajaongkir->provinces();
                if ($store->origin_province_id) {
                    $cities = $rajaongkir->cities($store->origin_province_id);
                }
            } else {
                $error = 'RAJAONGKIR_API_KEY belum diisi. Hubungi admin Autoluz.';
            }
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        }

        return Inertia::render('Seller/Settings', [
            'store' => $store->toSettingsArray(),
            'provinces' => $provinces,
            'cities' => $cities,
            'courierOptions' => Store::courierOptions(),
            'rajaongkir_error' => $error,
            'rajaongkir_configured' => $rajaongkir->configured(),
        ]);
    }

    public function cities(Request $request, RajaOngkirService $rajaongkir): \Illuminate\Http\JsonResponse
    {
        $provinceId = trim((string) $request->query('province_id', ''));
        if ($provinceId === '') {
            return response()->json(['data' => []]);
        }

        try {
            return response()->json(['data' => $rajaongkir->cities($provinceId)]);
        } catch (RuntimeException $e) {
            return response()->json(['data' => [], 'error' => $e->getMessage()], 422);
        }
    }

    public function update(Request $request): RedirectResponse
    {
        $store = $request->user()->ownedStore()->firstOrFail();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'tagline' => ['nullable', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:4000'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'pickup_address' => ['nullable', 'string', 'max:500'],
            'origin_province_id' => ['nullable', 'string', 'max:20'],
            'origin_province_name' => ['nullable', 'string', 'max:120'],
            'origin_city_id' => ['nullable', 'string', 'max:20'],
            'origin_city_name' => ['nullable', 'string', 'max:120'],
            'couriers' => ['required', 'array', 'min:1'],
            'couriers.*' => ['string', 'max:20'],
            'logo' => ['nullable', 'image', 'max:5120'],
            'remove_logo' => ['boolean'],
        ]);

        $store->fill([
            'name' => $data['name'],
            'tagline' => $data['tagline'] ?? null,
            'description' => $data['description'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'pickup_address' => $data['pickup_address'] ?? null,
            'origin_province_id' => $data['origin_province_id'] ?? null,
            'origin_province_name' => $data['origin_province_name'] ?? null,
            'origin_city_id' => $data['origin_city_id'] ?? null,
            'origin_city_name' => $data['origin_city_name'] ?? null,
            'couriers' => array_values($data['couriers']),
        ]);

        if ($request->boolean('remove_logo')) {
            $store->logo_path = null;
        }

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('stores', 'public');
            $store->logo_path = '/storage/'.$path;
        }

        $store->save();

        return back()->with('success', 'Pengaturan toko disimpan.');
    }
}
