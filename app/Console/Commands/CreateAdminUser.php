<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    protected $signature = 'admin:create
        {--name=Autoluz Admin : Admin display name}
        {--email=admin@autoluz.local : Admin email}
        {--password=password : Admin password}';

    protected $description = 'Create or promote an admin user for the Autoluz portal';

    public function handle(): int
    {
        $email = (string) $this->option('email');
        $name = (string) $this->option('name');
        $password = (string) $this->option('password');

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'is_admin' => true,
                'email_verified_at' => now(),
            ]
        );

        $this->info('Admin ready');
        $this->line('Email: '.$user->email);
        $this->line('Password: '.$password);
        $this->line('Login: '.url('/login'));

        return self::SUCCESS;
    }
}
