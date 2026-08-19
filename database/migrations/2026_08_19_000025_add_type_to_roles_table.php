<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('type', 20)->default('admin')->after('slug');
            $table->boolean('is_default')->default(false)->after('is_super');
        });

        DB::table('roles')->update(['type' => 'admin', 'is_default' => false]);
        DB::table('roles')->where('is_super', true)->update(['type' => 'admin']);

        $now = now();
        if (! DB::table('roles')->where('slug', 'pengunjung')->exists()) {
            DB::table('roles')->insert([
                'name' => 'Pengunjung',
                'slug' => 'pengunjung',
                'type' => 'visitor',
                'permissions' => json_encode([]),
                'is_super' => false,
                'is_default' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $visitorId = DB::table('roles')->where('slug', 'pengunjung')->value('id');
        if ($visitorId) {
            DB::table('users')->whereNull('role_id')->update(['role_id' => $visitorId]);
        }
    }

    public function down(): void
    {
        $visitorId = DB::table('roles')->where('slug', 'pengunjung')->value('id');

        if ($visitorId) {
            DB::table('users')->where('role_id', $visitorId)->update(['role_id' => null]);
            DB::table('roles')->where('id', $visitorId)->delete();
        }

        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['type', 'is_default']);
        });
    }
};
