<?php

namespace Tests\Feature\Auth;

use App\Mail\LoginCode;
use App\Models\User;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private array $validPayload = [
        'name' => 'Ana Paciente',
        'email' => 'ana@example.com',
        'phone_number' => '555-1234',
        'birth_date' => '1990-05-15',
        'sex' => 'female',
        'department' => 'san-salvador',
        'municipality' => 'San Salvador',
        'password' => 'Contrasena#123',
        'password_confirmation' => 'Contrasena#123',
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
        $this->assertSame('patient', $user->role);
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
        $this->assertSame('patient', $user->role);
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

    #[DataProvider('debilesProvider')]
    public function test_rechaza_contrasenas_que_no_cumplen_el_criterio_de_fuerza(string $weak): void
    {
        $response = $this->post('/register', [
            ...$this->validPayload,
            'password' => $weak,
            'password_confirmation' => $weak,
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['email' => 'ana@example.com']);
    }

    public static function debilesProvider(): array
    {
        return [
            'menos de 10 caracteres' => ['Ab1#567'],
            'sin mayúscula' => ['contrasena#123'],
            'sin minúscula' => ['CONTRASENA#123'],
            'sin número' => ['Contrasena#abc'],
            'sin símbolo' => ['Contrasena1234'],
        ];
    }

    public function test_guarda_sexo_departamento_y_municipio(): void
    {
        Mail::fake();

        $this->post('/register', $this->validPayload);

        $user = User::where('email', 'ana@example.com')->first();
        $this->assertSame('female', $user->sex);
        $this->assertSame('san-salvador', $user->department);
        $this->assertSame('San Salvador', $user->municipality);
    }

    public function test_requiere_sexo_departamento_y_municipio(): void
    {
        $response = $this->post('/register', [
            ...$this->validPayload,
            'sex' => '',
            'department' => '',
            'municipality' => '',
        ]);

        $response->assertSessionHasErrors(['sex', 'department', 'municipality']);
        $this->assertDatabaseMissing('users', ['email' => 'ana@example.com']);
    }

    public function test_rechaza_un_departamento_que_no_existe(): void
    {
        $response = $this->post('/register', [
            ...$this->validPayload,
            'department' => 'departamento-inventado',
        ]);

        $response->assertSessionHasErrors('department');
    }

    public function test_rechaza_un_municipio_que_no_pertenece_al_departamento(): void
    {
        // 'Santa Ana' pertenece al departamento de Santa Ana, no a San Salvador.
        $response = $this->post('/register', [
            ...$this->validPayload,
            'department' => 'san-salvador',
            'municipality' => 'Santa Ana',
        ]);

        $response->assertSessionHasErrors('municipality');
        $this->assertDatabaseMissing('users', ['email' => 'ana@example.com']);
    }

    public function test_registrarse_desde_una_promocion_lleva_a_agendar_esa_promocion_tras_el_2fa(): void
    {
        Mail::fake();
        $promotion = \App\Models\Promotion::create([
            'title' => 'Paquete inicial', 'price' => 45, 'description' => 'x', 'is_active' => true,
        ]);

        $this->post('/register', [...$this->validPayload, 'promotion' => $promotion->id]);

        $sent = null;
        Mail::assertSent(\App\Mail\LoginCode::class, function ($mail) use (&$sent) {
            $sent = $mail->code;

            return true;
        });

        $response = $this->post('/2fa/verify', ['code' => $sent]);

        $response->assertRedirect(route('patient.appointments.index', ['promotion' => $promotion->id]));
    }

    private function enableSmsChannel(): void
    {
        config([
            'services.twilio.sid' => 'AC-test-sid',
            'services.twilio.token' => 'test-token',
            'services.twilio.from' => '+14483332827',
        ]);
        app(SiteSettingsService::class)->set('security', ['channels' => ['sms' => true]]);
    }

    public function test_el_selector_de_metodo_no_aparece_si_sms_no_esta_disponible(): void
    {
        $response = $this->get('/register');

        $response->assertOk();
        $response->assertDontSee('name="two_factor_method"', false);
    }

    public function test_el_selector_de_metodo_aparece_si_sms_esta_disponible(): void
    {
        $this->enableSmsChannel();

        $response = $this->get('/register');

        $response->assertOk();
        $response->assertSee('name="two_factor_method"', false);
    }

    public function test_registrarse_eligiendo_sms_guarda_el_metodo_sms(): void
    {
        $this->enableSmsChannel();
        Mail::fake();

        $this->post('/register', [...$this->validPayload, 'two_factor_method' => 'sms']);

        $user = User::where('email', 'ana@example.com')->first();
        $this->assertSame('sms', $user->two_factor_method);
    }

    public function test_registrarse_sin_elegir_metodo_sigue_quedando_email_por_defecto(): void
    {
        Mail::fake();

        $this->post('/register', $this->validPayload);

        $user = User::where('email', 'ana@example.com')->first();
        $this->assertSame('email', $user->two_factor_method);
    }

    public function test_no_se_puede_forzar_whatsapp_desde_el_registro(): void
    {
        $this->enableSmsChannel();

        $response = $this->post('/register', [...$this->validPayload, 'two_factor_method' => 'whatsapp']);

        $response->assertSessionHasErrors('two_factor_method');
        $this->assertDatabaseMissing('users', ['email' => 'ana@example.com']);
    }
}
