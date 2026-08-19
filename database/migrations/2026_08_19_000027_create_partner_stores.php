<?php

use App\Models\ShopSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug', 180)->unique();
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();
            $table->string('logo_path', 600)->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->text('pickup_address')->nullable();
            $table->string('origin_province_id', 20)->nullable();
            $table->string('origin_province_name', 120)->nullable();
            $table->string('origin_city_id', 20)->nullable();
            $table->string('origin_city_name', 120)->nullable();
            $table->json('couriers')->nullable();
            $table->string('status', 20)->default('pending');
            $table->boolean('is_official')->default(false);
            $table->timestamps();

            $table->index(['status', 'is_official']);
            $table->index('user_id');
        });

        $settings = Schema::hasTable('shop_settings')
            ? DB::table('shop_settings')->orderBy('id')->first()
            : null;

        $officialId = DB::table('stores')->insertGetId([
            'user_id' => DB::table('users')->where('is_admin', true)->orderBy('id')->value('id'),
            'name' => $settings->store_name ?? 'Autoluz Shop',
            'slug' => 'autoluz',
            'tagline' => 'Official store',
            'contact_phone' => $settings->contact_phone ?? null,
            'pickup_address' => $settings->pickup_address ?? null,
            'origin_province_id' => $settings->origin_province_id ?? null,
            'origin_province_name' => $settings->origin_province_name ?? null,
            'origin_city_id' => $settings->origin_city_id ?? null,
            'origin_city_name' => $settings->origin_city_name ?? null,
            'couriers' => $settings->couriers ?? json_encode(['jne', 'jnt', 'pos']),
            'status' => 'approved',
            'is_official' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::create('shop_checkouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('number', 32)->unique();
            $table->string('status', 32)->default('pending_payment');
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('shipping_cost')->default(0);
            $table->unsignedBigInteger('grand_total')->default(0);
            $table->string('midtrans_order_id', 64)->nullable()->unique();
            $table->string('snap_token', 255)->nullable();
            $table->string('payment_type', 40)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->after('id')->constrained('stores')->restrictOnDelete();
        });

        DB::table('products')->whereNull('store_id')->update(['store_id' => $officialId]);

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->after('user_id')->constrained('stores')->restrictOnDelete();
            $table->foreignId('checkout_id')->nullable()->after('store_id')->constrained('shop_checkouts')->nullOnDelete();
        });

        DB::table('orders')->whereNull('store_id')->update(['store_id' => $officialId]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('checkout_id');
            $table->dropConstrainedForeignId('store_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('store_id');
        });

        Schema::dropIfExists('shop_checkouts');
        Schema::dropIfExists('stores');
    }
};
