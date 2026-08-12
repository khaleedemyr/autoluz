<?php

namespace App\Console\Commands;

use App\Support\YoutubeFeed;
use Illuminate\Console\Command;

class SyncYoutubeChannel extends Command
{
    protected $signature = 'youtube:sync
        {--dry-run : Preview videos without refreshing cache}';

    protected $description = 'Refresh live YouTube channel playlist cache (apih mototv) for homepage embeds';

    public function handle(): int
    {
        $channelId = config('youtube.channel_id');
        $this->info('Refreshing YouTube feed cache: '.config('youtube.channel_name'));
        $this->line("Channel ID: {$channelId}");
        $this->line('Feed: '.config('youtube.feed_url'));

        if ($this->option('dry-run')) {
            YoutubeFeed::forget();
            $videos = YoutubeFeed::playlist(true);

            if ($videos === []) {
                $this->warn('No videos found in feed.');

                return self::FAILURE;
            }

            foreach ($videos as $i => $video) {
                $this->line(($i + 1).'. ['.$video['youtube_id'].'] '.$video['title']);
            }

            return self::SUCCESS;
        }

        $videos = YoutubeFeed::refresh();

        if ($videos === []) {
            $this->warn('No videos found in feed. Cache not updated with useful data.');

            return self::FAILURE;
        }

        $this->info('Cached '.count($videos).' videos for embed (no DB write).');

        return self::SUCCESS;
    }
}
