<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ProductReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductReviewController extends Controller
{
    public function store(Request $request, Product $product, ProductReviewService $reviews): RedirectResponse
    {
        $product->loadMissing('store');

        abort_unless(
            $product->status === 'published'
            && ($product->published_at === null || $product->published_at->lte(now()))
            && $product->store?->isApproved(),
            404
        );

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'body' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $reviews->upsert($request->user(), $product, (int) $data['rating'], trim($data['body']));

        return back()->with('success', 'Ulasan tersimpan.');
    }
}
