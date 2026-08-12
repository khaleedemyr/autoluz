<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VehicleSeeder extends Seeder
{
    public function run(): void
    {
        Storage::disk('public')->makeDirectory('vehicles');

        $catalog = require database_path('data/vehicle_lineup.php');
        $sort = 10;

        foreach ($catalog as $brandSlug => $models) {
            $brand = Brand::query()->where('slug', $brandSlug)->first();
            if (! $brand) {
                $this->command?->warn("Brand skipped (missing): {$brandSlug}");
                continue;
            }

            foreach ($models as $index => $model) {
                $slug = $model['slug'] ?? Str::slug($model['name']);
                $excerpt = $model['excerpt'] ?? ($model['name'].' — line-up '.$brand->name.'.');
                $year = (string) ($model['model_year'] ?? '2025');
                $bodyType = $model['body_type'] ?? 'Lainnya';
                $specs = $model['specs'] ?? null;
                if (! $specs) {
                    $real = $this->realSpecs()[$slug] ?? null;
                    $specs = $real
                        ? $this->buildSpecs($real, $bodyType)
                        : $this->buildSpecs($model, $bodyType);
                }
                $commons = $model['commons'] ?? null;

                $cover = $this->resolveCover($slug, $commons, $brand->name.' '.$model['name']);

                $payload = [
                    'brand_id' => $brand->id,
                    'name' => $model['name'],
                    'body_type' => $bodyType,
                    'model_year' => $year,
                    'excerpt' => $excerpt,
                    'description_html' => '<p>'.$excerpt.'</p><p>Spesifikasi merujuk pada tipe representatif dan dapat berbeda per varian. Selalu cek data resmi merek.</p>',
                    'specs' => $specs,
                    'price_from' => $model['price_from'] ?? null,
                    'price_currency' => 'IDR',
                    'status' => 'published',
                    'published_at' => now(),
                    'sort_order' => $sort + $index,
                ];

                if ($cover) {
                    $payload['cover_image_url'] = $cover;
                }

                $vehicle = Vehicle::query()->updateOrCreate(
                    ['slug' => $slug],
                    $payload
                );

                if ($cover && $vehicle->images()->count() === 0) {
                    $vehicle->images()->create([
                        'image_url' => $cover,
                        'caption' => $vehicle->name,
                        'sort_order' => 1,
                    ]);
                } elseif ($cover && $vehicle->wasChanged('cover_image_url')) {
                    // keep gallery; ensure cover exists as image if empty gallery already handled
                }

                $sort += 10;
            }
        }

        $this->command?->info('Vehicle lineup seeded for '.count($catalog).' brands.');
    }

    private function realSpecs(): array
    {
        static $cache = null;
        if ($cache === null) {
            $path = database_path('data/vehicle_specs_real.php');
            $cache = file_exists($path) ? require $path : [];
        }

        return $cache;
    }

    private function buildSpecs(array $model, string $bodyType): array
    {
        $rows = [];
        $map = [
            'engine' => 'Mesin',
            'power' => 'Tenaga maks.',
            'torque' => 'Torsi maks.',
            'trans' => 'Transmisi',
            'drive' => 'Penggerak',
            'fuel' => 'BBM',
            'cap' => 'Kapasitas',
            'dim' => 'Dimensi (P×L×T)',
            'wb' => 'Wheelbase',
            'gc' => 'Ground clearance',
            'tank' => 'Tanki / baterai',
            'tire' => 'Ban',
            'feature' => 'Fitur',
            'range' => 'Jarak tempuh',
            'battery' => 'Baterai',
            '0_100' => '0–100 km/jam',
        ];

        foreach ($map as $key => $label) {
            if (! empty($model[$key])) {
                $rows[] = ['label' => $label, 'value' => (string) $model[$key]];
            }
        }

        return $rows !== [] ? $rows : $this->fallbackSpecs($bodyType, (int) ($model['price_from'] ?? 0));
    }

    private function fallbackSpecs(string $bodyType, int $price): array
    {
        $moto = in_array($bodyType, ['Scooter', 'Sport', 'Naked', 'Adventure', 'Cruiser', 'Touring', 'Trail', 'Classic', 'Matic'], true);

        if ($bodyType === 'EV' && ! $moto) {
            return [
                ['label' => 'Penggerak', 'value' => 'Motor listrik'],
                ['label' => 'BBM', 'value' => 'Listrik'],
                ['label' => 'Transmisi', 'value' => 'Single-speed'],
                ['label' => 'Baterai', 'value' => $price >= 800000000 ? '70–100 kWh (tipe)' : '40–65 kWh (tipe)'],
                ['label' => 'Jarak tempuh', 'value' => $price >= 800000000 ? '450–600 km (klaim)' : '300–450 km (klaim)'],
                ['label' => 'Kapasitas', 'value' => '5 penumpang'],
                ['label' => 'Fitur', 'value' => 'ADAS / fast charging (tipe)'],
            ];
        }

        if ($moto) {
            if ($bodyType === 'Scooter' || $bodyType === 'Matic') {
                $cc = $price >= 50000000 ? '150–250 cc' : ($price >= 25000000 ? '125–155 cc' : '110–125 cc');

                return [
                    ['label' => 'Mesin', 'value' => $cc.' SOHC'],
                    ['label' => 'Tenaga maks.', 'value' => $price >= 50000000 ? '14–22 PS' : ($price >= 25000000 ? '11–15 PS' : '8–10 PS')],
                    ['label' => 'Transmisi', 'value' => 'Otomatis (CVT)'],
                    ['label' => 'BBM', 'value' => 'Bensin'],
                    ['label' => 'Rem', 'value' => $price >= 25000000 ? 'ABS / CBS' : 'CBS'],
                    ['label' => 'Fitur', 'value' => 'Paneli digital / Smart Key (tipe)'],
                ];
            }

            return [
                ['label' => 'Mesin', 'value' => $price >= 200000000 ? '650–1100 cc' : ($price >= 80000000 ? '250–400 cc' : '150–200 cc')],
                ['label' => 'Tenaga maks.', 'value' => $price >= 200000000 ? '70–150 PS' : ($price >= 80000000 ? '30–45 PS' : '15–25 PS')],
                ['label' => 'Transmisi', 'value' => 'Manual 6-speed'],
                ['label' => 'BBM', 'value' => 'Bensin'],
                ['label' => 'Rem', 'value' => 'ABS'],
                ['label' => 'Fitur', 'value' => 'Riding mode / Traction control (tipe)'],
            ];
        }

        // Cars by price band
        if ($price >= 2000000000) {
            return [
                ['label' => 'Mesin', 'value' => '3.0–4.0L turbo / V8 (tipe)'],
                ['label' => 'Tenaga maks.', 'value' => '340–600 PS'],
                ['label' => 'Transmisi', 'value' => '8–9 AT'],
                ['label' => 'Penggerak', 'value' => 'AWD'],
                ['label' => 'BBM', 'value' => 'Bensin'],
                ['label' => 'Kapasitas', 'value' => '4–5 penumpang'],
                ['label' => 'Fitur', 'value' => 'ADAS penuh / interior luxury'],
            ];
        }

        if ($price >= 800000000) {
            return [
                ['label' => 'Mesin', 'value' => '2.0L turbo / hybrid'],
                ['label' => 'Tenaga maks.', 'value' => '180–300 PS'],
                ['label' => 'Transmisi', 'value' => '7–8 AT / DCT'],
                ['label' => 'Penggerak', 'value' => 'RWD / AWD'],
                ['label' => 'BBM', 'value' => 'Bensin / Hybrid'],
                ['label' => 'Kapasitas', 'value' => '5 penumpang'],
                ['label' => 'Fitur', 'value' => 'ADAS / panoramic roof (tipe)'],
            ];
        }

        if (in_array($bodyType, ['MPV', 'SUV', 'Pickup', 'Crossover'], true)) {
            return [
                ['label' => 'Mesin', 'value' => $price >= 400000000 ? '2.0–2.4L / diesel' : '1.5L'],
                ['label' => 'Tenaga maks.', 'value' => $price >= 400000000 ? '150–180 PS' : '100–120 PS'],
                ['label' => 'Torsi maks.', 'value' => $price >= 400000000 ? '250–450 Nm' : '130–150 Nm'],
                ['label' => 'Transmisi', 'value' => 'MT / AT / CVT'],
                ['label' => 'Penggerak', 'value' => $bodyType === 'Pickup' || $price >= 500000000 ? '4x2 / 4x4' : 'FWD'],
                ['label' => 'BBM', 'value' => $price >= 450000000 ? 'Diesel / Bensin' : 'Bensin'],
                ['label' => 'Kapasitas', 'value' => $bodyType === 'MPV' || $bodyType === 'SUV' ? '7 penumpang' : '5 penumpang'],
                ['label' => 'Fitur', 'value' => 'Kamera / sensor parkir / safety pack (tipe)'],
            ];
        }

        return [
            ['label' => 'Mesin', 'value' => '1.2–1.5L'],
            ['label' => 'Tenaga maks.', 'value' => '90–120 PS'],
            ['label' => 'Transmisi', 'value' => 'MT / CVT'],
            ['label' => 'Penggerak', 'value' => 'FWD'],
            ['label' => 'BBM', 'value' => 'Bensin'],
            ['label' => 'Kapasitas', 'value' => '5 penumpang'],
            ['label' => 'Fitur', 'value' => 'Safety sensing / infotainment (tipe)'],
        ];
    }

    private function resolveCover(string $slug, ?string $commonsTitle, string $searchHint): ?string
    {
        $disk = Storage::disk('public');
        foreach (['vehicles/'.$slug.'.jpg', 'vehicles/'.$slug.'-g1.jpg'] as $relative) {
            if ($disk->exists($relative) && $disk->size($relative) > 5000) {
                return '/storage/'.$relative;
            }
        }

        $title = $commonsTitle ?: $this->searchCommonsTitle($searchHint);
        if (! $title) {
            return null;
        }

        $relative = 'vehicles/'.$slug.'.jpg';
        $url = $this->commonsThumbUrl($title);
        if (! $url) {
            return null;
        }

        try {
            usleep(350000);
            $response = Http::timeout(25)
                ->withHeaders(['User-Agent' => 'AutoluzSeeder/1.0 (local development)'])
                ->get($url);

            if ($response->successful() && strlen($response->body()) > 5000) {
                $disk->put($relative, $response->body());

                return '/storage/'.$relative;
            }
        } catch (\Throwable) {
            // keep null
        }

        return $disk->exists($relative) ? '/storage/'.$relative : null;
    }

    private function searchCommonsTitle(string $query): ?string
    {
        try {
            usleep(250000);
            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => 'AutoluzSeeder/1.0 (local development)'])
                ->get('https://commons.wikimedia.org/w/api.php', [
                    'action' => 'query',
                    'list' => 'search',
                    'srsearch' => $query,
                    'srnamespace' => 6,
                    'srlimit' => 5,
                    'format' => 'json',
                ]);

            if (! $response->successful()) {
                return null;
            }

            $results = $response->json('query.search') ?? [];
            foreach ($results as $row) {
                $title = $row['title'] ?? '';
                if ($title === '' || ! str_starts_with($title, 'File:')) {
                    continue;
                }
                // Skip obvious mismatches like logos/icons/svg
                if (preg_match('/\.(svg|png)\s*$/i', $title)) {
                    continue;
                }
                if (preg_match('/logo|icon|badge|emblem/i', $title)) {
                    continue;
                }

                return $title;
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    private function commonsThumbUrl(string $title): ?string
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => 'AutoluzSeeder/1.0 (local development)'])
                ->get('https://commons.wikimedia.org/w/api.php', [
                    'action' => 'query',
                    'titles' => $title,
                    'prop' => 'imageinfo',
                    'iiprop' => 'url',
                    'iiurlwidth' => 1280,
                    'format' => 'json',
                ]);

            if (! $response->successful()) {
                return null;
            }

            foreach ($response->json('query.pages') ?? [] as $page) {
                if (! empty($page['missing'])) {
                    continue;
                }
                $info = $page['imageinfo'][0] ?? null;
                if (! empty($info['thumburl'])) {
                    return $info['thumburl'];
                }
                if (! empty($info['url'])) {
                    return $info['url'];
                }
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }
}
