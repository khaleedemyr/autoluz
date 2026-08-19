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
        return Cache::remember('shop.rajaongkir.v3.provinces', 86400, function () {
            return $this->mapPlaces($this->pagedRows('/destination/province'));
        });
    }

    /**
     * @return list<array{id: string, name: string, postal_code: string|null}>
     */
    public function cities(string $provinceId): array
    {
        return Cache::remember('shop.rajaongkir.v3.cities.'.$provinceId, 86400, function () use ($provinceId) {
            return $this->mapPlaces(
                $this->pagedRows('/destination/city/'.$provinceId),
                withPostal: true,
                withType: true,
            );
        });
    }

    /**
     * @return list<array{id: string, name: string, postal_code: string|null}>
     */
    public function districts(string $cityId): array
    {
        return Cache::remember('shop.rajaongkir.v3.districts.'.$cityId, 86400, function () use ($cityId) {
            return $this->mapPlaces(
                $this->pagedRows('/destination/district/'.$cityId),
                withPostal: true,
            );
        });
    }

    /**
     * @param  list<string>|null  $couriers
     * @return list<array{courier: string, service: string, description: string, cost: int, etd: string, label: string}>
     */
    public function costs(string $destinationId, int $weightGrams, ?array $couriers = null, ?string $originId = null): array
    {
        if (! $this->configured()) {
            throw new RuntimeException('RajaOngkir belum dikonfigurasi.');
        }

        $settings = ShopSetting::current();
        $origin = $originId ?: $settings->origin_district_id ?: $settings->origin_city_id;
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
                    'destination' => $destinationId,
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
     * @return list<array<string, mixed>>
     */
    private function pagedRows(string $path): array
    {
        $all = [];
        $seen = [];
        $limit = 100;
        $offset = 0;

        for ($page = 0; $page < 40; $page++) {
            $payload = $this->get($path, [
                'limit' => $limit,
                'offset' => $offset,
                'page' => $page + 1,
            ]);
            $chunk = $this->rows($payload);
            if ($chunk === []) {
                break;
            }

            $added = 0;
            foreach ($chunk as $row) {
                $id = (string) ($row['id'] ?? $row['city_id'] ?? $row['province_id'] ?? $row['district_id'] ?? '');
                $key = $id !== '' ? $id : md5(json_encode($row));
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $all[] = $row;
                $added++;
            }

            $total = (int) (data_get($payload, 'meta.total') ?: data_get($payload, 'data.total') ?: 0);
            if ($added === 0 || count($chunk) < $limit || ($total > 0 && count($all) >= $total)) {
                break;
            }

            $offset += $limit;
        }

        return $all;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array{id: string, name: string, postal_code?: string|null}>
     */
    private function mapPlaces(array $rows, bool $withPostal = false, bool $withType = false): array
    {
        $mapped = collect($rows)
            ->map(function ($row) use ($withPostal, $withType) {
                $name = trim((string) ($row['name'] ?? $row['city_name'] ?? $row['province'] ?? $row['district'] ?? ''));
                if ($withType) {
                    $type = trim((string) ($row['type'] ?? ''));
                    if ($type !== '' && ! str_starts_with(mb_strtoupper($name), mb_strtoupper($type))) {
                        $name = trim($type.' '.$name);
                    }
                }

                $item = [
                    'id' => (string) ($row['id'] ?? $row['city_id'] ?? $row['province_id'] ?? $row['district_id'] ?? ''),
                    'name' => $name,
                ];
                if ($withPostal) {
                    $item['postal_code'] = $row['zip_code'] ?? $row['postal_code'] ?? null;
                }

                return $item;
            })
            ->filter(fn ($row) => $row['id'] !== '' && $row['name'] !== '')
            ->unique('id')
            ->sortBy(fn ($row) => mb_strtoupper($row['name']), SORT_NATURAL)
            ->values()
            ->all();

        return $mapped;
    }

    /**
     * @return array<string, mixed>
     */
    private function get(string $path, array $query = []): array
    {
        $response = Http::timeout(20)
            ->withHeaders(['key' => config('shop.rajaongkir.api_key')])
            ->acceptJson()
            ->get(config('shop.rajaongkir.base_url').$path, $query);

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

        if (is_array($data) && isset($data['data']) && is_array($data['data'])) {
            $data = $data['data'];
        }

        if (! is_array($data)) {
            return [];
        }

        $values = array_values($data);
        if ($values === [] || ! is_array($values[0] ?? null)) {
            return [];
        }

        return $values;
    }
}
