<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAddress extends Model
{
    protected $fillable = [
        'user_id',
        'label',
        'recipient_name',
        'phone',
        'address',
        'province_id',
        'province_name',
        'city_id',
        'city_name',
        'district_id',
        'district_name',
        'postal_code',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function toArrayPublic(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'recipient_name' => $this->recipient_name,
            'phone' => $this->phone,
            'address' => $this->address,
            'province_id' => $this->province_id,
            'province_name' => $this->province_name,
            'city_id' => $this->city_id,
            'city_name' => $this->city_name,
            'district_id' => $this->district_id,
            'district_name' => $this->district_name,
            'postal_code' => $this->postal_code,
            'is_default' => $this->is_default,
            'summary' => trim($this->address.', '.collect([$this->district_name, $this->city_name, $this->province_name])->filter()->implode(', ')),
        ];
    }

    public function destinationId(): string
    {
        return (string) ($this->district_id ?: $this->city_id);
    }
}
