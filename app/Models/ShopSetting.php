<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopSetting extends Model
{
    protected $fillable = [
        'store_name',
        'contact_phone',
        'pickup_address',
        'origin_province_id',
        'origin_province_name',
        'origin_city_id',
        'origin_city_name',
        'origin_district_id',
        'origin_district_name',
        'couriers',
    ];

    protected function casts(): array
    {
        return [
            'couriers' => 'array',
        ];
    }

    public static function current(): self
    {
        $row = static::query()->orderBy('id')->first();

        if ($row) {
            return $row;
        }

        return static::query()->create([
            'store_name' => 'Autoluz Shop',
            'couriers' => ['jne', 'jnt', 'pos'],
        ]);
    }

    /**
     * @return list<string>
     */
    public function courierList(): array
    {
        $list = array_values(array_filter($this->couriers ?? []));

        return $list !== [] ? $list : ['jne', 'jnt', 'pos'];
    }

    public function toAdminArray(): array
    {
        return [
            'id' => $this->id,
            'store_name' => $this->store_name,
            'contact_phone' => $this->contact_phone,
            'pickup_address' => $this->pickup_address,
            'origin_province_id' => $this->origin_province_id,
            'origin_province_name' => $this->origin_province_name,
            'origin_city_id' => $this->origin_city_id,
            'origin_city_name' => $this->origin_city_name,
            'origin_district_id' => $this->origin_district_id,
            'origin_district_name' => $this->origin_district_name,
            'couriers' => $this->courierList(),
            'origin_ready' => filled($this->origin_city_id),
        ];
    }
}
