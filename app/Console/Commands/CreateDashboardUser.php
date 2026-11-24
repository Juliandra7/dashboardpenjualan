<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateDashboardUser extends Command
{
    protected $signature = 'dashboard:user {name : User name} {email : User email address}';

    protected $description = 'Create a user who can sign in to the sales dashboard';

    public function handle(): int
    {
        $name = trim((string) $this->argument('name'));
        $email = filter_var(trim((string) $this->argument('email')), FILTER_VALIDATE_EMAIL);

        if ($name === '' || ! $email) {
            $this->error('Nama dan alamat email yang valid wajib diisi.');

            return self::FAILURE;
        }

        if (User::query()->where('name', $name)->exists()) {
            $this->error('Nama akun tersebut sudah digunakan. Pilih nama yang unik agar login dengan username tidak ambigu.');

            return self::FAILURE;
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->error('Email tersebut sudah terdaftar.');

            return self::FAILURE;
        }

        $password = $this->secret('Password');
        $confirmation = $this->secret('Konfirmasi password');

        if (! $password || $password !== $confirmation || strlen($password) < 8) {
            $this->error('Password harus sama dengan konfirmasi dan minimal 8 karakter.');

            return self::FAILURE;
        }

        User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $this->info("Akun untuk {$email} berhasil dibuat.");

        return self::SUCCESS;
    }
}
