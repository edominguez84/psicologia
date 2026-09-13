<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MakeAdminUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_crea_una_cuenta_con_el_correo_marcado_como_verificado(): void
    {
        $this->artisan('make:admin', [
            '--email' => 'nueva-admin@example.com',
            '--name' => 'Nueva Admin',
            '--role' => 'super_admin',
        ])->expectsQuestion('Contraseña (mínimo 8 caracteres)', 'password123')
            ->assertSuccessful();

        $user = User::where('email', 'nueva-admin@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->email_verified_at);
    }
}
