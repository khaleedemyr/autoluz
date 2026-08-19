<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShopSetting;
use App\Models\Store;
use App\Services\RajaOngkirService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class ShopSettingController extends Controller
{
    public function edit(RajaOngkirService $rajaongkir): Response
    {
        $settings = ShopSetting::current();
        $provinces = [];
        $cities = [];
        $error = null;

        try {
            if ($rajaongkir->configured()) {
                $provinces = $rajaongkir->provinces();
                if ($settings->origin_province_id) {
                    $cities = $rajaongkir->cities($settings->origin_province_id);
                }
            } else {
                $error = 'RAJAONGKIR_API_KEY belum diisi di .env.';
            }
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        }

        $stores = Store::query()
            ->with('owner:id,name,email')
            ->orderByDesc('is_official')
            ->orderBy('name')
            ->get()
            ->map(fn (Store $store) => [
                'id' => $store->id,
                'name' => $store->name,
                'slug' => $store->slug,
                'is_official' => $store->is_official,
                'origin_city_name' => $store->origin_city_name,
                'origin_ready' => $store->originReady(),
                'couriers' => $store->courierList(),
                'status_label' => $store->statusLabel(),
                'owner' => $store->owner ? [
                    'name' => $store->owner->name,
                    'email' => $store->owner->email,
                ] : null,
            ]);

        return Inertia::render('Admin/ShopSettings/Edit', [
            'settings' => $settings->toAdminArray(),
            'stores' => $stores,
            'provinces' => $provinces,
            'cities' => $cities,
            'courierOptions' => Store::courierOptions(),
            'rajaongkir_error' => $error,
            'rajaongkir_configured' => $rajaongkir->configured(),
            'midtrans_configured' => filled(config('shop.midtrans.server_key')),
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
        $data = $request->validate([
            'store_name' => ['required', 'string', 'max:120'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'pickup_address' => ['nullable', 'string', 'max:500'],
            'origin_province_id' => ['nullable', 'string', 'max:20'],
            'origin_province_name' => ['nullable', 'string', 'max:120'],
            'origin_city_id' => ['nullable', 'string', 'max:20'],
            'origin_city_name' => ['nullable', 'string', 'max:120'],
            'couriers' => ['required', 'array', 'min:1'],
            'couriers.*' => ['string', 'max:20'],
        ]);

        ShopSetting::current()->update($data);

        $official = \App\Models\Store::official();
        if ($official) {
            $official->update([
                'name' => $data['store_name'],
                'contact_phone' => $data['contact_phone'] ?? null,
                'pickup_address' => $data['pickup_address'] ?? null,
                'origin_province_id' => $data['origin_province_id'] ?? null,
                'origin_province_name' => $data['origin_province_name'] ?? null,
                'origin_city_id' => $data['origin_city_id'] ?? null,
                'origin_city_name' => $data['origin_city_name'] ?? null,
                'couriers' => $data['couriers'],
            ]);
        }

        return back()->with('success', 'Pengaturan toko disimpan.');
    }
}
