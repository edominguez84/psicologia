<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StaffControllerTest extends TestCase
{
    use RefreshDatabase;

    private array $validPayload = [
        'name' => 'Nueva Editora',
        'email' => 'editora@example.com',
        'password' => 'Contrasena#123',
        'role' => 'editor',
    ];

    public function test_invitado_no_puede_ver_el_formulario(): void
    {
        $this->get('/admin/staff/create')->assertRedirect(route('login'));
    }

    public function test_admin_normal_no_puede_crear_cuentas(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/staff/create')->assertForbidden();
        $this->actingAs($admin)->post('/admin/staff', $this->validPayload)->assertForbidden();
    }

    public function test_super_admin_puede_crear_una_cuenta_de_cualquier_rol(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($superAdmin)->post('/admin/staff', $this->validPayload);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'editora@example.com',
            'role' => 'editor',
        ]);
        $user = User::where('email', 'editora@example.com')->first();
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_rechaza_un_email_ya_registrado(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        User::factory()->create(['email' => 'editora@example.com']);

        $response = $this->actingAs($superAdmin)->post('/admin/staff', $this->validPayload);

        $response->assertSessionHasErrors('email');
    }

    #[DataProvider('debilesProvider')]
    public function test_rechaza_contrasenas_que_no_cumplen_el_criterio_de_fuerza(string $weak): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($superAdmin)->post('/admin/staff', [
            ...$this->validPayload,
            'password' => $weak,
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['email' => 'editora@example.com']);
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

    public function test_rechaza_un_rol_que_no_existe_en_el_enum(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($superAdmin)->post('/admin/staff', [
            ...$this->validPayload,
            'role' => 'rol-inventado',
        ]);

        $response->assertSessionHasErrors('role');
    }
}
