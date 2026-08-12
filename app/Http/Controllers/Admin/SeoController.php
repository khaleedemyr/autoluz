<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Support\SeoGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeoController extends Controller
{
    public function generate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string'],
            'content_html' => ['nullable', 'string'],
            'slug' => ['nullable', 'string', 'max:220'],
            'category_id' => ['nullable', 'exists:categories,id'],
        ]);

        $categoryName = null;
        if (! empty($data['category_id'])) {
            $categoryName = Category::query()->whereKey($data['category_id'])->value('name');
        }

        $seo = SeoGenerator::generate(
            $data['title'],
            $data['excerpt'] ?? null,
            $data['content_html'] ?? null,
            $data['slug'] ?? null,
            $categoryName
        );

        return response()->json($seo);
    }
}
