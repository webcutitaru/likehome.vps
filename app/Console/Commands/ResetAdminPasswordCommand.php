<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ResetAdminPasswordCommand extends Command
{
    protected $signature = 'likehome:reset-admin-password
                            {email=admin@likehome.md : Email cont admin}
                            {--password=ChangeMeNow!2026 : Parola nouă}';

    protected $description = 'Resetează parola unui utilizator admin/manager (pentru login Filament)';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $password = (string) $this->option('password');

        if ($email === '' || $password === '') {
            $this->error('Email și parola sunt obligatorii.');

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $user = User::query()->create([
                'email' => $email,
                'name' => 'Administrator',
                'password' => $password,
                'role' => 'admin',
                'status' => 'active',
            ]);
            $this->info("Cont creat: {$email}");

            return self::SUCCESS;
        }

        $user->password = $password;
        if ($user->status !== 'active') {
            $user->status = 'active';
        }
        $user->save();

        $this->info("Parola a fost resetată pentru {$email}.");

        return self::SUCCESS;
    }
}
