<?php

namespace App\Models;

use App\Support\MediaUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'product_name',
        'variant_label',
        'sku',
        'price',
        'qty',
        'weight_grams',
        'image_url',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'qty' => 'integer',
            'weight_grams' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
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
        return $this->price * $this->qty;
    }

    public function toArrayPublic(): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'variant_id' => $this->product_variant_id,
            'name' => $this->product_name,
            'variant_label' => $this->variant_label,
            'sku' => $this->sku,
            'qty' => $this->qty,
            'price' => $this->price,
            'price_label' => MediaUrl::formatRupiah($this->price),
            'line_total' => $this->lineTotal(),
            'line_total_label' => MediaUrl::formatRupiah($this->lineTotal()),
            'image_url' => MediaUrl::absolute($this->image_url),
        ];
    }
}
