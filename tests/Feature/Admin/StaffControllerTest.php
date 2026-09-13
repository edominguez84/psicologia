<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffControllerTest extends TestCase
{
    use RefreshDatabase;

    private array $validPayload = [
        'name' => 'Nueva Editora',
        'email' => 'editora@example.com',
        'password' => 'password123',
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

    public function test_rechaza_una_password_menor_a_8_caracteres(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($superAdmin)->post('/admin/staff', [
            ...$this->validPayload,
            'password' => 'corta',
        ]);

        $response->assertSessionHasErrors('password');
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
