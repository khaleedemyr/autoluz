<?php

namespace App\Support;

class AdminPermissions
{
    public const CATALOG = [
        'dashboard' => 'Dashboard',
        'articles' => 'Artikel',
        'events' => 'Event',
        'galleries' => 'Galeri',
        'brands' => 'Merek',
        'vehicles' => 'Kendaraan',
        'newsletter' => 'Newsletter',
        'categories' => 'Kategori',
        'comments' => 'Komentar',
        'videos' => 'Video',
        'users' => 'Users',
        'roles' => 'Role',
    ];

    /**
     * @return array<int, array{key: string, label: string}>
     */
    public static function catalog(): array
    {
        return collect(self::CATALOG)
            ->map(fn (string $label, string $key) => ['key' => $key, 'label' => $label])
            ->values()
            ->all();
    }

    public static function keys(): array
    {
        return array_keys(self::CATALOG);
    }

    public static function fromRoute(?string $name): ?string
    {
        if (! $name || ! str_starts_with($name, 'admin.')) {
            return null;
        }

        if ($name === 'admin.dashboard') {
            return 'dashboard';
        }

        if ($name === 'admin.seo.generate' || str_starts_with($name, 'admin.articles.')) {
            return 'articles';
        }

        foreach (self::keys() as $key) {
            if (str_starts_with($name, 'admin.'.$key.'.')) {
                return $key;
            }
        }

        return null;
    }
}
