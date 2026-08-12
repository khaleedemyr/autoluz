<?php

return [
    'channel_id' => env('YOUTUBE_CHANNEL_ID', 'UCPvSp1UdLh9QC7XXMTgpNjQ'),
    'channel_url' => env('YOUTUBE_CHANNEL_URL', 'https://youtube.com/@apihmototv'),
    'channel_name' => env('YOUTUBE_CHANNEL_NAME', 'apih mototv'),
    'feed_url' => env(
        'YOUTUBE_FEED_URL',
        'https://www.youtube.com/feeds/videos.xml?channel_id='.env('YOUTUBE_CHANNEL_ID', 'UCPvSp1UdLh9QC7XXMTgpNjQ')
    ),
    'import_limit' => (int) env('YOUTUBE_IMPORT_LIMIT', 15),
    // Cache TTL in seconds — homepage embeds refresh from channel automatically.
    'cache_ttl' => (int) env('YOUTUBE_CACHE_TTL', 1800),
];
