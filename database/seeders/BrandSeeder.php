<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        Storage::disk('public')->makeDirectory('brands');

        $brands = [
            // Cars — volume / mainstream
            ['name' => 'Toyota', 'slug' => 'toyota', 'type' => 'car', 'icon' => 'toyota', 'sort_order' => 10],
            ['name' => 'Honda', 'slug' => 'honda', 'type' => 'both', 'icon' => 'honda', 'sort_order' => 20],
            ['name' => 'Mitsubishi', 'slug' => 'mitsubishi', 'type' => 'car', 'icon' => 'mitsubishi', 'sort_order' => 30],
            ['name' => 'Suzuki', 'slug' => 'suzuki', 'type' => 'both', 'icon' => 'suzuki', 'sort_order' => 40],
            ['name' => 'Daihatsu', 'slug' => 'daihatsu', 'type' => 'car', 'icon' => 'daihatsu', 'sort_order' => 50],
            ['name' => 'Nissan', 'slug' => 'nissan', 'type' => 'car', 'icon' => 'nissan', 'sort_order' => 60],
            ['name' => 'Mazda', 'slug' => 'mazda', 'type' => 'car', 'icon' => 'mazda', 'sort_order' => 70],
            ['name' => 'Hyundai', 'slug' => 'hyundai', 'type' => 'car', 'icon' => 'hyundai', 'sort_order' => 80],
            ['name' => 'Kia', 'slug' => 'kia', 'type' => 'car', 'icon' => 'kia', 'sort_order' => 90],
            ['name' => 'Isuzu', 'slug' => 'isuzu', 'type' => 'car', 'icon' => 'isuzu', 'sort_order' => 100],
            ['name' => 'Subaru', 'slug' => 'subaru', 'type' => 'car', 'icon' => 'subaru', 'sort_order' => 110],

            // Cars — premium / luxury
            ['name' => 'BMW', 'slug' => 'bmw', 'type' => 'both', 'icon' => 'bmw', 'sort_order' => 120],
            ['name' => 'Mercedes-Benz', 'slug' => 'mercedes-benz', 'type' => 'car', 'icon' => 'mercedes', 'sort_order' => 130],
            ['name' => 'Audi', 'slug' => 'audi', 'type' => 'car', 'icon' => 'audi', 'sort_order' => 140],
            ['name' => 'Lexus', 'slug' => 'lexus', 'type' => 'car', 'icon' => 'lexus', 'sort_order' => 150],
            ['name' => 'Porsche', 'slug' => 'porsche', 'type' => 'car', 'icon' => 'porsche', 'sort_order' => 160],
            ['name' => 'Land Rover', 'slug' => 'land-rover', 'type' => 'car', 'icon' => 'landrover', 'sort_order' => 170],
            ['name' => 'Jaguar', 'slug' => 'jaguar', 'type' => 'car', 'icon' => 'jaguar', 'sort_order' => 180],
            ['name' => 'Volvo', 'slug' => 'volvo', 'type' => 'car', 'icon' => 'volvo', 'sort_order' => 190],
            ['name' => 'MINI', 'slug' => 'mini', 'type' => 'car', 'icon' => 'mini', 'sort_order' => 200],
            ['name' => 'Bentley', 'slug' => 'bentley', 'type' => 'car', 'icon' => 'bentley', 'sort_order' => 210],
            ['name' => 'Rolls-Royce', 'slug' => 'rolls-royce', 'type' => 'car', 'icon' => 'rollsroyce', 'sort_order' => 220],
            ['name' => 'Ferrari', 'slug' => 'ferrari', 'type' => 'car', 'icon' => 'ferrari', 'sort_order' => 230],
            ['name' => 'Lamborghini', 'slug' => 'lamborghini', 'type' => 'car', 'icon' => 'lamborghini', 'sort_order' => 240],
            ['name' => 'Maserati', 'slug' => 'maserati', 'type' => 'car', 'icon' => 'maserati', 'sort_order' => 250],
            ['name' => 'McLaren', 'slug' => 'mclaren', 'type' => 'car', 'icon' => 'mclaren', 'sort_order' => 260],
            ['name' => 'Aston Martin', 'slug' => 'aston-martin', 'type' => 'car', 'icon' => 'astonmartin', 'sort_order' => 270],
            ['name' => 'Genesis', 'slug' => 'genesis', 'type' => 'car', 'icon' => 'genesis', 'sort_order' => 280],
            ['name' => 'Infiniti', 'slug' => 'infiniti', 'type' => 'car', 'icon' => 'infiniti', 'sort_order' => 290],
            ['name' => 'Cadillac', 'slug' => 'cadillac', 'type' => 'car', 'icon' => 'cadillac', 'sort_order' => 300],
            ['name' => 'Alfa Romeo', 'slug' => 'alfa-romeo', 'type' => 'car', 'icon' => 'alfaromeo', 'sort_order' => 310],

            // Cars — Europe / US volume
            ['name' => 'Volkswagen', 'slug' => 'volkswagen', 'type' => 'car', 'icon' => 'volkswagen', 'sort_order' => 320],
            ['name' => 'Peugeot', 'slug' => 'peugeot', 'type' => 'car', 'icon' => 'peugeot', 'sort_order' => 330],
            ['name' => 'Renault', 'slug' => 'renault', 'type' => 'car', 'icon' => 'renault', 'sort_order' => 340],
            ['name' => 'Citroën', 'slug' => 'citroen', 'type' => 'car', 'icon' => 'citroen', 'sort_order' => 350],
            ['name' => 'Opel', 'slug' => 'opel', 'type' => 'car', 'icon' => 'opel', 'sort_order' => 360],
            ['name' => 'Fiat', 'slug' => 'fiat', 'type' => 'car', 'icon' => 'fiat', 'sort_order' => 370],
            ['name' => 'Ford', 'slug' => 'ford', 'type' => 'car', 'icon' => 'ford', 'sort_order' => 380],
            ['name' => 'Chevrolet', 'slug' => 'chevrolet', 'type' => 'car', 'icon' => 'chevrolet', 'sort_order' => 390],
            ['name' => 'Jeep', 'slug' => 'jeep', 'type' => 'car', 'icon' => 'jeep', 'sort_order' => 400],
            ['name' => 'Dodge', 'slug' => 'dodge', 'type' => 'car', 'icon' => 'dodge', 'sort_order' => 410],
            ['name' => 'GMC', 'slug' => 'gmc', 'type' => 'car', 'icon' => 'gmc', 'sort_order' => 420],
            ['name' => 'Ram', 'slug' => 'ram', 'type' => 'car', 'icon' => 'ram', 'sort_order' => 430],
            ['name' => 'Skoda', 'slug' => 'skoda', 'type' => 'car', 'icon' => 'skoda', 'sort_order' => 440],
            ['name' => 'SEAT', 'slug' => 'seat', 'type' => 'car', 'icon' => 'seat', 'sort_order' => 450],
            ['name' => 'Cupra', 'slug' => 'cupra', 'type' => 'car', 'icon' => 'cupra', 'sort_order' => 460],

            // Cars — China / EV / ASEAN
            ['name' => 'BYD', 'slug' => 'byd', 'type' => 'car', 'icon' => 'byd', 'sort_order' => 470],
            ['name' => 'Wuling', 'slug' => 'wuling', 'type' => 'car', 'icon' => 'wuling', 'sort_order' => 480],
            ['name' => 'Chery', 'slug' => 'chery', 'type' => 'car', 'icon' => 'chery', 'sort_order' => 490],
            ['name' => 'GWM', 'slug' => 'gwm', 'type' => 'car', 'icon' => 'greatwall', 'sort_order' => 500],
            ['name' => 'MG', 'slug' => 'mg', 'type' => 'car', 'icon' => 'mg', 'sort_order' => 510],
            ['name' => 'Tesla', 'slug' => 'tesla', 'type' => 'car', 'icon' => 'tesla', 'sort_order' => 520],
            ['name' => 'Neta', 'slug' => 'neta', 'type' => 'car', 'icon' => null, 'sort_order' => 530],
            ['name' => 'XPeng', 'slug' => 'xpeng', 'type' => 'car', 'icon' => 'xpeng', 'sort_order' => 540],
            ['name' => 'NIO', 'slug' => 'nio', 'type' => 'car', 'icon' => 'nio', 'sort_order' => 550],
            ['name' => 'Zeekr', 'slug' => 'zeekr', 'type' => 'car', 'icon' => null, 'sort_order' => 560],
            ['name' => 'Geely', 'slug' => 'geely', 'type' => 'car', 'icon' => 'geely', 'sort_order' => 570],
            ['name' => 'Haval', 'slug' => 'haval', 'type' => 'car', 'icon' => null, 'sort_order' => 580],
            ['name' => 'Tank', 'slug' => 'tank', 'type' => 'car', 'icon' => null, 'sort_order' => 590],
            ['name' => 'Ora', 'slug' => 'ora', 'type' => 'car', 'icon' => null, 'sort_order' => 600],
            ['name' => 'DFSK', 'slug' => 'dfsk', 'type' => 'car', 'icon' => null, 'sort_order' => 610],
            ['name' => 'Seres', 'slug' => 'seres', 'type' => 'car', 'icon' => null, 'sort_order' => 620],
            ['name' => 'Maxus', 'slug' => 'maxus', 'type' => 'car', 'icon' => null, 'sort_order' => 630],
            ['name' => 'Jaecoo', 'slug' => 'jaecoo', 'type' => 'car', 'icon' => null, 'sort_order' => 640],
            ['name' => 'Omoda', 'slug' => 'omoda', 'type' => 'car', 'icon' => null, 'sort_order' => 650],
            ['name' => 'VinFast', 'slug' => 'vinfast', 'type' => 'car', 'icon' => 'vinfast', 'sort_order' => 660],
            ['name' => 'Aion', 'slug' => 'aion', 'type' => 'car', 'icon' => null, 'sort_order' => 670],
            ['name' => 'Deepal', 'slug' => 'deepal', 'type' => 'car', 'icon' => null, 'sort_order' => 680],
            ['name' => 'Smart', 'slug' => 'smart', 'type' => 'car', 'icon' => 'smart', 'sort_order' => 700],
            ['name' => 'Polestar', 'slug' => 'polestar', 'type' => 'car', 'icon' => 'polestar', 'sort_order' => 710],
            ['name' => 'Rivian', 'slug' => 'rivian', 'type' => 'car', 'icon' => 'rivian', 'sort_order' => 720],
            ['name' => 'Lucid', 'slug' => 'lucid', 'type' => 'car', 'icon' => 'lucid', 'sort_order' => 730],

            // Motorcycles — major
            ['name' => 'Yamaha', 'slug' => 'yamaha', 'type' => 'moto', 'icon' => 'yamaha', 'sort_order' => 810],
            ['name' => 'Kawasaki', 'slug' => 'kawasaki', 'type' => 'moto', 'icon' => 'kawasaki', 'sort_order' => 820],
            ['name' => 'Ducati', 'slug' => 'ducati', 'type' => 'moto', 'icon' => 'ducati', 'sort_order' => 830],
            ['name' => 'Harley-Davidson', 'slug' => 'harley-davidson', 'type' => 'moto', 'icon' => 'harley-davidson', 'sort_order' => 840],
            ['name' => 'KTM', 'slug' => 'ktm', 'type' => 'moto', 'icon' => 'ktm', 'sort_order' => 850],
            ['name' => 'Triumph', 'slug' => 'triumph', 'type' => 'moto', 'icon' => 'triumph', 'sort_order' => 860],
            ['name' => 'Royal Enfield', 'slug' => 'royal-enfield', 'type' => 'moto', 'icon' => 'royalenfield', 'sort_order' => 870],
            ['name' => 'Vespa', 'slug' => 'vespa', 'type' => 'moto', 'icon' => 'vespa', 'sort_order' => 880],
            ['name' => 'Piaggio', 'slug' => 'piaggio', 'type' => 'moto', 'icon' => 'piaggio', 'sort_order' => 890],
            ['name' => 'Aprilia', 'slug' => 'aprilia', 'type' => 'moto', 'icon' => 'aprilia', 'sort_order' => 900],
            ['name' => 'Benelli', 'slug' => 'benelli', 'type' => 'moto', 'icon' => 'benelli', 'sort_order' => 910],
            ['name' => 'TVS', 'slug' => 'tvs', 'type' => 'moto', 'icon' => 'tvs', 'sort_order' => 920],
            ['name' => 'Bajaj', 'slug' => 'bajaj', 'type' => 'moto', 'icon' => 'bajaj', 'sort_order' => 930],
            ['name' => 'Hero', 'slug' => 'hero', 'type' => 'moto', 'icon' => null, 'sort_order' => 940],
            ['name' => 'Husqvarna', 'slug' => 'husqvarna', 'type' => 'moto', 'icon' => 'husqvarna', 'sort_order' => 950],
            ['name' => 'MV Agusta', 'slug' => 'mv-agusta', 'type' => 'moto', 'icon' => null, 'sort_order' => 960],
            ['name' => 'Indian Motorcycle', 'slug' => 'indian', 'type' => 'moto', 'icon' => null, 'sort_order' => 970],
            ['name' => 'CFMoto', 'slug' => 'cfmoto', 'type' => 'moto', 'icon' => null, 'sort_order' => 980],
            ['name' => 'Keeway', 'slug' => 'keeway', 'type' => 'moto', 'icon' => null, 'sort_order' => 990],
            ['name' => 'Sym', 'slug' => 'sym', 'type' => 'moto', 'icon' => null, 'sort_order' => 1000],
            ['name' => 'Kymco', 'slug' => 'kymco', 'type' => 'moto', 'icon' => null, 'sort_order' => 1010],
            ['name' => 'Viar', 'slug' => 'viar', 'type' => 'moto', 'icon' => null, 'sort_order' => 1020],
            ['name' => 'Gesits', 'slug' => 'gesits', 'type' => 'moto', 'icon' => null, 'sort_order' => 1030],
            ['name' => 'Alva', 'slug' => 'alva', 'type' => 'moto', 'icon' => null, 'sort_order' => 1040],
            ['name' => 'Polytron', 'slug' => 'polytron', 'type' => 'moto', 'icon' => null, 'sort_order' => 1050],
            ['name' => 'Selis', 'slug' => 'selis', 'type' => 'moto', 'icon' => null, 'sort_order' => 1060],
            ['name' => 'Smoot', 'slug' => 'smoot', 'type' => 'moto', 'icon' => null, 'sort_order' => 1070],
            ['name' => 'Uwinfly', 'slug' => 'uwinfly', 'type' => 'moto', 'icon' => null, 'sort_order' => 1080],
            ['name' => 'Volta', 'slug' => 'volta', 'type' => 'moto', 'icon' => null, 'sort_order' => 1090],
            ['name' => 'Electrum', 'slug' => 'electrum', 'type' => 'moto', 'icon' => null, 'sort_order' => 1100],
            ['name' => 'SMK', 'slug' => 'smk', 'type' => 'moto', 'icon' => null, 'sort_order' => 1110],
            ['name' => 'QJMotor', 'slug' => 'qjmotor', 'type' => 'moto', 'icon' => null, 'sort_order' => 1120],
            ['name' => 'Zontes', 'slug' => 'zontes', 'type' => 'moto', 'icon' => null, 'sort_order' => 1130],
            ['name' => 'GPX', 'slug' => 'gpx', 'type' => 'moto', 'icon' => null, 'sort_order' => 1140],
            ['name' => 'Italjet', 'slug' => 'italjet', 'type' => 'moto', 'icon' => null, 'sort_order' => 1150],
            ['name' => 'Lambretta', 'slug' => 'lambretta', 'type' => 'moto', 'icon' => null, 'sort_order' => 1160],
            ['name' => 'Zero Motorcycles', 'slug' => 'zero', 'type' => 'moto', 'icon' => null, 'sort_order' => 1170],
            ['name' => 'Energica', 'slug' => 'energica', 'type' => 'moto', 'icon' => null, 'sort_order' => 1180],
        ];

        foreach ($brands as $brand) {
            $logoUrl = $this->resolveLogo($brand['slug'], $brand['icon'] ?? null);

            $model = Brand::query()->updateOrCreate(
                ['slug' => $brand['slug']],
                [
                    'name' => $brand['name'],
                    'type' => $brand['type'],
                    'sort_order' => $brand['sort_order'],
                    'is_active' => true,
                    'logo_url' => $logoUrl,
                    'description' => 'Update terbaru seputar '.$brand['name'].' di Indonesia.',
                ]
            );

            $articleIds = Article::query()
                ->published()
                ->where(function ($q) use ($brand) {
                    $q->where('title', 'like', '%'.$brand['name'].'%')
                        ->orWhere('slug', 'like', '%'.$brand['slug'].'%');
                })
                ->orderByDesc('published_at')
                ->limit(12)
                ->pluck('id')
                ->all();

            if ($articleIds !== []) {
                $model->articles()->syncWithoutDetaching($articleIds);
            }
        }
    }

    private function resolveLogo(string $slug, ?string $icon): ?string
    {
        $relative = 'brands/'.$slug.'.svg';
        $disk = Storage::disk('public');

        if ($disk->exists($relative)) {
            return '/storage/'.$relative;
        }

        if (! $icon) {
            return $this->writeFallbackLogo($disk, $relative, $slug);
        }

        try {
            $response = Http::timeout(8)
                ->accept('image/svg+xml')
                ->get('https://cdn.simpleicons.org/'.$icon.'/111111');

            if ($response->successful()) {
                $disk->put($relative, $response->body());

                return '/storage/'.$relative;
            }
        } catch (\Throwable) {
            // fall through
        }

        return $this->writeFallbackLogo($disk, $relative, $slug);
    }

    private function writeFallbackLogo($disk, string $relative, string $slug): string
    {
        $letter = strtoupper(substr($slug, 0, 1));
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="128" height="128" viewBox="0 0 128 128">
  <rect width="128" height="128" rx="24" fill="#F3F4F6"/>
  <text x="64" y="76" text-anchor="middle" font-family="Arial, sans-serif" font-size="52" font-weight="700" fill="#111111">{$letter}</text>
</svg>
SVG;
        $disk->put($relative, $svg);

        return '/storage/'.$relative;
    }
}
