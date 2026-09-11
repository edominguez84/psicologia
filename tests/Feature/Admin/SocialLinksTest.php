<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_no_puede_ver_el_formulario(): void
    {
        $this->get('/admin/social')->assertRedirect(route('login'));
    }

    public function test_usuario_normal_recibe_403(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get('/admin/social')->assertForbidden();
    }

    public function test_administradora_puede_guardar_urls_validas(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->put('/admin/social', [
            'facebook'  => 'https://facebook.com/erikamagana',
            'instagram' => 'https://instagram.com/erikamagana',
            'tiktok'    => '',
            'linkedin'  => '',
            'youtube'   => '',
            'x'         => '',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('site_settings', ['key' => 'social']);

        $saved = \App\Models\SiteSetting::where('key', 'social')->first()->value;
        $this->assertSame('https://facebook.com/erikamagana', $saved['facebook']);
        $this->assertArrayNotHasKey('tiktok', $saved);
    }

    public function test_rechaza_una_url_invalida(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->put('/admin/social', [
            'facebook' => 'no-es-una-url',
        ]);

        $response->assertSessionHasErrors('facebook');
        $this->assertDatabaseMissing('site_settings', ['key' => 'social']);
    }
}
