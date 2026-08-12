<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Event;
use App\Models\Gallery;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $base = rtrim(config('app.url'), '/');

        $urls = [
            ['loc' => $base.'/', 'changefreq' => 'hourly', 'priority' => '1.0'],
            ['loc' => $base.'/cari', 'changefreq' => 'weekly', 'priority' => '0.5'],
            ['loc' => $base.'/event', 'changefreq' => 'daily', 'priority' => '0.7'],
            ['loc' => $base.'/merek', 'changefreq' => 'weekly', 'priority' => '0.6'],
            ['loc' => $base.'/galeri', 'changefreq' => 'daily', 'priority' => '0.6'],
            ['loc' => $base.'/berita', 'changefreq' => 'hourly', 'priority' => '0.8'],
        ];

        foreach (Category::query()->active()->orderBy('name')->get() as $category) {
            $urls[] = [
                'loc' => $base.'/kategori/'.$category->slug,
                'changefreq' => 'daily',
                'priority' => '0.7',
            ];
        }

        foreach (Brand::query()->active()->orderBy('name')->get() as $brand) {
            $urls[] = [
                'loc' => $base.'/merek/'.$brand->slug,
                'changefreq' => 'weekly',
                'priority' => '0.6',
            ];
        }

        foreach (Event::query()->published()->orderByDesc('starts_at')->limit(200)->get() as $event) {
            $urls[] = [
                'loc' => $base.'/event/'.$event->slug,
                'lastmod' => optional($event->updated_at)?->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.6',
            ];
        }

        foreach (Gallery::query()->published()->orderByDesc('published_at')->limit(200)->get() as $gallery) {
            $urls[] = [
                'loc' => $base.'/galeri/'.$gallery->slug,
                'lastmod' => optional($gallery->updated_at ?? $gallery->published_at)?->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.6',
            ];
        }

        foreach (
            Article::query()->published()->orderByDesc('published_at')->limit(5000)->get() as $article
        ) {
            $urls[] = [
                'loc' => $base.'/berita/'.$article->slug,
                'lastmod' => optional($article->updated_at ?? $article->published_at)?->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>'.e($url['loc'])."</loc>\n";
            if (! empty($url['lastmod'])) {
                $xml .= '    <lastmod>'.e($url['lastmod'])."</lastmod>\n";
            }
            $xml .= '    <changefreq>'.e($url['changefreq'])."</changefreq>\n";
            $xml .= '    <priority>'.e($url['priority'])."</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
