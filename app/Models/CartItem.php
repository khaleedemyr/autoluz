<?php

namespace App\Models;

use App\Support\MediaUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = [
        'cart_id',
        'product_id',
        'product_variant_id',
        'qty',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
        ];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function lineTotal(): int
    {
        return (int) ($this->variant?->price ?? 0) * $this->qty;
    }

    public function toArrayPublic(): array
    {
        $product = $this->product;
        $variant = $this->variant;
        $price = (int) ($variant?->price ?? 0);

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'variant_id' => $this->product_variant_id,
            'qty' => $this->qty,
            'name' => $product?->name,
            'slug' => $product?->slug,
            'url' => $product ? route('shop.show', $product->slug) : null,
            'image_url' => $product?->coverUrl(),
            'variant_label' => $variant?->label(),
            'size' => $variant?->size,
            'color' => $variant?->color,
            'sku' => $variant?->sku,
            'price' => $price,
            'price_label' => MediaUrl::formatRupiah($price),
            'line_total' => $this->lineTotal(),
            'line_total_label' => MediaUrl::formatRupiah($this->lineTotal()),
            'stock' => (int) ($variant?->stock ?? 0),
            'weight_grams' => (int) ($product?->weight_grams ?? 0) * $this->qty,
            'in_stock' => (int) ($variant?->stock ?? 0) >= $this->qty,
            'store_id' => $product?->store_id,
            'store' => $product?->relationLoaded('store') && $product->store
                ? $product->store->toCardArray()
                : null,
        ];
    }
}
