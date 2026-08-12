<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    protected $fillable = [
        'title',
        'youtube_url',
        'youtube_id',
        'embed_url',
        'video_type',
        'duration_label',
        'thumbnail_url',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function extractYoutubeId(?string $urlOrId): ?string
    {
        if (!$urlOrId) {
            return null;
        }

        $value = trim($urlOrId);

        if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $value)) {
            return $value;
        }

        if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $value, $matches)) {
            return $matches[1];
        }

        return null;
    }

    public function toPlaylistArray(): array
    {
        $youtubeId = $this->youtube_id ?: self::extractYoutubeId($this->youtube_url);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'youtube_url' => $this->youtube_url,
            'youtube_id' => $youtubeId,
            'embed_url' => $this->embed_url ?: ($youtubeId ? "https://www.youtube.com/embed/{$youtubeId}" : null),
            'video_type' => $this->video_type,
            'duration_label' => $this->duration_label,
            'thumbnail_url' => $this->thumbnail_url ?: ($youtubeId ? "https://i.ytimg.com/vi/{$youtubeId}/hqdefault.jpg" : null),
            'sort_order' => $this->sort_order,
        ];
    }
}
