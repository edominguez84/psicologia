<?php

namespace Tests\Feature\Admin;

use App\Models\SiteSetting;
use App\Models\User;
use App\Support\ColorThemes;
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

    public function test_el_formulario_expone_los_temas_predefinidos(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/theme');

        $response->assertOk();
        $response->assertViewHas('presets', fn ($presets) => count($presets) === 7 && array_key_exists('mint', $presets));
    }

    public function test_administradora_puede_aplicar_un_tema_predefinido(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/admin/theme/preset', ['preset' => 'mint']);

        $response->assertRedirect();
        $colors = SiteSetting::where('key', 'colors')->first()->value;
        $this->assertSame(ColorThemes::find('mint')['colors'], $colors);
    }

    public function test_rechaza_un_tema_que_no_existe(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/admin/theme/preset', ['preset' => 'inventado']);

        $response->assertSessionHasErrors('preset');
        $this->assertDatabaseMissing('site_settings', ['key' => 'colors']);
    }

    public function test_aplicar_un_tema_se_refleja_en_el_home(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post('/admin/theme/preset', ['preset' => 'mint']);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee(ColorThemes::find('mint')['colors']['primary'], false);
    }

    public function test_el_tema_activo_queda_marcado_como_en_uso(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post('/admin/theme/preset', ['preset' => 'forest']);

        $response = $this->actingAs($admin)->get('/admin/theme');

        $response->assertOk();
        $response->assertViewHas('activePreset', 'forest');
    }

    public function test_colores_personalizados_no_marcan_ningun_tema_como_activo(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->put('/admin/theme', $this->validPayload);

        $response = $this->actingAs($admin)->get('/admin/theme');

        $response->assertOk();
        $response->assertViewHas('activePreset', false);
    }

    public function test_el_tema_activo_se_detecta_aunque_las_claves_del_json_vengan_en_otro_orden(): void
    {
        // Regresión: MySQL no garantiza el orden de las claves de un JSON al
        // devolverlo, así que la detección de "tema activo" no puede
        // comparar arrays con === (sensible al orden), debe comparar valor
        // por valor. Se simula ese reordenamiento directo en BD.
        $admin = User::factory()->create(['role' => 'admin']);
        $mint = ColorThemes::find('mint')['colors'];
        SiteSetting::updateOrCreate(['key' => 'colors'], ['value' => [
            'text' => $mint['text'],
            'accent' => $mint['accent'],
            'primary' => $mint['primary'],
            'background' => $mint['background'],
        ]]);

        $response = $this->actingAs($admin)->get('/admin/theme');

        $response->assertOk();
        $response->assertViewHas('activePreset', 'mint');
    }
}
