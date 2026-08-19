<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShopCategory;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RabbitAndWheelsSeeder extends Seeder
{
    /**
     * Demo partner store inspired by public Rabbit & Wheels catalog (Bandung riding apparel).
     * Prices/names follow commonly listed models. Product photos are original generated catalog shots, not official brand photography.
     */
    public function run(): void
    {
        Storage::disk('public')->makeDirectory('stores');
        Storage::disk('public')->makeDirectory('products/rabbit-and-wheels');

        $owner = User::query()->updateOrCreate(
            ['email' => 'seller.rabbit@autoluz.local'],
            [
                'name' => 'Rabbit & Wheels',
                'username' => User::query()->where('email', 'seller.rabbit@autoluz.local')->exists()
                    ? User::query()->where('email', 'seller.rabbit@autoluz.local')->value('username')
                    : User::uniqueUsername('rabbitwheels'),
                'password' => Hash::make('password'),
                'is_admin' => false,
                'email_verified_at' => now(),
                'bio' => 'Official partner store — riding apparel Bandung. Ride with Style.',
            ]
        );

        $official = Store::official();
        $logoPath = $this->publishAsset('rabbit-and-wheels-logo.jpg', 'stores/rabbit-and-wheels.jpg')
            ?: $this->writeSvg('stores/rabbit-and-wheels.svg', $this->logoSvg());

        $store = Store::query()->updateOrCreate(
            ['slug' => 'rabbit-and-wheels'],
            [
                'user_id' => $owner->id,
                'name' => 'Rabbit & Wheels',
                'tagline' => 'Ride with Style',
                'description' => 'Apparel riding asal Bandung. Jaket motorsport, city ride, hoodie, dan gear pendukung — safety tetap stylish. Outlet: Ruko Paskal Hyper Square Blok F 12, Jl. Pasir Kaliki No. 25–27, Bandung.',
                'logo_path' => $logoPath,
                'contact_phone' => '022-2056-0000',
                'pickup_address' => 'Ruko Paskal Hyper Square Blok F 12, Jl. Pasir Kaliki No. 25-27, Ciroyom, Andir, Kota Bandung, Jawa Barat 40171',
                'origin_province_id' => $official?->origin_province_id,
                'origin_province_name' => $official?->origin_province_name ?: 'Jawa Barat',
                'origin_city_id' => $official?->origin_city_id,
                'origin_city_name' => $official?->origin_city_name ?: 'Kota Bandung',
                'couriers' => ['jne', 'jnt', 'sicepat', 'pos'],
                'status' => Store::STATUS_APPROVED,
                'is_official' => false,
            ]
        );

        $categories = $this->categories();

        foreach ($this->catalog() as $index => $item) {
            $cover = $this->publishAsset($item['slug'].'.jpg', 'products/rabbit-and-wheels/'.$item['slug'].'.jpg')
                ?: $this->writeSvg(
                    'products/rabbit-and-wheels/'.$item['slug'].'.svg',
                    $this->productSvg($item['name'], $item['accent'])
                );

            $product = Product::query()->updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'store_id' => $store->id,
                    'shop_category_id' => $categories[$item['category']]->id,
                    'name' => $item['name'],
                    'excerpt' => $item['excerpt'],
                    'description_html' => $item['html'],
                    'cover_image_url' => $cover,
                    'weight_grams' => $item['weight'],
                    'featured' => (bool) ($item['featured'] ?? false),
                    'status' => 'published',
                    'published_at' => now()->subDays(20 - $index),
                    'sort_order' => ($index + 1) * 10,
                ]
            );

            $sort = 0;
            $keepSkus = [];
            foreach ($item['variants'] as $variant) {
                $sku = $variant['sku'];
                $keepSkus[] = $sku;
                ProductVariant::query()->updateOrCreate(
                    ['sku' => $sku],
                    [
                        'product_id' => $product->id,
                        'size' => $variant['size'] ?? null,
                        'color' => $variant['color'] ?? null,
                        'price' => $variant['price'],
                        'stock' => $variant['stock'] ?? 8,
                        'is_active' => true,
                        'sort_order' => $sort++,
                    ]
                );
            }

            $product->variants()->whereNotIn('sku', $keepSkus)->delete();

            if ($cover && str_ends_with($cover, '.jpg')) {
                $product->images()->delete();
                $product->images()->create([
                    'image_url' => $cover,
                    'caption' => $item['name'],
                    'sort_order' => 1,
                ]);
            }
        }

        $this->command?->info('Rabbit & Wheels seeded. Login seller: seller.rabbit@autoluz.local / password');
        if (! $store->originReady()) {
            $this->command?->warn('Kota asal masih kosong. Isi origin Bandung di /seller/settings supaya ongkir jalan.');
        }
    }

    /**
     * @return array<string, ShopCategory>
     */
    private function categories(): array
    {
        $rows = [
            'jaket' => ['Jaket Riding', 'Jaket motorsport, mesh, dan city ride.'],
            'hoodie' => ['Hoodie', 'Hoodie riding dan casual zipper.'],
            'kaos' => ['Kaos & Jersey', 'T-shirt dan jersey motorsport.'],
            'tas' => ['Tas', 'Backpack dan waist bag bikers.'],
            'aksesoris' => ['Aksesoris', 'Topi, shiftpad, dan pelengkap riding.'],
        ];

        $map = [];
        $sort = 10;
        foreach ($rows as $key => [$name, $description]) {
            $map[$key] = ShopCategory::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'description' => $description,
                    'sort_order' => $sort,
                    'is_active' => true,
                ]
            );
            $sort += 10;
        }

        return $map;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function catalog(): array
    {
        $sizes = ['S', 'M', 'L', 'XL', 'XXL'];

        return [
            [
                'slug' => 'rnw-legendary-midnight-2025',
                'name' => 'Legendary Midnight 2025',
                'category' => 'jaket',
                'excerpt' => 'Edisi terbatas bernuansa cafe racer. Mesh tropis, protektor terintegrasi, karakter diam yang kuat.',
                'html' => '<p>Jaket eksklusif yang mengangkat estetika cafe racer era 60-an: sederhana, fungsional, berkarakter. Bahan mesh berpori nyaman di iklim tropis, dengan protektor di dada, punggung, bahu, dan siku.</p><p>Produksi terbatas. Cocok untuk yang ingin tampil berkelas tanpa berisik.</p>',
                'weight' => 1400,
                'featured' => true,
                'accent' => '#111111',
                'variants' => $this->sized('RNW-MIDNIGHT', 'Black', 2200000, $sizes, 4),
            ],
            [
                'slug' => 'rnw-msp-motul-2024',
                'name' => 'MSP Motul 2024',
                'category' => 'jaket',
                'excerpt' => 'Jaket mesh touring full protektor, kolaborasi palet Motul yang paling dikenal bikers RNW.',
                'html' => '<p>Motorsport jacket mesh untuk touring harian. Sirkulasi udara tinggi, panel gesek, dan protektor bahu/siku/punggung. Potongan racing yang tetap dipakai di kota.</p>',
                'weight' => 1350,
                'featured' => true,
                'accent' => '#c81e1e',
                'variants' => array_merge(
                    $this->sized('RNW-MOTUL-BLK', 'Black', 2370000, $sizes, 6),
                    $this->sized('RNW-MOTUL-RED', 'Red Grey', 2370000, $sizes, 5),
                ),
            ],
            [
                'slug' => 'rnw-msp-falcon-2024',
                'name' => 'MSP Falcon 2024',
                'category' => 'jaket',
                'excerpt' => 'Jaket riding mesh 2024 dengan siluet agresif dan protektor lengkap.',
                'html' => '<p>Falcon adalah seri MSP untuk pengendara yang ingin tampilan motorsport tegas. Mesh body, aksen kontras, dan protektor terintegrasi.</p>',
                'weight' => 1300,
                'featured' => false,
                'accent' => '#1d4ed8',
                'variants' => $this->sized('RNW-FALCON', 'Black Blue', 2075000, $sizes, 7),
            ],
            [
                'slug' => 'rnw-combo-legacy',
                'name' => 'Combo Legacy',
                'category' => 'jaket',
                'excerpt' => 'Jaket combo motorsport — layer mesh plus panel proteksi, palet merah khas RNW.',
                'html' => '<p>Seri Combo mewarisi DNA motorsport Rabbit &amp; Wheels: aman dipakai touring, tetap rapi saat turun dari motor.</p>',
                'weight' => 1450,
                'featured' => true,
                'accent' => '#b91c1c',
                'variants' => array_merge(
                    $this->sized('RNW-COMBO-RED', 'Legacy Red', 2040000, $sizes, 6),
                    $this->sized('RNW-COMBO-TOSCA', 'Legacy Tosca', 2040000, $sizes, 4),
                ),
            ],
            [
                'slug' => 'rnw-polo-mesh-msp',
                'name' => 'Polo Mesh MSP',
                'category' => 'jaket',
                'excerpt' => 'Jaket pendek mesh bergaya polo — ringan, protektor include, pas untuk city ride panas.',
                'html' => '<p>Potongan pendek lebih breathable di macet tropis. Tetap membawa protektor bahu dan siku. Favorit city ride Bandung–Jakarta.</p>',
                'weight' => 980,
                'featured' => false,
                'accent' => '#0f766e',
                'variants' => array_merge(
                    $this->sized('RNW-POLO-BLU', 'Blue', 1350000, $sizes, 8),
                    $this->sized('RNW-POLO-RED', 'Red', 1490000, $sizes, 7),
                ),
            ],
            [
                'slug' => 'rnw-patron-tex',
                'name' => 'Patron Tex',
                'category' => 'jaket',
                'excerpt' => 'Jaket city ride berbahan Patron Tex — lebih ringan, zipper tegas, masuk entry motorsport.',
                'html' => '<p>Opsi city ride yang lebih terjangkau tanpa kehilangan identitas RNW. Cocok harian, bukan trek.</p>',
                'weight' => 900,
                'featured' => false,
                'accent' => '#7f1d1d',
                'variants' => array_merge(
                    $this->sized('RNW-PATRON-RED', 'Red', 1080000, $sizes, 10),
                    $this->sized('RNW-PATRON-BLK', 'Black', 1080000, $sizes, 10),
                    $this->sized('RNW-PATRON-NVY', 'Navy', 1080000, $sizes, 8),
                ),
            ],
            [
                'slug' => 'rnw-jarvis',
                'name' => 'Jarvis Jacket',
                'category' => 'jaket',
                'excerpt' => 'Jaket motorsport entry dengan harga lebih rapat, tetap berkarakter RNW.',
                'html' => '<p>Jarvis adalah pintu masuk ke lini jaket Rabbit &amp; Wheels. Ringan, tegas, siap touring dekat.</p>',
                'weight' => 950,
                'featured' => false,
                'accent' => '#292524',
                'variants' => $this->sized('RNW-JARVIS', 'Black', 970000, $sizes, 9),
            ],
            [
                'slug' => 'rnw-nasional-motorsport',
                'name' => 'Nasional Motorsport Jacket',
                'category' => 'jaket',
                'excerpt' => 'Seri nasional — jaket motorsport yang sempat jadi wajah RNW di lintasan dan jalan raya.',
                'html' => '<p>Seri Nasional merujuk pada jaket motorsport yang membawa nama Indonesia. Proteksi lengkap, palet merah-putih yang tidak berteriak.</p>',
                'weight' => 1320,
                'featured' => true,
                'accent' => '#9f1239',
                'variants' => $this->sized('RNW-NASIONAL', 'Merah Putih', 1700000, $sizes, 5),
            ],
            [
                'slug' => 'rnw-city-ride',
                'name' => 'City Ride Jacket',
                'category' => 'jaket',
                'excerpt' => 'Jaket city ride harian: lebih ramping, tetap ada proteksi bahu dan siku.',
                'html' => '<p>Dirancang untuk commute. Tidak seberat seri MSP, tetap memberi lapisan gesek dan protektor dasar.</p>',
                'weight' => 880,
                'featured' => false,
                'accent' => '#44403c',
                'variants' => $this->sized('RNW-CITY', 'Black', 850000, $sizes, 12),
            ],
            [
                'slug' => 'rnw-tritone-hoodie',
                'name' => 'Tritone Hoodie Zipper Motorsport',
                'category' => 'hoodie',
                'excerpt' => 'Hoodie zipper motorsport tiga tone — layer touring yang tetap bisa ke kafe.',
                'html' => '<p>Hoodie zipper full mesh di area strategis. Dipakai sebagai layer atau outer ringan. Siluet motorsport, nyaman di tropis.</p>',
                'weight' => 750,
                'featured' => true,
                'accent' => '#57534e',
                'variants' => $this->sized('RNW-TRITONE', 'Black Grey', 1484000, $sizes, 6),
            ],
            [
                'slug' => 'rnw-hoodie-signature',
                'name' => 'Hoodie Motorsport Signature',
                'category' => 'hoodie',
                'excerpt' => 'Hoodie signature RNW — ikon lifestyle bikers yang tetap siap angin jalan.',
                'html' => '<p>Signature hoodie adalah wajah casual Rabbit &amp; Wheels. Grafis motorsport, zipper penuh, kantung hangat.</p>',
                'weight' => 720,
                'featured' => false,
                'accent' => '#0a0a0a',
                'variants' => $this->sized('RNW-HOOD-SIG', 'Black', 1250000, $sizes, 8),
            ],
            [
                'slug' => 'rnw-hoodie-vontega',
                'name' => 'Casual Hoodie Vontega',
                'category' => 'hoodie',
                'excerpt' => 'Hoodie casual zipper mesh — lebih santai dari seri motorsport, tetap RNW.',
                'html' => '<p>Vontega untuk hangout dan riding pelan. Mesh di punggung, aksen logo kelinci &amp; roda.</p>',
                'weight' => 700,
                'featured' => false,
                'accent' => '#1c1917',
                'variants' => $this->sized('RNW-VONTEGA', 'Black', 1100000, $sizes, 7),
            ],
            [
                'slug' => 'rnw-tee-ride-with-style',
                'name' => 'T-Shirt Ride with Style',
                'category' => 'kaos',
                'excerpt' => 'Kaos katun grafis RNW. Dipakai di bawah jaket atau sendiri.',
                'html' => '<p>Motto merek sejak awal: Ride with Style. Sablon plastisol, potongan regular.</p>',
                'weight' => 220,
                'featured' => false,
                'accent' => '#e11d48',
                'variants' => array_merge(
                    $this->sized('RNW-TEE-BLK', 'Black', 185000, $sizes, 20),
                    $this->sized('RNW-TEE-WHT', 'White', 185000, $sizes, 16),
                    $this->sized('RNW-TEE-RED', 'Red', 185000, $sizes, 12),
                ),
            ],
            [
                'slug' => 'rnw-jersey-motorsport',
                'name' => 'Jersey Motorsport',
                'category' => 'kaos',
                'excerpt' => 'Jersey dry-fit untuk riding panjang di panas. Grafis racing RNW.',
                'html' => '<p>Bahan dry-fit cepat kering. Dipakai solo atau base layer di bawah mesh jacket.</p>',
                'weight' => 280,
                'featured' => false,
                'accent' => '#dc2626',
                'variants' => $this->sized('RNW-JERSEY', 'Red Black', 450000, $sizes, 10),
            ],
            [
                'slug' => 'rnw-lumens-backpack',
                'name' => 'Lumens Backpack',
                'category' => 'tas',
                'excerpt' => 'Ransel bikers RNW — kompartemen helm-friendly, strip reflektif.',
                'html' => '<p>Backpack harian yang kuat digoyang touring dekat. Panel belakang gepeng, saku laptop, aksen reflektif malam.</p>',
                'weight' => 680,
                'featured' => false,
                'accent' => '#171717',
                'variants' => [[
                    'sku' => 'RNW-LUMENS-BLK',
                    'size' => null,
                    'color' => 'Black',
                    'price' => 385000,
                    'stock' => 15,
                ]],
            ],
            [
                'slug' => 'rnw-waist-bag-raws-v2',
                'name' => 'Waist Bag Raws V2',
                'category' => 'tas',
                'excerpt' => 'Tas pinggang V2 — HP, STNK, kunci, tanpa bongkar jaket.',
                'html' => '<p>Raws V2 lebih ramping dari generasi pertama. Gesper cepat, tahan gerimis ringan.</p>',
                'weight' => 280,
                'featured' => false,
                'accent' => '#3f3f46',
                'variants' => [[
                    'sku' => 'RNW-RAWS-V2',
                    'size' => null,
                    'color' => 'Black',
                    'price' => 235000,
                    'stock' => 18,
                ]],
            ],
            [
                'slug' => 'rnw-cap',
                'name' => 'Topi Rabbit & Wheels',
                'category' => 'aksesoris',
                'excerpt' => 'Snapback bordir logo kelinci & roda.',
                'html' => '<p>Pelengkap outfit pit atau ngopi setelah riding. Bordir 3D, visor datar.</p>',
                'weight' => 140,
                'featured' => false,
                'accent' => '#0f0f0f',
                'variants' => [
                    ['sku' => 'RNW-CAP-BLK', 'size' => null, 'color' => 'Black', 'price' => 125000, 'stock' => 24],
                    ['sku' => 'RNW-CAP-RED', 'size' => null, 'color' => 'Red', 'price' => 125000, 'stock' => 16],
                ],
            ],
            [
                'slug' => 'rnw-shiftpad',
                'name' => 'Shiftpad',
                'category' => 'aksesoris',
                'excerpt' => 'Pelindung sepatu saat gigi motor. Kecil, wajib di tas jok.',
                'html' => '<p>Shiftpad melindungi ujung sepatu dari gesekan shifter. Karet + strap, pas di sepatu harian maupun riding boots.</p>',
                'weight' => 80,
                'featured' => false,
                'accent' => '#525252',
                'variants' => [[
                    'sku' => 'RNW-SHIFTPAD',
                    'size' => null,
                    'color' => 'Black',
                    'price' => 55000,
                    'stock' => 40,
                ]],
            ],
            [
                'slug' => 'rnw-keychain',
                'name' => 'Gantungan Kunci RNW',
                'category' => 'aksesoris',
                'excerpt' => 'Keychain logam logo Rabbit & Wheels.',
                'html' => '<p>Merchandise kecil untuk kunci motor atau tas. Enamel logo, ring baja.</p>',
                'weight' => 40,
                'featured' => false,
                'accent' => '#b45309',
                'variants' => [[
                    'sku' => 'RNW-KEY',
                    'size' => null,
                    'color' => 'Metal',
                    'price' => 55000,
                    'stock' => 50,
                ]],
            ],
        ];
    }

    /**
     * @param  list<string>  $sizes
     * @return list<array{sku: string, size: string, color: string, price: int, stock: int}>
     */
    private function sized(string $skuPrefix, string $color, int $price, array $sizes, int $stock): array
    {
        $rows = [];
        foreach ($sizes as $size) {
            $rows[] = [
                'sku' => $skuPrefix.'-'.$size,
                'size' => $size,
                'color' => $color,
                'price' => $price,
                'stock' => $stock,
            ];
        }

        return $rows;
    }

    private function publishAsset(string $filename, string $storagePath): ?string
    {
        $source = database_path('seeders/assets/rabbit-and-wheels/'.$filename);
        if (! is_file($source)) {
            return null;
        }

        Storage::disk('public')->put($storagePath, file_get_contents($source));

        return '/storage/'.$storagePath;
    }

    private function writeSvg(string $path, string $svg): string
    {
        Storage::disk('public')->put($path, $svg);

        return '/storage/'.$path;
    }

    private function logoSvg(): string
    {
        return <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 240 240">
  <rect width="240" height="240" fill="#111"/>
  <circle cx="120" cy="132" r="46" fill="none" stroke="#e11d48" stroke-width="10"/>
  <circle cx="120" cy="132" r="14" fill="#e11d48"/>
  <path d="M88 78c8-28 22-44 32-44 6 0 8 8 8 16 0 14-6 28-12 40" fill="none" stroke="#fafafa" stroke-width="10" stroke-linecap="round"/>
  <path d="M152 78c-8-28-22-44-32-44-6 0-8 8-8 16 0 14 6 28 12 40" fill="none" stroke="#fafafa" stroke-width="10" stroke-linecap="round"/>
  <text x="120" y="210" text-anchor="middle" fill="#fafafa" font-family="Georgia, serif" font-size="18">R &amp; W</text>
</svg>
SVG;
    }

    private function productSvg(string $name, string $accent): string
    {
        $safe = htmlspecialchars($name, ENT_XML1);
        $accent = htmlspecialchars($accent, ENT_XML1);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 1000">
  <rect width="800" height="1000" fill="#0a0a0a"/>
  <rect x="0" y="0" width="800" height="18" fill="{$accent}"/>
  <text x="60" y="120" fill="#e11d48" font-family="Georgia, serif" font-size="28">RABBIT &amp; WHEELS</text>
  <text x="60" y="520" fill="#fafafa" font-family="Georgia, serif" font-size="48">{$safe}</text>
  <text x="60" y="920" fill="#737373" font-family="sans-serif" font-size="22">RIDE WITH STYLE</text>
</svg>
SVG;
    }
}
