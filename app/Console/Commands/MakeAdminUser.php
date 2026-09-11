<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class MakeAdminUser extends Command
{
    /**
     * Crea o actualiza la cuenta de administración del panel /admin.
     * Nunca se guarda una contraseña real en el repositorio: se pide de forma
     * interactiva (o se toma de ADMIN_EMAIL/ADMIN_PASSWORD si están en .env,
     * útil para scripts de despliegue no interactivos).
     */
    protected $signature = 'make:admin {--email=} {--name=}';

    protected $description = 'Crea o convierte en administradora una cuenta del panel /admin';

    public function handle(): int
    {
        $email = $this->option('email') ?: env('ADMIN_EMAIL') ?: $this->ask('Email de la administradora');
        $name = $this->option('name') ?: env('ADMIN_NAME') ?: $this->ask('Nombre', 'Administradora');

        $validator = Validator::make(['email' => $email], ['email' => ['required', 'email']]);
        if ($validator->fails()) {
            $this->error('Email no válido.');

            return self::FAILURE;
        }

        $existing = User::where('email', $email)->first();

        if ($existing && $existing->isAdmin()) {
            if (! $this->confirm("Ya existe una administradora con {$email}. ¿Actualizar la contraseña?", false)) {
                $this->info('Sin cambios.');

                return self::SUCCESS;
            }
        }

        $password = env('ADMIN_PASSWORD') ?: $this->secret('Contraseña (mínimo 8 caracteres)');

        if (strlen((string) $password) < 8) {
            $this->error('La contraseña debe tener al menos 8 caracteres.');

            return self::FAILURE;
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        $this->info("Cuenta admin lista: {$user->email}");

        return self::SUCCESS;
    }
}
