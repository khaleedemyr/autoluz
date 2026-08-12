<?php

return [
    'channel_id' => env('YOUTUBE_CHANNEL_ID', 'UCPvSp1UdLh9QC7XXMTgpNjQ'),
    'channel_url' => env('YOUTUBE_CHANNEL_URL', 'https://youtube.com/@apihmototv'),
    'channel_name' => env('YOUTUBE_CHANNEL_NAME', 'apih mototv'),
    'feed_url' => env(
        'YOUTUBE_FEED_URL',
        'https://www.youtube.com/feeds/videos.xml?channel_id='.env('YOUTUBE_CHANNEL_ID', 'UCPvSp1UdLh9QC7XXMTgpNjQ')
    ),
    // Max shorts to show (channel Shorts tab + pagination). Raise if channel has more.
    'import_limit' => (int) env('YOUTUBE_IMPORT_LIMIT', 2000),
    // Homepage only needs a short playlist — full list stays in admin / sync cache.
    'homepage_limit' => (int) env('YOUTUBE_HOMEPAGE_LIMIT', 8),
    'shorts_only' => filter_var(env('YOUTUBE_SHORTS_ONLY', true), FILTER_VALIDATE_BOOLEAN),
    'shorts_max_pages' => (int) env('YOUTUBE_SHORTS_MAX_PAGES', 50),
    // Cache TTL in seconds — homepage embeds refresh from channel automatically.
    'cache_ttl' => (int) env('YOUTUBE_CACHE_TTL', 1800),
];
