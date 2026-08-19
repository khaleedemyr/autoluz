<?php

use App\Support\AdminPermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 120)->unique();
            $table->json('permissions')->nullable();
            $table->boolean('is_super')->default(false);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('is_admin')->constrained('roles')->nullOnDelete();
        });

        $now = now();

        $superId = DB::table('roles')->insertGetId([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'permissions' => json_encode(AdminPermissions::keys()),
            'is_super' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('roles')->insert([
            'name' => 'Editor',
            'slug' => 'editor',
            'permissions' => json_encode([
                'dashboard',
                'articles',
                'events',
                'galleries',
                'categories',
                'comments',
                'videos',
            ]),
            'is_super' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('users')->where('is_admin', true)->update(['role_id' => $superId]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
        });

        Schema::dropIfExists('roles');
    }
};
