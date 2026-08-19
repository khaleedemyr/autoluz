<?php

namespace App\Models;

use App\Support\MediaUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    protected $fillable = [
        'product_id',
        'image_url',
        'caption',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function toArrayPublic(): array
    {
        return [
            'id' => $this->id,
            'image_url' => MediaUrl::absolute($this->image_url),
            'caption' => $this->caption,
            'sort_order' => $this->sort_order,
        ];
    }
}
