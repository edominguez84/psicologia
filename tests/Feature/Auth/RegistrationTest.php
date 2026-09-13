<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Mail\LoginCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private array $validPayload = [
        'name' => 'Ana Paciente',
        'email' => 'ana@example.com',
        'phone_number' => '555-1234',
        'birth_date' => '1990-05-15',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    public function test_la_pagina_de_registro_se_puede_ver(): void
    {
        $this->get('/register')->assertOk();
    }

    public function test_registro_valido_crea_un_paciente_y_dispara_el_reto_2fa(): void
    {
        Mail::fake();

        $response = $this->post('/register', $this->validPayload);

        $user = User::where('email', 'ana@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame(UserRole::Patient, $user->role);
        $this->assertGuest();
        $response->assertRedirect(route('2fa.challenge'));
        Mail::assertSent(LoginCode::class);
    }

    public function test_el_campo_role_del_request_se_ignora_por_completo(): void
    {
        Mail::fake();

        $this->post('/register', [
            ...$this->validPayload,
            'role' => 'super_admin',
        ]);

        $user = User::where('email', 'ana@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame(UserRole::Patient, $user->role);
        $this->assertFalse($user->isAdmin());
    }

    public function test_requiere_telefono_y_fecha_de_nacimiento(): void
    {
        $response = $this->post('/register', [
            ...$this->validPayload,
            'phone_number' => '',
            'birth_date' => '',
        ]);

        $response->assertSessionHasErrors(['phone_number', 'birth_date']);
        $this->assertDatabaseMissing('users', ['email' => 'ana@example.com']);
    }

    public function test_rechaza_una_fecha_de_nacimiento_futura(): void
    {
        $response = $this->post('/register', [
            ...$this->validPayload,
            'birth_date' => now()->addYear()->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors('birth_date');
    }

    public function test_rechaza_un_email_ya_registrado(): void
    {
        User::factory()->create(['email' => 'ana@example.com']);

        $response = $this->post('/register', $this->validPayload);

        $response->assertSessionHasErrors('email');
    }

    public function test_rechaza_password_no_confirmada(): void
    {
        $response = $this->post('/register', [
            ...$this->validPayload,
            'password_confirmation' => 'otra-cosa',
        ]);

        $response->assertSessionHasErrors('password');
    }
}
