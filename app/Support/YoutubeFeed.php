<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use SimpleXMLElement;

class YoutubeFeed
{
    public const CACHE_KEY = 'autoluz.youtube.feed.v1';

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
        $channelId = (string) config('youtube.channel_id');
        $feedUrl = (string) config('youtube.feed_url');
        $limit = max(1, (int) config('youtube.import_limit', 15));

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

        $entries = self::parseFeed($response->body(), $channelId);
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
}
