<?php

namespace App\Models;

use App\Support\MediaUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    public const STATUS_PENDING = 'pending_payment';
    public const STATUS_PAID = 'paid';
    public const STATUS_PACKED = 'packed';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'user_id',
        'number',
        'status',
        'subtotal',
        'shipping_cost',
        'grand_total',
        'weight_grams',
        'courier',
        'courier_service',
        'courier_service_name',
        'etd',
        'tracking_number',
        'recipient_name',
        'phone',
        'address',
        'province_id',
        'province_name',
        'city_id',
        'city_name',
        'postal_code',
        'midtrans_order_id',
        'snap_token',
        'payment_type',
        'paid_at',
        'stock_reserved_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
            'shipping_cost' => 'integer',
            'grand_total' => 'integer',
            'weight_grams' => 'integer',
            'paid_at' => 'datetime',
            'stock_reserved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function isPendingPayment(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canPay(): bool
    {
        return $this->isPendingPayment();
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Menunggu pembayaran',
            self::STATUS_PAID => 'Dibayar',
            self::STATUS_PACKED => 'Dikemas',
            self::STATUS_SHIPPED => 'Dikirim',
            self::STATUS_COMPLETED => 'Selesai',
            self::STATUS_CANCELLED => 'Dibatalkan',
            self::STATUS_EXPIRED => 'Kedaluwarsa',
            default => $this->status,
        };
    }

    /**
     * @return list<array{key: string, label: string, done: bool}>
     */
    public function timeline(): array
    {
        $steps = [
            self::STATUS_PENDING => 'Pesanan dibuat',
            self::STATUS_PAID => 'Pembayaran diterima',
            self::STATUS_PACKED => 'Dikemas',
            self::STATUS_SHIPPED => 'Dikirim',
            self::STATUS_COMPLETED => 'Selesai',
        ];

        if (in_array($this->status, [self::STATUS_CANCELLED, self::STATUS_EXPIRED], true)) {
            return [
                ['key' => self::STATUS_PENDING, 'label' => 'Pesanan dibuat', 'done' => true],
                ['key' => $this->status, 'label' => $this->statusLabel(), 'done' => true],
            ];
        }

        $order = array_keys($steps);
        $currentIndex = array_search($this->status, $order, true);
        if ($currentIndex === false) {
            $currentIndex = 0;
        }

        $timeline = [];
        foreach ($steps as $key => $label) {
            $index = array_search($key, $order, true);
            $timeline[] = [
                'key' => $key,
                'label' => $label,
                'done' => $index <= $currentIndex,
            ];
        }

        return $timeline;
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
            'courier' => strtoupper((string) $this->courier),
            'courier_service' => $this->courier_service,
            'courier_service_name' => $this->courier_service_name,
            'etd' => $this->etd,
            'tracking_number' => $this->tracking_number,
            'recipient_name' => $this->recipient_name,
            'phone' => $this->phone,
            'address' => $this->address,
            'city_name' => $this->city_name,
            'province_name' => $this->province_name,
            'postal_code' => $this->postal_code,
            'can_pay' => $this->canPay(),
            'paid_at' => optional($this->paid_at)?->toIso8601String(),
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'timeline' => $this->timeline(),
            'items' => $this->relationLoaded('items')
                ? $this->items->map->toArrayPublic()->values()->all()
                : [],
            'url' => route('shop.orders.show', $this->number),
        ];
    }

    public static function nextNumber(): string
    {
        $prefix = 'ALZ-'.now('Asia/Jakarta')->format('Ymd').'-';
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
        return 'ALZ-'.strtoupper(Str::random(12));
    }
}
