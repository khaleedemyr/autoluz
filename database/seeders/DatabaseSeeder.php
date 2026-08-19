<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Auth scaffolding user only — content comes from WordPress import + YouTube sync.
        User::query()->updateOrCreate(
            ['email' => 'admin@autoluz.local'],
            [
                'name' => 'Autoluz Admin',
                'password' => Hash::make('password'),
                'is_admin' => true,
                'role_id' => Role::super()?->id,
                'email_verified_at' => now(),
            ]
        );

        $this->call([
            EventSeeder::class,
            BrandSeeder::class,
            VehicleSeeder::class,
        ]);
    }
}
