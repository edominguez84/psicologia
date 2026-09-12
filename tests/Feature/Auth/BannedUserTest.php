<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BannedUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_baneado_no_puede_iniciar_sesion(): void
    {
        $user = User::factory()->create(['banned_at' => now()]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_usuario_baneado_a_mitad_de_sesion_pierde_acceso_en_la_siguiente_peticion(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)->get('/admin')->assertOk();

        $user->update(['banned_at' => now()]);

        $response = $this->actingAs($user, 'web')->get('/admin');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
