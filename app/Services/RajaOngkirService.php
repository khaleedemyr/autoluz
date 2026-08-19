<?php

namespace App\Services;

use App\Models\ShopSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class RajaOngkirService
{
    public function configured(): bool
    {
        return filled(config('shop.rajaongkir.api_key'));
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    public function provinces(): array
    {
        return Cache::remember('shop.rajaongkir.provinces', 86400, function () {
            $payload = $this->get('/destination/province');

            return collect($this->rows($payload))
                ->map(fn ($row) => [
                    'id' => (string) ($row['id'] ?? $row['province_id'] ?? ''),
                    'name' => (string) ($row['name'] ?? $row['province'] ?? ''),
                ])
                ->filter(fn ($row) => $row['id'] !== '' && $row['name'] !== '')
                ->values()
                ->all();
        });
    }

    /**
     * @return list<array{id: string, name: string, postal_code: string|null}>
     */
    public function cities(string $provinceId): array
    {
        return Cache::remember('shop.rajaongkir.cities.'.$provinceId, 86400, function () use ($provinceId) {
            $payload = $this->get('/destination/city/'.$provinceId);

            return collect($this->rows($payload))
                ->map(fn ($row) => [
                    'id' => (string) ($row['id'] ?? $row['city_id'] ?? ''),
                    'name' => trim(((string) ($row['type'] ?? '')).' '.((string) ($row['name'] ?? $row['city_name'] ?? ''))),
                    'postal_code' => $row['zip_code'] ?? $row['postal_code'] ?? null,
                ])
                ->filter(fn ($row) => $row['id'] !== '' && trim($row['name']) !== '')
                ->values()
                ->all();
        });
    }

    /**
     * @param  list<string>|null  $couriers
     * @return list<array{courier: string, service: string, description: string, cost: int, etd: string, label: string}>
     */
    public function costs(string $destinationCityId, int $weightGrams, ?array $couriers = null, ?string $originCityId = null): array
    {
        if (! $this->configured()) {
            throw new RuntimeException('RajaOngkir belum dikonfigurasi.');
        }

        $settings = ShopSetting::current();
        $origin = $originCityId ?: $settings->origin_city_id;
        if (! $origin) {
            throw new RuntimeException('Kota asal pengiriman belum diatur.');
        }

        $weight = max(1, $weightGrams);
        $couriers = $couriers ?: $settings->courierList();
        $results = [];

        foreach ($couriers as $courier) {
            try {
                $payload = $this->post('/calculate/domestic-cost', [
                    'origin' => $origin,
                    'destination' => $destinationCityId,
                    'weight' => $weight,
                    'courier' => strtolower((string) $courier),
                    'price' => 'lowest',
                ]);
            } catch (RuntimeException) {
                continue;
            }

            foreach ($this->rows($payload) as $row) {
                $cost = (int) ($row['cost'] ?? $row['value'] ?? 0);
                $service = (string) ($row['service'] ?? '');
                $description = (string) ($row['description'] ?? $row['name'] ?? $service);
                $etd = (string) ($row['etd'] ?? $row['estimated'] ?? '');
                $code = strtolower((string) ($row['code'] ?? $courier));

                if ($cost <= 0 || $service === '') {
                    continue;
                }

                $results[] = [
                    'courier' => $code,
                    'service' => $service,
                    'description' => $description,
                    'cost' => $cost,
                    'etd' => $etd,
                    'label' => strtoupper($code).' '.$service.' — Rp '.number_format($cost, 0, ',', '.').($etd !== '' ? ' ('.$etd.' hari)' : ''),
                ];
            }
        }

        usort($results, fn ($a, $b) => $a['cost'] <=> $b['cost']);

        return $results;
    }

    /**
     * @return array<string, mixed>
     */
    private function get(string $path): array
    {
        $response = Http::timeout(20)
            ->withHeaders(['key' => config('shop.rajaongkir.api_key')])
            ->acceptJson()
            ->get(config('shop.rajaongkir.base_url').$path);

        if (! $response->successful()) {
            throw new RuntimeException('Gagal memuat data RajaOngkir.');
        }

        return $response->json() ?? [];
    }

    /**
     * @param  array<string, mixed>  $form
     * @return array<string, mixed>
     */
    private function post(string $path, array $form): array
    {
        $response = Http::timeout(25)
            ->asForm()
            ->withHeaders(['key' => config('shop.rajaongkir.api_key')])
            ->acceptJson()
            ->post(config('shop.rajaongkir.base_url').$path, $form);

        if (! $response->successful()) {
            throw new RuntimeException('Gagal menghitung ongkir.');
        }

        return $response->json() ?? [];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array<string, mixed>>
     */
    private function rows(array $payload): array
    {
        $data = $payload['data'] ?? $payload['rajaongkir']['results'] ?? $payload['results'] ?? [];

        return is_array($data) ? array_values($data) : [];
    }
}
