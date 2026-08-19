<?php

namespace App\Models;

use App\Support\MediaUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ShopCheckout extends Model
{
    public const STATUS_PENDING = 'pending_payment';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'user_id',
        'number',
        'status',
        'subtotal',
        'shipping_cost',
        'grand_total',
        'midtrans_order_id',
        'snap_token',
        'payment_type',
        'paid_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
            'shipping_cost' => 'integer',
            'grand_total' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'checkout_id');
    }

    public function canPay(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Menunggu pembayaran',
            self::STATUS_PAID => 'Dibayar',
            self::STATUS_CANCELLED => 'Dibatalkan',
            self::STATUS_EXPIRED => 'Kedaluwarsa',
            default => $this->status,
        };
    }

    public function toArrayPublic(): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'subtotal' => $this->subtotal,
            'subtotal_label' => MediaUrl::formatRupiah($this->subtotal),
            'shipping_cost' => $this->shipping_cost,
            'shipping_cost_label' => MediaUrl::formatRupiah($this->shipping_cost),
            'grand_total' => $this->grand_total,
            'grand_total_label' => MediaUrl::formatRupiah($this->grand_total),
            'can_pay' => $this->canPay(),
            'paid_at' => optional($this->paid_at)?->toIso8601String(),
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'orders' => $this->relationLoaded('orders')
                ? $this->orders->map->toArrayPublic()->values()->all()
                : [],
            'url' => route('shop.checkouts.show', $this->number),
        ];
    }

    public static function nextNumber(): string
    {
        $prefix = 'ALC-'.now('Asia/Jakarta')->format('Ymd').'-';
        $last = static::query()
            ->where('number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('number');

        $seq = 1;
        if ($last && preg_match('/-(\d+)$/', $last, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public static function makeMidtransOrderId(): string
    {
        return 'ALC-'.strtoupper(Str::random(12));
    }
}
