<?php

namespace App\Support;

use Illuminate\Support\Str;

class SeoGenerator
{
    /**
     * Generate SEO suggestions from article fields.
     *
     * @return array{
     *   meta_title: string,
     *   meta_description: string,
     *   focus_keyword: string,
     *   og_title: string,
     *   og_description: string,
     *   canonical_url: string|null,
     *   score: int,
     *   tips: list<string>
     * }
     */
    public static function generate(
        string $title,
        ?string $excerpt = null,
        ?string $contentHtml = null,
        ?string $slug = null,
        ?string $categoryName = null
    ): array {
        $cleanTitle = trim(html_entity_decode(strip_tags($title), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $plain = self::plainText($excerpt ?: $contentHtml ?: $cleanTitle);

        $brand = 'Autoluz';
        $metaTitle = self::fit("{$cleanTitle} | {$brand}", 60);
        if (mb_strlen($cleanTitle) > 50) {
            $metaTitle = self::fit($cleanTitle, 60);
        }

        $metaDescription = self::fit(
            $plain !== '' ? $plain : "Baca berita otomotif {$cleanTitle} di Autoluz Car & Moto.",
            155
        );

        $focusKeyword = self::guessKeyword($cleanTitle, $categoryName);
        $ogTitle = self::fit($cleanTitle, 90);
        $ogDescription = self::fit($metaDescription, 180);
        $canonical = $slug ? url('/berita/'.$slug) : null;

        $tips = [];
        $score = 40;

        if (mb_strlen($metaTitle) >= 30 && mb_strlen($metaTitle) <= 60) {
            $score += 15;
        } else {
            $tips[] = 'Meta title ideal 30–60 karakter.';
        }

        if (mb_strlen($metaDescription) >= 110 && mb_strlen($metaDescription) <= 160) {
            $score += 20;
        } else {
            $tips[] = 'Meta description ideal 110–160 karakter.';
        }

        if ($focusKeyword !== '' && Str::contains(mb_strtolower($cleanTitle), mb_strtolower($focusKeyword))) {
            $score += 15;
        } else {
            $tips[] = 'Masukkan focus keyword di judul artikel.';
        }

        if ($slug) {
            $score += 10;
        } else {
            $tips[] = 'Isi slug URL yang pendek dan deskriptif.';
        }

        if (mb_strlen($plain) >= 120) {
            $score += 10;
        } else {
            $tips[] = 'Perkaya excerpt/konten agar deskripsi SEO lebih kuat.';
        }

        if ($tips === []) {
            $tips[] = 'SEO dasar sudah bagus. Cek keyword di paragraf pertama saat publish.';
        }

        return [
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'focus_keyword' => $focusKeyword,
            'og_title' => $ogTitle,
            'og_description' => $ogDescription,
            'canonical_url' => $canonical,
            'score' => min(100, $score),
            'tips' => $tips,
        ];
    }

    protected static function plainText(?string $value): string
    {
        $text = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';

        return trim($text);
    }

    protected static function fit(string $value, int $max): string
    {
        $value = trim($value);
        if (mb_strlen($value) <= $max) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, $max - 1)).'…';
    }

    protected static function guessKeyword(string $title, ?string $categoryName): string
    {
        $stop = [
            'yang', 'untuk', 'dari', 'dengan', 'pada', 'ini', 'itu', 'dan', 'atau', 'di', 'ke', 'the', 'a', 'an',
            'ada', 'udah', 'jadi', 'bisa', 'lebih', 'saat', 'akan', 'juga', 'oleh', 'dalam', 'sudah', 'resmi',
        ];

        $words = preg_split('/[\s\-–,.:!?\/]+/u', mb_strtolower($title)) ?: [];
        $words = array_values(array_filter($words, function ($word) use ($stop) {
            return mb_strlen($word) >= 3 && ! in_array($word, $stop, true);
        }));

        if ($words === []) {
            return trim((string) $categoryName) ?: 'otomotif';
        }

        $keyword = implode(' ', array_slice($words, 0, 3));

        return self::fit($keyword, 60);
    }
}
