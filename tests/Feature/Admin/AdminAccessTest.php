<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_es_redirigido_a_login(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_usuario_normal_recibe_403(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_administradora_puede_entrar(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_administradora_puede_actualizar_colores(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->put('/admin/theme', [
            'primary' => '#123456',
            'background' => '#ffffff',
            'accent' => '#abcdef',
            'text' => '#111111',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('site_settings', ['key' => 'colors']);
    }
}
