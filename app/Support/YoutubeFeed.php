<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use SimpleXMLElement;

class YoutubeFeed
{
    public const CACHE_KEY = 'autoluz.youtube.feed.v2';

    /**
     * Latest channel videos as playlist rows for the frontend embed stage.
     *
     * @return list<array{
     *   id: string,
     *   title: string,
     *   youtube_url: string,
     *   youtube_id: string,
     *   embed_url: string,
     *   video_type: string,
     *   duration_label: null,
     *   thumbnail_url: string|null,
     *   sort_order: int
     * }>
     */
    public static function playlist(bool $forceRefresh = false): array
    {
        if ($forceRefresh) {
            self::forget();
        }

        $ttl = max(60, (int) config('youtube.cache_ttl', 1800));

        /** @var list<array<string, mixed>> $videos */
        $videos = Cache::remember(self::CACHE_KEY, $ttl, fn () => self::fetchFromChannel());

        return $videos;
    }

    public static function count(bool $forceRefresh = false): int
    {
        return count(self::playlist($forceRefresh));
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
        // Drop legacy cache key from the RSS-only importer.
        Cache::forget('autoluz.youtube.feed.v1');
        Cache::forget('autoluz.home.page.v2');
        Cache::forget('autoluz.home.page.v3');
    }

    public static function refresh(): array
    {
        self::forget();

        return self::playlist(true);
    }

    /**
     * @return list<array{
     *   id: string,
     *   title: string,
     *   youtube_url: string,
     *   youtube_id: string,
     *   embed_url: string,
     *   video_type: string,
     *   duration_label: null,
     *   thumbnail_url: string|null,
     *   sort_order: int
     * }>
     */
    protected static function fetchFromChannel(): array
    {
        $limit = max(1, (int) config('youtube.import_limit', 200));
        $shortsOnly = (bool) config('youtube.shorts_only', true);

        $entries = self::fetchShortsFromChannelPage($limit);

        if ($entries === []) {
            $entries = self::fetchFromRssFeed();
        }

        if ($shortsOnly) {
            $entries = array_values(array_filter(
                $entries,
                fn (array $entry) => ($entry['video_type'] ?? '') === 'short'
            ));
        }

        $entries = array_slice($entries, 0, $limit);

        $playlist = [];
        foreach ($entries as $index => $entry) {
            $playlist[] = [
                'id' => $entry['youtube_id'],
                'title' => $entry['title'],
                'youtube_url' => $entry['youtube_url'],
                'youtube_id' => $entry['youtube_id'],
                'embed_url' => 'https://www.youtube.com/embed/'.$entry['youtube_id'],
                'video_type' => $entry['video_type'],
                'duration_label' => null,
                'thumbnail_url' => $entry['thumbnail_url'],
                'sort_order' => $index + 1,
            ];
        }

        return $playlist;
    }

    /**
     * Scrapes the channel Shorts tab + Innertube continuations (no API key required).
     *
     * @return list<array{youtube_id: string, title: string, youtube_url: string, thumbnail_url: ?string, video_type: string}>
     */
    protected static function fetchShortsFromChannelPage(int $limit): array
    {
        $channelId = (string) config('youtube.channel_id');
        if ($channelId === '') {
            return [];
        }

        $shortsUrl = "https://www.youtube.com/channel/{$channelId}/shorts";
        $maxPages = max(1, (int) config('youtube.shorts_max_pages', 20));

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                    'Accept-Language' => 'id-ID,id;q=0.9,en;q=0.8',
                ])
                ->get($shortsUrl);
        } catch (\Throwable) {
            return [];
        }

        if (! $response->successful()) {
            return [];
        }

        $html = $response->body();
        $initial = self::extractYtInitialData($html);
        if ($initial === null) {
            return [];
        }

        $apiKey = self::extractInnertubeApiKey($html);
        $clientVersion = self::extractInnertubeClientVersion($html) ?: '2.20240101.00.00';

        $collected = [];
        $seen = [];
        self::collectShortsFromNode($initial, $collected, $seen);

        $continuation = self::findContinuationToken($initial);
        $pages = 1;

        while ($continuation && $apiKey && count($collected) < $limit && $pages < $maxPages) {
            $page = self::browseContinuation($apiKey, $clientVersion, $continuation);
            if ($page === null) {
                break;
            }

            $before = count($collected);
            self::collectShortsFromNode($page, $collected, $seen);
            $continuation = self::findContinuationToken($page);
            $pages++;

            if (count($collected) === $before) {
                break;
            }
        }

        return array_values($collected);
    }

    /**
     * RSS fallback — YouTube hard-caps this feed at ~15 entries.
     *
     * @return list<array{youtube_id: string, title: string, youtube_url: string, thumbnail_url: ?string, video_type: string}>
     */
    protected static function fetchFromRssFeed(): array
    {
        $channelId = (string) config('youtube.channel_id');
        $feedUrl = (string) config('youtube.feed_url');

        try {
            $response = Http::timeout(25)
                ->withHeaders(['User-Agent' => 'AutoluzBot/1.0'])
                ->get($feedUrl);
        } catch (\Throwable) {
            return [];
        }

        if (! $response->successful()) {
            return [];
        }

        return self::parseFeed($response->body(), $channelId);
    }

    /**
     * @return list<array{youtube_id: string, title: string, youtube_url: string, thumbnail_url: ?string, video_type: string}>
     */
    public static function parseFeed(string $xmlBody, string $expectedChannelId): array
    {
        $previous = libxml_use_internal_errors(true);

        try {
            $xml = new SimpleXMLElement($xmlBody);
        } catch (\Throwable) {
            return [];
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $entries = [];

        foreach ($xml->entry as $entry) {
            $yt = $entry->children('http://www.youtube.com/xml/schemas/2015');
            $media = $entry->children('http://search.yahoo.com/mrss/');

            $videoId = trim((string) ($yt->videoId ?? ''));
            $entryChannelId = trim((string) ($yt->channelId ?? ''));
            $title = trim((string) ($entry->title ?? ''));

            if ($videoId === '' || $title === '') {
                continue;
            }

            if ($entryChannelId !== '' && $entryChannelId !== $expectedChannelId) {
                continue;
            }

            $link = '';
            foreach ($entry->link as $linkNode) {
                $rel = (string) ($linkNode['rel'] ?? 'alternate');
                if ($rel === 'alternate') {
                    $link = (string) ($linkNode['href'] ?? '');
                    break;
                }
            }

            if ($link === '') {
                $link = "https://www.youtube.com/watch?v={$videoId}";
            }

            $thumbnail = null;
            if (isset($media->group->thumbnail)) {
                $thumbnail = (string) ($media->group->thumbnail['url'] ?? '');
            }
            if (! $thumbnail) {
                $thumbnail = "https://i.ytimg.com/vi/{$videoId}/hqdefault.jpg";
            }

            $isShort = str_contains($link, '/shorts/');

            $entries[] = [
                'youtube_id' => $videoId,
                'title' => mb_substr($title, 0, 180),
                'youtube_url' => $link,
                'thumbnail_url' => $thumbnail,
                'video_type' => $isShort ? 'short' : 'long',
            ];
        }

        return $entries;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected static function extractYtInitialData(string $html): ?array
    {
        if (! preg_match('/ytInitialData\s*=\s*(\{.+?\});/s', $html, $matches)) {
            return null;
        }

        $decoded = json_decode($matches[1], true);

        return is_array($decoded) ? $decoded : null;
    }

    protected static function extractInnertubeApiKey(string $html): ?string
    {
        if (preg_match('/"INNERTUBE_API_KEY":"([^"]+)"/', $html, $matches)) {
            return $matches[1];
        }

        return null;
    }

    protected static function extractInnertubeClientVersion(string $html): ?string
    {
        if (preg_match('/"INNERTUBE_CLIENT_VERSION":"([^"]+)"/', $html, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  array<string, array{youtube_id: string, title: string, youtube_url: string, thumbnail_url: ?string, video_type: string}>  $collected
     * @param  array<string, true>  $seen
     */
    protected static function collectShortsFromNode(array $node, array &$collected, array &$seen): void
    {
        if (isset($node['richItemRenderer']['content']['shortsLockupViewModel'])) {
            $model = $node['richItemRenderer']['content']['shortsLockupViewModel'];
            $videoId = $model['onTap']['innertubeCommand']['reelWatchEndpoint']['videoId'] ?? null;

            if (is_string($videoId) && preg_match('/^[a-zA-Z0-9_-]{11}$/', $videoId) && ! isset($seen[$videoId])) {
                $title = $model['overlayMetadata']['primaryText']['content']
                    ?? $model['accessibilityText']
                    ?? $videoId;
                $thumbnail = $model['thumbnail']['sources'][0]['url']
                    ?? "https://i.ytimg.com/vi/{$videoId}/hqdefault.jpg";

                $seen[$videoId] = true;
                $collected[$videoId] = [
                    'youtube_id' => $videoId,
                    'title' => mb_substr(trim((string) $title), 0, 180),
                    'youtube_url' => "https://www.youtube.com/shorts/{$videoId}",
                    'thumbnail_url' => is_string($thumbnail) ? $thumbnail : "https://i.ytimg.com/vi/{$videoId}/hqdefault.jpg",
                    'video_type' => 'short',
                ];
            }
        }

        if (isset($node['gridVideoRenderer']['videoId'])) {
            $videoId = (string) $node['gridVideoRenderer']['videoId'];
            if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $videoId) && ! isset($seen[$videoId])) {
                $title = $node['gridVideoRenderer']['title']['simpleText']
                    ?? ($node['gridVideoRenderer']['title']['runs'][0]['text'] ?? $videoId);
                $thumbnail = $node['gridVideoRenderer']['thumbnail']['thumbnails'][0]['url']
                    ?? "https://i.ytimg.com/vi/{$videoId}/hqdefault.jpg";

                $seen[$videoId] = true;
                $collected[$videoId] = [
                    'youtube_id' => $videoId,
                    'title' => mb_substr(trim((string) $title), 0, 180),
                    'youtube_url' => "https://www.youtube.com/shorts/{$videoId}",
                    'thumbnail_url' => is_string($thumbnail) ? $thumbnail : "https://i.ytimg.com/vi/{$videoId}/hqdefault.jpg",
                    'video_type' => 'short',
                ];
            }
        }

        foreach ($node as $value) {
            if (is_array($value)) {
                self::collectShortsFromNode($value, $collected, $seen);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $node
     */
    protected static function findContinuationToken(array $node): ?string
    {
        if (isset($node['continuationItemRenderer']['continuationEndpoint']['continuationCommand']['token'])) {
            $token = $node['continuationItemRenderer']['continuationEndpoint']['continuationCommand']['token'];

            return is_string($token) && $token !== '' ? $token : null;
        }

        foreach ($node as $key => $value) {
            // Ignore chip-bar / filter continuations; only follow grid load-more tokens.
            if ($key === 'chipBarViewModel' || $key === 'chipCloudChipRenderer') {
                continue;
            }

            if (is_array($value)) {
                $found = self::findContinuationToken($value);
                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected static function browseContinuation(string $apiKey, string $clientVersion, string $continuation): ?array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                    'Content-Type' => 'application/json',
                    'X-Youtube-Client-Name' => '1',
                    'X-Youtube-Client-Version' => $clientVersion,
                ])
                ->post("https://www.youtube.com/youtubei/v1/browse?key={$apiKey}", [
                    'context' => [
                        'client' => [
                            'clientName' => 'WEB',
                            'clientVersion' => $clientVersion,
                            'hl' => 'id',
                            'gl' => 'ID',
                        ],
                    ],
                    'continuation' => $continuation,
                ]);
        } catch (\Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $json = $response->json();

        return is_array($json) ? $json : null;
    }
}
