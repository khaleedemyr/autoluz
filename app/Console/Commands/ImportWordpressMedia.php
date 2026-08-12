<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Support\NavBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportWordpressMedia extends Command
{
    protected $signature = 'import:wordpress-media
        {--dry-run : Preview without downloading or writing}
        {--limit= : Limit number of articles to process}
        {--all : Also reprocess articles that already use local media}';

    protected $description = 'Download WordPress article images locally and rewrite URLs';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limit = $this->option('limit');
        $importLimit = $limit !== null && $limit !== '' ? (int) $limit : null;
        $onlyMissing = ! (bool) $this->option('all');

        $disk = Storage::disk('public');
        $disk->makeDirectory('articles');

        $query = Article::query()->orderBy('id');

        if ($onlyMissing) {
            $query->where(function ($q) {
                $q->where(function ($inner) {
                    $inner->whereNotNull('featured_image_url')
                        ->where('featured_image_url', 'like', 'http%');
                })->orWhere('content_html', 'like', '%src="http%')
                    ->orWhere('content_html', 'like', "%src='http%");
            });
        }

        if ($importLimit) {
            $query->limit($importLimit);
        }

        $articles = $query->get(['id', 'slug', 'featured_image_url', 'content_html']);
        $this->info('Articles to process: '.$articles->count());
        $this->line('Mode: '.($dryRun ? 'dry-run' : 'write'));

        $downloaded = 0;
        $rewritten = 0;
        $failed = 0;
        $urlCache = [];

        foreach ($articles as $article) {
            $changed = false;
            $featured = $article->featured_image_url;
            $content = $article->content_html;

            if ($featured && $this->isRemoteUrl($featured) && $this->isAllowedUrl($featured)) {
                $local = $this->localizeUrl($featured, $disk, $dryRun, $urlCache, $downloaded, $failed);
                if ($local && $local !== $featured) {
                    $featured = $local;
                    $changed = true;
                }
            }

            if (is_string($content) && $content !== '') {
                $content = preg_replace_callback(
                    '/(<img[^>]+src=["\'])([^"\']+)(["\'][^>]*>)/i',
                    function (array $matches) use ($disk, $dryRun, &$urlCache, &$downloaded, &$failed, &$changed) {
                        $src = html_entity_decode($matches[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');

                        if (! $this->isRemoteUrl($src) || ! $this->isAllowedUrl($src)) {
                            return $matches[0];
                        }

                        $local = $this->localizeUrl($src, $disk, $dryRun, $urlCache, $downloaded, $failed);
                        if ($local && $local !== $src) {
                            $changed = true;

                            return $matches[1].$local.$matches[3];
                        }

                        return $matches[0];
                    },
                    $content
                ) ?? $content;
            }

            if ($changed && ! $dryRun) {
                $article->featured_image_url = $featured;
                $article->content_html = $content;
                $article->save();
                $rewritten++;
            } elseif ($changed && $dryRun) {
                $rewritten++;
            }

            if ($rewritten > 0 && $rewritten % 25 === 0) {
                $this->line("Progress: rewritten={$rewritten}, downloaded={$downloaded}, failed={$failed}");
            }
        }

        if (! $dryRun) {
            NavBuilder::clearCache();
        }

        $this->info('Media import finished');
        $this->line("Downloaded: {$downloaded}");
        $this->line('Rewritten articles: '.$rewritten);
        $this->line("Failed: {$failed}");

        return self::SUCCESS;
    }

    protected function isRemoteUrl(?string $value): bool
    {
        if (! $value) {
            return false;
        }

        return Str::startsWith($value, ['http://', 'https://']);
    }

    protected function isAllowedUrl(string $value): bool
    {
        if (! filter_var($value, FILTER_VALIDATE_URL)) {
            return false;
        }

        $host = strtolower((string) parse_url($value, PHP_URL_HOST));
        $allow = array_filter(array_map(
            'trim',
            explode(',', (string) env('MEDIA_IMPORT_HOST_ALLOWLIST', 'autoluz.id,www.autoluz.id'))
        ));

        $wpBase = rtrim((string) env('WP_UPLOADS_BASE_URL', ''), '/');
        if ($wpBase !== '' && ! Str::startsWith($value, $wpBase)) {
            return false;
        }

        if ($allow !== [] && ! in_array($host, array_map('strtolower', $allow), true)) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<string, string>  $urlCache
     */
    protected function localizeUrl(
        string $remoteUrl,
        $disk,
        bool $dryRun,
        array &$urlCache,
        int &$downloaded,
        int &$failed
    ): ?string {
        if (isset($urlCache[$remoteUrl])) {
            return $urlCache[$remoteUrl];
        }

        // Already local
        if (! $this->isRemoteUrl($remoteUrl)) {
            return $remoteUrl;
        }

        $hash = substr(sha1($remoteUrl), 0, 20);
        $ext = $this->guessExtension($remoteUrl);
        $relative = "articles/wp-{$hash}{$ext}";
        $publicUrl = '/storage/'.$relative;

        if ($disk->exists($relative)) {
            $urlCache[$remoteUrl] = $publicUrl;

            return $publicUrl;
        }

        if ($dryRun) {
            $downloaded++;
            $urlCache[$remoteUrl] = $publicUrl;

            return $publicUrl;
        }

        try {
            $response = Http::timeout(40)
                ->withHeaders(['User-Agent' => 'AutoluzMediaBot/1.0'])
                ->withOptions(['allow_redirects' => true])
                ->get($remoteUrl);

            if (! $response->successful()) {
                $failed++;
                $this->warn("HTTP {$response->status()} for {$remoteUrl}");

                return null;
            }

            $contentType = $response->header('Content-Type');
            $ext = $this->guessExtension($remoteUrl, $contentType);
            $relative = "articles/wp-{$hash}{$ext}";
            $publicUrl = '/storage/'.$relative;

            $disk->put($relative, $response->body());
            $downloaded++;
            $urlCache[$remoteUrl] = $publicUrl;

            return $publicUrl;
        } catch (\Throwable $e) {
            $failed++;
            $this->warn('Download failed: '.$remoteUrl.' ('.$e->getMessage().')');

            return null;
        }
    }

    protected function guessExtension(string $url, ?string $contentType = null): string
    {
        $map = [
            'image/jpeg' => '.jpg',
            'image/jpg' => '.jpg',
            'image/png' => '.png',
            'image/webp' => '.webp',
            'image/gif' => '.gif',
            'image/svg+xml' => '.svg',
        ];

        $type = strtolower(trim(explode(';', (string) $contentType)[0]));
        if (isset($map[$type])) {
            return $map[$type];
        }

        $path = (string) parse_url($url, PHP_URL_PATH);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext !== '' && strlen($ext) <= 5) {
            return '.'.$ext;
        }

        return '.jpg';
    }
}
