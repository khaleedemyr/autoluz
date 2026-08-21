<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Article;
use App\Models\Event;
use App\Models\Gallery;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function events(): JsonResponse
    {
        $upcoming = Event::query()->published()->upcoming()->limit(20)->get()->map->toCardArray()->values();
        $past = Event::query()->published()->past()->limit(8)->get()->map->toCardArray()->values();

        return response()->json([
            'upcoming' => $upcoming,
            'past' => $past,
            'hero' => $upcoming->first(),
        ]);
    }

    public function event(string $slug): JsonResponse
    {
        $event = Event::query()->published()->where('slug', $slug)->firstOrFail();
        $related = Event::query()
            ->published()
            ->where('id', '!=', $event->id)
            ->orderBy('starts_at')
            ->limit(3)
            ->get()
            ->map->toCardArray()
            ->values();

        return response()->json([
            'event' => $event->toDetailArray(),
            'related' => $related,
        ]);
    }

    public function shareEvent(Request $request, string $slug): JsonResponse
    {
        $request->validate([
            'channel' => ['nullable', 'string', 'max:40'],
        ]);

        $event = Event::query()->published()->where('slug', $slug)->firstOrFail();
        $event->increment('shares_count');

        return response()->json([
            'shares_count' => (int) $event->fresh()->shares_count,
        ]);
    }

    public function brands(): JsonResponse
    {
        $map = fn (Brand $brand) => $brand->toCardArray();

        return response()->json([
            'cars' => Brand::query()->active()->cars()->withCount([
                'articles' => fn ($q) => $q->published(),
                'vehicles' => fn ($q) => $q->published(),
            ])->orderBy('sort_order')->orderBy('name')->get()->map($map)->values(),
            'motos' => Brand::query()->active()->motos()->withCount([
                'articles' => fn ($q) => $q->published(),
                'vehicles' => fn ($q) => $q->published(),
            ])->orderBy('sort_order')->orderBy('name')->get()->map($map)->values(),
        ]);
    }

    public function brand(Request $request, string $slug): JsonResponse
    {
        $brand = Brand::query()
            ->active()
            ->withCount([
                'articles as articles_count' => fn ($q) => $q->published(),
                'vehicles as vehicles_count' => fn ($q) => $q->published(),
            ])
            ->where('slug', $slug)
            ->firstOrFail();

        $vehicles = Vehicle::query()
            ->with('brand')
            ->withCount('images')
            ->published()
            ->where('brand_id', $brand->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map->toCardArray()
            ->values();

        $articles = Article::query()
            ->with(['category', 'brands'])
            ->withCardStats()
            ->published()
            ->whereHas('brands', fn ($q) => $q->where('brands.id', $brand->id))
            ->orderByDesc('published_at')
            ->paginate(9)
            ->through(fn (Article $article) => $article->toCardArray());

        return response()->json([
            'brand' => $brand->toCardArray(),
            'vehicles' => $vehicles,
            'articles' => $articles,
        ]);
    }

    public function vehicle(string $brandSlug, string $vehicleSlug): JsonResponse
    {
        $brand = Brand::query()->active()->where('slug', $brandSlug)->firstOrFail();
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

        return response()->json([
            'brand' => $brand->toCardArray(),
            'vehicle' => $vehicle->toDetailArray(),
            'related' => $related,
            'credit_defaults' => $this->creditDefaults(),
        ]);
    }

    public function compare(Request $request): JsonResponse
    {
        $ids = collect(explode(',', (string) $request->query('ids', '')))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->take(3)
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

        return response()->json([
            'vehicles' => $vehicles,
            'spec_labels' => $labels,
            'ids' => $ids->all(),
            'max' => 3,
        ]);
    }

    public function compareSearch(Request $request): JsonResponse
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
                        ->orWhereHas('brand', fn ($brand) => $brand->where('name', 'like', $like));
                });
            })
            ->limit(12)
            ->get()
            ->map->toCardArray()
            ->values();

        return response()->json(['data' => $vehicles]);
    }

    public function galleries(): JsonResponse
    {
        $galleries = Gallery::query()
            ->published()
            ->with(['images' => fn ($q) => $q->limit(1)])
            ->withCount('images')
            ->orderByDesc('published_at')
            ->paginate(12)
            ->through(fn (Gallery $gallery) => $gallery->toCardArray());

        return response()->json($galleries);
    }

    public function gallery(string $slug): JsonResponse
    {
        $gallery = Gallery::query()->published()->with('images')->where('slug', $slug)->firstOrFail();
        $related = Gallery::query()
            ->published()
            ->withCount('images')
            ->where('id', '!=', $gallery->id)
            ->orderByDesc('published_at')
            ->limit(4)
            ->get()
            ->map->toCardArray()
            ->values();

        return response()->json([
            'gallery' => $gallery->toDetailArray(),
            'related' => $related,
        ]);
    }

    public function credit(Request $request): JsonResponse
    {
        $vehicleId = (int) $request->query('vehicle', 0);
        $vehicle = null;
        if ($vehicleId > 0) {
            $found = Vehicle::query()->published()->with('brand')->whereKey($vehicleId)->first();
            if ($found) {
                $vehicle = $found->toCardArray();
            }
        }

        $price = (int) $request->query('price', $vehicle['price_from'] ?? 0);
        $dpPercent = (float) $request->query('dp_percent', config('credit.default_dp_percent', 20));
        $tenor = (int) $request->query('tenor', config('credit.default_tenor', 36));
        $rate = (float) $request->query('rate', config('credit.default_rate', 5.5));
        $method = (string) $request->query('method', config('credit.default_method', 'flat'));
        $downPayment = (int) round($price * max(0, min(90, $dpPercent)) / 100);

        return response()->json([
            'vehicle' => $vehicle,
            'defaults' => $this->creditDefaults(),
            'result' => $this->simulate($price, $downPayment, $tenor, $rate, $method),
            'table' => collect(config('credit.tenor_options', [12, 24, 36, 48, 60]))
                ->map(fn ($n) => $this->simulate($price, $downPayment, (int) $n, $rate, $method))
                ->values(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function creditDefaults(): array
    {
        return [
            'dp_percent' => (float) config('credit.default_dp_percent', 20),
            'tenor' => (int) config('credit.default_tenor', 36),
            'rate' => (float) config('credit.default_rate', 5.5),
            'method' => (string) config('credit.default_method', 'flat'),
            'tenor_options' => array_values(config('credit.tenor_options', [12, 24, 36, 48, 60])),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function simulate(int $price, int $downPayment, int $tenor, float $annualRate, string $method): array
    {
        $price = max(0, $price);
        $downPayment = max(0, min($price, $downPayment));
        $tenor = max(1, $tenor);
        $principal = max(0, $price - $downPayment);
        $method = $method === 'annuity' ? 'annuity' : 'flat';

        if ($principal <= 0) {
            return [
                'ok' => true,
                'method' => $method,
                'price' => $price,
                'downPayment' => $downPayment,
                'principal' => 0,
                'tenor' => $tenor,
                'annualRate' => $annualRate,
                'monthly' => 0,
                'totalInterest' => 0,
                'totalPayment' => $downPayment,
                'totalWithDp' => $downPayment,
            ];
        }

        if ($method === 'annuity') {
            $r = $annualRate / 100 / 12;
            $monthly = $r === 0.0
                ? $principal / $tenor
                : ($principal * $r * ((1 + $r) ** $tenor)) / (((1 + $r) ** $tenor) - 1);
            $monthly = (int) round($monthly);
            $totalInstallments = $monthly * $tenor;
            $totalInterest = max(0, $totalInstallments - $principal);

            return [
                'ok' => true,
                'method' => $method,
                'price' => $price,
                'downPayment' => $downPayment,
                'principal' => $principal,
                'tenor' => $tenor,
                'annualRate' => $annualRate,
                'monthly' => $monthly,
                'totalInterest' => $totalInterest,
                'totalPayment' => $totalInstallments,
                'totalWithDp' => $totalInstallments + $downPayment,
            ];
        }

        $totalInterest = (int) round($principal * ($annualRate / 100) * ($tenor / 12));
        $totalPayment = $principal + $totalInterest;
        $monthly = (int) round($totalPayment / $tenor);

        return [
            'ok' => true,
            'method' => $method,
            'price' => $price,
            'downPayment' => $downPayment,
            'principal' => $principal,
            'tenor' => $tenor,
            'annualRate' => $annualRate,
            'monthly' => $monthly,
            'totalInterest' => $totalInterest,
            'totalPayment' => $totalPayment,
            'totalWithDp' => $totalPayment + $downPayment,
        ];
    }
}
