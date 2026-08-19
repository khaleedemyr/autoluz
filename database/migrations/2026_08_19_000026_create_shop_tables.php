<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 180)->unique();
            $table->string('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_category_id')->nullable()->constrained('shop_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug', 220)->unique();
            $table->text('excerpt')->nullable();
            $table->longText('description_html')->nullable();
            $table->string('cover_image_url', 600)->nullable();
            $table->unsignedInteger('weight_grams')->default(250);
            $table->boolean('featured')->default(false);
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['status', 'featured', 'sort_order']);
            $table->index('shop_category_id');
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('image_url', 600);
            $table->string('caption')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku', 80)->nullable()->unique();
            $table->string('size', 40)->nullable();
            $table->string('color', 40)->nullable();
            $table->unsignedBigInteger('price');
            $table->unsignedInteger('stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'is_active']);
        });

        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('session_id', 80)->nullable();
            $table->timestamps();

            $table->unique('user_id');
            $table->index('session_id');
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('qty')->default(1);
            $table->timestamps();

            $table->unique(['cart_id', 'product_variant_id']);
        });

        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label', 80)->nullable();
            $table->string('recipient_name');
            $table->string('phone', 30);
            $table->text('address');
            $table->string('province_id', 20);
            $table->string('province_name', 120);
            $table->string('city_id', 20);
            $table->string('city_name', 120);
            $table->string('postal_code', 12)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('number', 32)->unique();
            $table->string('status', 32)->default('pending_payment');
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('shipping_cost')->default(0);
            $table->unsignedBigInteger('grand_total')->default(0);
            $table->unsignedInteger('weight_grams')->default(0);
            $table->string('courier', 20)->nullable();
            $table->string('courier_service', 40)->nullable();
            $table->string('courier_service_name', 80)->nullable();
            $table->string('etd', 40)->nullable();
            $table->string('tracking_number', 80)->nullable();
            $table->string('recipient_name');
            $table->string('phone', 30);
            $table->text('address');
            $table->string('province_id', 20);
            $table->string('province_name', 120);
            $table->string('city_id', 20);
            $table->string('city_name', 120);
            $table->string('postal_code', 12)->nullable();
            $table->string('midtrans_order_id', 64)->nullable()->unique();
            $table->string('snap_token', 255)->nullable();
            $table->string('payment_type', 40)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('stock_reserved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->string('variant_label')->nullable();
            $table->string('sku', 80)->nullable();
            $table->unsignedBigInteger('price');
            $table->unsignedInteger('qty');
            $table->unsignedInteger('weight_grams')->default(0);
            $table->string('image_url', 600)->nullable();
            $table->timestamps();
        });

        Schema::create('shop_settings', function (Blueprint $table) {
            $table->id();
            $table->string('store_name')->default('Autoluz Shop');
            $table->string('contact_phone', 30)->nullable();
            $table->text('pickup_address')->nullable();
            $table->string('origin_province_id', 20)->nullable();
            $table->string('origin_province_name', 120)->nullable();
            $table->string('origin_city_id', 20)->nullable();
            $table->string('origin_city_name', 120)->nullable();
            $table->json('couriers')->nullable();
            $table->timestamps();
        });

        DB::table('shop_settings')->insert([
            'store_name' => 'Autoluz Shop',
            'couriers' => json_encode(['jne', 'jnt', 'pos']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('user_addresses');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('products');
        Schema::dropIfExists('shop_categories');
        Schema::dropIfExists('shop_settings');
    }
};
