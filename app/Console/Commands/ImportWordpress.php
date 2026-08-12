<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportWordpress extends Command
{
    protected $signature = 'import:wordpress {--dry-run : Preview counts without writing} {--limit= : Limit number of posts to import}';

    protected $description = 'Import WordPress categories and posts into Autoluz tables';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limit = $this->option('limit');
        $importLimit = $limit !== null && $limit !== '' ? (int) $limit : null;
        $prefix = config('wordpress.table_prefix', 'wp_');

        $this->info('Starting WordPress import...');
        $this->line('Mode: ' . ($dryRun ? 'dry-run' : 'write'));
        $this->line(sprintf(
            'Source DB: %s:%s/%s',
            config('wordpress.host'),
            config('wordpress.port'),
            config('wordpress.database')
        ));
        $this->line(sprintf(
            'Target DB: %s:%s/%s',
            config('database.connections.' . config('database.default') . '.host'),
            config('database.connections.' . config('database.default') . '.port'),
            config('database.connections.' . config('database.default') . '.database')
        ));

        try {
            DB::connection('wordpress')->getPdo();
        } catch (\Throwable $e) {
            $this->error('Cannot connect to WordPress database: ' . $e->getMessage());

            return self::FAILURE;
        }

        $categoryStats = $this->importCategories($prefix, $dryRun);
        $articleStats = $this->importArticles($prefix, $dryRun, $importLimit);

        $this->info('Import finished');

        \App\Support\NavBuilder::clearCache();

        if ($dryRun) {
            $this->line("Categories: {$categoryStats['wouldImport']} would be imported (dry-run)");
            $this->line("Articles: {$articleStats['wouldImport']} would be imported (dry-run)");
        } else {
            $this->line("Categories: {$categoryStats['imported']}/{$categoryStats['total']}");
            $this->line("Articles: {$articleStats['imported']}/{$articleStats['total']}");
        }

        return self::SUCCESS;
    }

    protected function importCategories(string $prefix, bool $dryRun): array
    {
        $categories = DB::connection('wordpress')->select("
            SELECT
                t.term_id AS termId,
                t.name,
                t.slug,
                tt.description,
                tt.parent AS parentTermId
            FROM {$prefix}terms t
            INNER JOIN {$prefix}term_taxonomy tt ON tt.term_id = t.term_id
            WHERE tt.taxonomy = 'category'
            ORDER BY tt.parent ASC, t.name ASC
        ");

        $this->info('Found ' . count($categories) . ' WordPress categories');

        if ($dryRun) {
            return ['total' => count($categories), 'imported' => 0, 'wouldImport' => count($categories)];
        }

        foreach ($categories as $category) {
            DB::table('categories')->upsert(
                [[
                    'legacy_wp_term_id' => $category->termId,
                    'name' => $this->truncate($category->name, 120),
                    'slug' => $this->truncate($category->slug, 180),
                    'description' => $this->truncate($category->description, 255),
                    'parent_id' => null,
                    'sort_order' => 0,
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]],
                ['legacy_wp_term_id'],
                ['name', 'slug', 'description', 'is_active', 'updated_at']
            );
        }

        $targetRows = DB::table('categories')
            ->whereNotNull('legacy_wp_term_id')
            ->get(['id', 'legacy_wp_term_id']);

        $categoryMap = [];
        foreach ($targetRows as $row) {
            $categoryMap[(int) $row->legacy_wp_term_id] = (int) $row->id;
        }

        foreach ($categories as $category) {
            $currentId = $categoryMap[(int) $category->termId] ?? null;
            $parentId = $category->parentTermId
                ? ($categoryMap[(int) $category->parentTermId] ?? null)
                : null;

            if (!$currentId) {
                continue;
            }

            DB::table('categories')->where('id', $currentId)->update(['parent_id' => $parentId]);
        }

        return ['total' => count($categories), 'imported' => count($categories), 'wouldImport' => 0];
    }

    protected function importArticles(string $prefix, bool $dryRun, ?int $importLimit): array
    {
        $limitSql = $importLimit && is_finite($importLimit) ? 'LIMIT ' . (int) $importLimit : '';

        $posts = DB::connection('wordpress')->select("
            SELECT
                p.ID AS legacyWpId,
                p.post_name AS slug,
                p.post_title AS title,
                p.post_excerpt AS excerpt,
                p.post_content AS contentHtml,
                p.post_status AS postStatus,
                p.post_date_gmt AS publishedAt,
                p.post_modified_gmt AS updatedAt,
                thumb.guid AS featuredImageUrl
            FROM {$prefix}posts p
            LEFT JOIN {$prefix}postmeta pm
                ON pm.post_id = p.ID AND pm.meta_key = '_thumbnail_id'
            LEFT JOIN {$prefix}posts thumb
                ON thumb.ID = CAST(pm.meta_value AS UNSIGNED)
            WHERE p.post_type = 'post'
                AND p.post_status IN ('publish', 'draft', 'pending', 'future', 'private', 'trash')
            ORDER BY p.ID ASC
            {$limitSql}
        ");

        $this->info('Found ' . count($posts) . ' WordPress posts');

        if ($dryRun) {
            return ['total' => count($posts), 'imported' => 0, 'wouldImport' => count($posts)];
        }

        $categoryRows = DB::table('categories')
            ->whereNotNull('legacy_wp_term_id')
            ->get(['id', 'legacy_wp_term_id', 'slug']);

        $categoryMap = [];
        $categorySlugById = [];
        foreach ($categoryRows as $row) {
            $categoryMap[(int) $row->legacy_wp_term_id] = (int) $row->id;
            $categorySlugById[(int) $row->id] = (string) $row->slug;
        }

        // All category term ids per WP post.
        $postTerms = DB::connection('wordpress')->select("
            SELECT
                tr.object_id AS postId,
                tt.term_id AS termId
            FROM {$prefix}term_relationships tr
            INNER JOIN {$prefix}term_taxonomy tt
                ON tt.term_taxonomy_id = tr.term_taxonomy_id
            WHERE tt.taxonomy = 'category'
        ");

        $termsByPost = [];
        foreach ($postTerms as $row) {
            $termsByPost[(int) $row->postId][] = (int) $row->termId;
        }

        $imported = 0;

        foreach ($posts as $post) {
            $legacyId = (int) $post->legacyWpId;
            $slug = $this->truncate(
                $post->slug ?: Str::slug($post->title ?: "wp-post-{$legacyId}"),
                220
            );
            $title = $this->truncate($post->title ?: "Untitled {$legacyId}", 255);
            $excerpt = $this->buildExcerpt($post->excerpt, $post->contentHtml);
            $contentHtml = $this->ensureUtf8(trim((string) ($post->contentHtml ?? ''))) ?: '<p></p>';
            $featuredImageUrl = $this->truncate($post->featuredImageUrl, 600);

            $localCategoryIds = [];
            foreach ($termsByPost[$legacyId] ?? [] as $termId) {
                if (isset($categoryMap[$termId])) {
                    $localCategoryIds[] = $categoryMap[$termId];
                }
            }
            $localCategoryIds = array_values(array_unique($localCategoryIds));
            $categoryId = $this->pickPrimaryCategoryId($localCategoryIds, $categorySlugById);

            DB::table('articles')->upsert(
                [[
                    'legacy_wp_id' => $legacyId,
                    'slug' => $slug,
                    'title' => $title,
                    'excerpt' => $excerpt,
                    'content_html' => $contentHtml,
                    'featured_image_url' => $featuredImageUrl,
                    'category_id' => $categoryId,
                    'status' => $this->mapPostStatus($post->postStatus),
                    'published_at' => $this->normalizeDate($post->publishedAt),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]],
                ['legacy_wp_id'],
                [
                    'slug',
                    'title',
                    'excerpt',
                    'content_html',
                    'featured_image_url',
                    'category_id',
                    'status',
                    'published_at',
                    'updated_at',
                ]
            );

            $articleId = (int) DB::table('articles')->where('legacy_wp_id', $legacyId)->value('id');

            if ($articleId) {
                DB::table('article_category')->where('article_id', $articleId)->delete();

                $pivotRows = [];
                foreach ($localCategoryIds as $catId) {
                    $pivotRows[] = [
                        'article_id' => $articleId,
                        'category_id' => $catId,
                        'is_primary' => $categoryId === $catId ? 1 : 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if ($pivotRows !== []) {
                    DB::table('article_category')->insert($pivotRows);
                }
            }

            $imported++;
        }

        // Remove demo seed articles so mega menu / feeds show real WP content.
        $demoDeleted = DB::table('articles')->whereNull('legacy_wp_id')->delete();
        if ($demoDeleted > 0) {
            $this->line("Removed demo articles: {$demoDeleted}");
        }

        return ['total' => count($posts), 'imported' => $imported, 'wouldImport' => 0];
    }

    /**
     * Prefer editorial categories over generic Blog/Uncategorized.
     *
     * @param  list<int>  $categoryIds
     * @param  array<int, string>  $slugById
     */
    protected function pickPrimaryCategoryId(array $categoryIds, array $slugById): ?int
    {
        if ($categoryIds === []) {
            return null;
        }

        $priority = [
            'motor' => 100,
            'mobil' => 100,
            'reviews' => 95,
            'motor-baru' => 90,
            'mobil-baru' => 90,
            'hot-news-motor' => 85,
            'hot-news' => 85,
            'tips-motor' => 80,
            'tips' => 80,
            'galeri-foto-motor' => 75,
            'galeri-foto' => 75,
            'life' => 70,
            'motorsports' => 65,
            'first-drive' => 60,
            'auto-shows' => 55,
            'technology' => 50,
            'classic-cars' => 45,
            'future-cars' => 40,
            'blog' => 5,
            'uncategorized' => 1,
        ];

        $bestId = $categoryIds[0];
        $bestScore = -1;

        foreach ($categoryIds as $id) {
            $slug = $slugById[$id] ?? '';
            $score = $priority[$slug] ?? 20;
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestId = $id;
            }
        }

        return $bestId;
    }

    protected function mapPostStatus(?string $status): string
    {
        return match ($status) {
            'publish' => 'published',
            'trash' => 'archived',
            default => 'draft',
        };
    }

    protected function normalizeDate(mixed $value): ?string
    {
        if (!$value) {
            return null;
        }

        $normalized = trim((string) $value);

        if ($normalized === '' || str_starts_with($normalized, '0000-00-00')) {
            return null;
        }

        return $normalized;
    }

    protected function truncate(mixed $value, int $maxLength): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = $this->ensureUtf8(trim((string) $value));

        if ($trimmed === '') {
            return null;
        }

        return Str::limit($trimmed, $maxLength, '');
    }

    protected function buildExcerpt(?string $excerpt, ?string $content): ?string
    {
        $explicit = $this->truncate($excerpt, 1000);

        if ($explicit) {
            return $explicit;
        }

        $plain = $this->stripHtml($content);

        if (!$plain) {
            return null;
        }

        // Use mb-safe limit — byte substr() can split UTF-8 and trigger MySQL 1366.
        return Str::limit($plain, 240, '...');
    }

    protected function stripHtml(?string $input): string
    {
        $text = preg_replace('/<script[\s\S]*?<\/script>/i', ' ', (string) $input) ?? '';
        $text = preg_replace('/<style[\s\S]*?<\/style>/i', ' ', $text) ?? '';
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';

        return trim($this->ensureUtf8($text));
    }

    protected function ensureUtf8(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (!mb_check_encoding($value, 'UTF-8')) {
            $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
        }

        // Drop leftover invalid sequences so MySQL utf8mb4 never rejects the row.
        return iconv('UTF-8', 'UTF-8//IGNORE', $value) ?: '';
    }
}
