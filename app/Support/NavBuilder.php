<?php

namespace App\Support;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;

class NavBuilder
{
    /**
     * Mega menu groups matching Autoluz WordPress IA.
     */
    private const MEGA = [
        'mobil' => [
            'label_key' => 'mobil',
            'root' => 'mobil',
            'subs' => [
                'all' => 'all',
                'galeri-foto' => 'galeri-foto',
                'hot-news' => 'hot-news',
                'mobil-baru' => 'mobil-baru',
                'tips' => 'tips',
            ],
            // Only true mobil taxonomies (do not mix motor tips/hot-news).
            'group_slugs' => ['mobil', 'galeri-foto', 'hot-news', 'mobil-baru', 'tips', 'life', 'car-news', 'auto-shows', 'classic-cars', 'first-drive', 'future-cars'],
        ],
        'motor' => [
            'label_key' => 'motor',
            'root' => 'motor',
            'subs' => [
                'all' => 'all',
                'galeri-foto-motor' => 'galeri-foto-motor',
                'hot-news-motor' => 'hot-news-motor',
                'motor-baru' => 'motor-baru',
                'tips-motor' => 'tips-motor',
            ],
            'group_slugs' => ['motor', 'galeri-foto-motor', 'hot-news-motor', 'motor-baru', 'tips-motor', 'motorsports'],
        ],
        'reviews' => [
            'label_key' => 'reviews',
            'root' => 'reviews',
            'subs' => [
                'all' => 'all',
            ],
            'group_slugs' => ['reviews'],
        ],
    ];

    private const MORE_SLUGS = [
        'life',
        'mobil-baru',
        'motor-baru',
        'auto-shows',
        'classic-cars',
        'first-drive',
        'future-cars',
        'motorsports',
        'technology',
        'blog',
    ];

    /**
     * @return array{items: list<array>, more: list<array>, primary: list<array>}
     */
    public static function build(): array
    {
        $locale = app()->getLocale();

        return Cache::remember('autoluz.nav.mega.v3.'.$locale, 120, function () {
            $categories = Category::query()
                ->active()
                ->get()
                ->keyBy('slug');

            $items = [];

            foreach (self::MEGA as $key => $config) {
                $root = $categories->get($config['root']);
                if (! $root) {
                    continue;
                }

                $groupIds = self::resolveIds($categories, $config['group_slugs']);

                $subLinks = [];
                $panels = [];

                foreach ($config['subs'] as $subSlug => $labelKey) {
                    if ($subSlug === 'all') {
                        $subLinks[] = [
                            'key' => 'all',
                            'label' => self::label($labelKey, 'All'),
                            'slug' => $root->slug,
                            'href_slug' => $root->slug,
                        ];
                        $panels['all'] = self::latestArticlesForCategories($groupIds, 8);
                        continue;
                    }

                    $cat = $categories->get($subSlug);
                    if (! $cat) {
                        continue;
                    }

                    $subLinks[] = [
                        'key' => $subSlug,
                        'label' => self::label($labelKey, $cat->name),
                        'slug' => $cat->slug,
                        'href_slug' => $cat->slug,
                    ];
                    $panels[$subSlug] = self::latestArticlesForCategories([(int) $cat->id], 8);
                }

                $items[] = [
                    'key' => $key,
                    'id' => $root->id,
                    'name' => self::label($config['label_key'], $root->name),
                    'slug' => $root->slug,
                    'has_mega' => true,
                    'subs' => $subLinks,
                    'panels' => $panels,
                ];
            }

            $more = [];
            foreach (self::MORE_SLUGS as $slug) {
                $cat = $categories->get($slug);
                if ($cat) {
                    $more[] = $cat->toNavArray();
                }
            }

            $primary = array_map(fn (array $item) => [
                'id' => $item['id'],
                'name' => $item['name'],
                'slug' => $item['slug'],
            ], $items);

            return [
                'items' => $items,
                'more' => $more,
                'primary' => $primary,
            ];
        });
    }

    private static function label(string $key, string $fallback): string
    {
        $translated = __('nav.'.$key);

        return $translated !== 'nav.'.$key ? $translated : $fallback;
    }

    /**
     * @param  list<string>  $slugs
     * @return list<int>
     */
    private static function resolveIds($categories, array $slugs): array
    {
        $ids = [];
        foreach ($slugs as $slug) {
            $cat = $categories->get($slug);
            if ($cat) {
                $ids[] = (int) $cat->id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Latest published articles that belong to any of the given categories
     * (primary category_id OR article_category pivot).
     *
     * @param  list<int>  $categoryIds
     * @return list<array>
     */
    private static function latestArticlesForCategories(array $categoryIds, int $limit = 8): array
    {
        if ($categoryIds === []) {
            return [];
        }

        return Article::query()
            ->with('category')
            ->published()
            ->inCategories($categoryIds)
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get()
            ->map(fn (Article $article) => [
                'id' => $article->id,
                'slug' => $article->slug,
                'title' => $article->title,
                'featured_image_url' => $article->toCardArray()['featured_image_url'],
                'published_at' => optional($article->published_at)?->toIso8601String(),
                'category' => $article->category ? [
                    'name' => $article->category->localizedName(),
                    'slug' => $article->category->slug,
                ] : null,
            ])
            ->values()
            ->all();
    }

    public static function clearCache(): void
    {
        Cache::forget('autoluz.nav.mega.v1');
        Cache::forget('autoluz.nav.mega.v2');
        foreach (['id', 'en'] as $locale) {
            Cache::forget('autoluz.nav.mega.v3.'.$locale);
        }
    }
}
