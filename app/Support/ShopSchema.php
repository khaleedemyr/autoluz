<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class ShopSchema
{
    public static function ensure(): void
    {
        try {
            if (Cache::get('shop.schema.ready.v2')) {
                return;
            }

            if (! Schema::hasTable('products') || ! Schema::hasTable('orders')) {
                return;
            }

            static::ensureWishlist();
            static::ensureReviews();
            static::ensureDistrictColumns();
            Cache::put('shop.schema.ready.v2', true, now()->addDay());
        } catch (\Throwable) {
            try {
                Cache::forget('shop.schema.ready.v2');
            } catch (\Throwable) {
            }
        }
    }

    private static function ensureWishlist(): void
    {
        if (Schema::hasTable('wishlist_items')) {
            return;
        }

        Schema::create('wishlist_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('session_id', 80)->nullable();
            $table->unsignedBigInteger('product_id');
            $table->timestamps();

            $table->unique(['user_id', 'product_id']);
            $table->unique(['session_id', 'product_id']);
            $table->index('session_id');
            $table->index('product_id');
        });
    }

    private static function ensureReviews(): void
    {
        if (Schema::hasTable('product_reviews')) {
            return;
        }

        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedTinyInteger('rating');
            $table->text('body');
            $table->timestamps();

            $table->unique(['user_id', 'product_id']);
            $table->index(['product_id', 'created_at']);
            $table->index('order_id');
        });
    }

    private static function ensureDistrictColumns(): void
    {
        $columns = [
            'user_addresses' => ['district_id' => 20, 'district_name' => 120],
            'orders' => ['district_id' => 20, 'district_name' => 120],
            'shop_settings' => ['origin_district_id' => 20, 'origin_district_name' => 120],
            'stores' => ['origin_district_id' => 20, 'origin_district_name' => 120],
        ];

        foreach ($columns as $table => $defs) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($defs as $name => $length) {
                if (Schema::hasColumn($table, $name)) {
                    continue;
                }

                Schema::table($table, function (Blueprint $blueprint) use ($name, $length) {
                    $blueprint->string($name, $length)->nullable();
                });
            }
        }
    }
}
