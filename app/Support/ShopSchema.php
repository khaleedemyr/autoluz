<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class ShopSchema
{
    public static function ensure(): void
    {
        if (Cache::get('shop.schema.ready')) {
            return;
        }

        try {
            if (! Schema::hasTable('products') || ! Schema::hasTable('orders')) {
                return;
            }

            static::ensureWishlist();
            static::ensureReviews();
            Cache::put('shop.schema.ready', true, now()->addDay());
        } catch (\Throwable) {
            Cache::forget('shop.schema.ready');
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
}
