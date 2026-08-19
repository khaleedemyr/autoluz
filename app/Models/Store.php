<?php

namespace App\Models;

use App\Support\MediaUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Store extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'tagline',
        'description',
        'logo_path',
        'contact_phone',
        'pickup_address',
        'origin_province_id',
        'origin_province_name',
        'origin_city_id',
        'origin_city_name',
        'origin_district_id',
        'origin_district_name',
        'couriers',
        'status',
        'is_official',
    ];

    protected function casts(): array
    {
        return [
            'couriers' => 'array',
            'is_official' => 'boolean',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function originReady(): bool
    {
        return filled($this->origin_city_id);
    }

    public function originDestinationId(): ?string
    {
        $id = $this->origin_district_id ?: $this->origin_city_id;

        return filled($id) ? (string) $id : null;
    }

    /**
     * @return list<string>
     */
    public function courierList(): array
    {
        $list = array_values(array_filter($this->couriers ?? []));
        if ($list !== []) {
            return $list;
        }

        return ShopSetting::current()->courierList();
    }

    public function logoUrl(): ?string
    {
        return MediaUrl::absolute($this->logo_path);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Menunggu persetujuan',
            self::STATUS_APPROVED => 'Aktif',
            self::STATUS_SUSPENDED => 'Ditangguhkan',
            default => $this->status,
        };
    }

    public function toCardArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'tagline' => $this->tagline,
            'logo_url' => $this->logoUrl(),
            'is_official' => $this->is_official,
            'url' => route('shop.stores.show', $this->slug),
        ];
    }

    public function toPublicArray(): array
    {
        return [
            ...$this->toCardArray(),
            'description' => $this->description,
            'origin_city_name' => $this->origin_city_name,
            'origin_ready' => $this->originReady(),
        ];
    }

    public function toSettingsArray(): array
    {
        return [
            ...$this->toPublicArray(),
            'contact_phone' => $this->contact_phone,
            'pickup_address' => $this->pickup_address,
            'origin_province_id' => $this->origin_province_id,
            'origin_province_name' => $this->origin_province_name,
            'origin_city_id' => $this->origin_city_id,
            'origin_city_name' => $this->origin_city_name,
            'origin_district_id' => $this->origin_district_id,
            'origin_district_name' => $this->origin_district_name,
            'couriers' => $this->courierList(),
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'user_id' => $this->user_id,
            'owner' => $this->relationLoaded('owner') && $this->owner ? [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
                'email' => $this->owner->email,
            ] : null,
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function courierOptions(): array
    {
        return [
            ['value' => 'jne', 'label' => 'JNE'],
            ['value' => 'jnt', 'label' => 'J&T'],
            ['value' => 'pos', 'label' => 'POS'],
            ['value' => 'sicepat', 'label' => 'SiCepat'],
            ['value' => 'tiki', 'label' => 'TIKI'],
        ];
    }

    public static function official(): ?self
    {
        return static::query()->where('is_official', true)->orderBy('id')->first();
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'toko';
        $slug = $base;
        $i = 2;

        while (
            static::query()
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
