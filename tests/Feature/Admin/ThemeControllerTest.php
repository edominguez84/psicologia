<?php

namespace Tests\Feature\Admin;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeControllerTest extends TestCase
{
    use RefreshDatabase;

    private array $validPayload = [
        'primary' => '#123456',
        'background' => '#ffffff',
        'accent' => '#abcdef',
        'text' => '#111111',
        'font_heading' => 'lora',
        'font_body' => 'inter',
        'font_button' => 'karla',
    ];

    public function test_invitado_no_puede_ver_el_formulario(): void
    {
        $this->get('/admin/theme')->assertRedirect(route('login'));
    }

    public function test_usuario_normal_recibe_403(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get('/admin/theme')->assertForbidden();
    }

    public function test_administradora_puede_guardar_colores_y_tipografia(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->put('/admin/theme', $this->validPayload);

        $response->assertRedirect();
        $this->assertDatabaseHas('site_settings', ['key' => 'colors']);
        $fonts = SiteSetting::where('key', 'fonts')->first()->value;
        $this->assertSame('lora', $fonts['heading']);
        $this->assertSame('inter', $fonts['body']);
        $this->assertSame('karla', $fonts['button']);
    }

    public function test_rechaza_una_fuente_que_no_existe_en_el_catalogo(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->put('/admin/theme', [
            ...$this->validPayload,
            'font_heading' => 'comic-sans-inventada',
        ]);

        $response->assertSessionHasErrors('font_heading');
        $this->assertDatabaseMissing('site_settings', ['key' => 'fonts']);
    }

    public function test_la_tipografia_elegida_se_refleja_en_el_head_del_sitio(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->put('/admin/theme', $this->validPayload);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Lora', false);
        $response->assertSee('Inter', false);
    }

    public function test_sin_configuracion_previa_usa_las_fuentes_por_defecto(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Fraunces', false);
        $response->assertSee('Nunito+Sans', false);
    }
}
