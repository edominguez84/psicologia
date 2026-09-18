<?php

namespace Tests\Feature;

use App\Models\Promotion;
use App\Models\Testimonial;
use App\Models\User;
use App\Services\SiteSettingsService;
use App\Support\LandingTemplates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Test de humo por plantilla: las 3 (App\Support\LandingTemplates::all())
 * deben renderizar 200 OK con datos reales (testimonio aprobado, promoción
 * activa) y seguir respetando section_visibility — cada una es una vista
 * Blade completamente distinta, así que un error de sintaxis en cualquiera
 * solo se detecta ejecutándola de verdad (php -l no valida Blade).
 */
class LandingTemplatesSmokeTest extends TestCase
{
    use RefreshDatabase;

    private function activate(string $template): void
    {
        app(SiteSettingsService::class)->set('landing_template', ['key' => $template]);
    }

    public static function templateKeysProvider(): array
    {
        return array_map(fn ($key) => [$key], array_keys(LandingTemplates::all()));
    }

    #[DataProvider('templateKeysProvider')]
    public function test_la_plantilla_renderiza_200_con_datos_reales(string $template): void
    {
        $this->activate($template);
        $patient = User::factory()->create(['role' => 'patient']);
        $testimonial = Testimonial::create(['user_id' => $patient->id, 'text' => 'Excelente atención, muy recomendable.', 'rating' => 5]);
        $testimonial->forceFill(['is_approved' => true])->save();
        Promotion::create(['title' => 'Plan mensual', 'price' => 40, 'description' => 'Cuatro sesiones al mes.', 'is_active' => true]);

        $response = $this->withHeader('Accept-Language', 'es-ES,es;q=0.9')->get('/');

        $response->assertOk();
        $response->assertSee('Excelente atención, muy recomendable.');
        $response->assertSee('Plan mensual');
    }

    #[DataProvider('templateKeysProvider')]
    public function test_la_plantilla_respeta_una_seccion_oculta(string $template): void
    {
        $this->activate($template);
        app(SiteSettingsService::class)->set('section_visibility', ['myths' => false]);

        $response = $this->withHeader('Accept-Language', 'es-ES,es;q=0.9')->get('/');

        $response->assertOk();
        $response->assertDontSee('id="mitos"', false);
    }

    public function test_una_key_de_plantilla_invalida_cae_a_la_plantilla_por_defecto(): void
    {
        app(SiteSettingsService::class)->set('landing_template', ['key' => 'plantilla-que-no-existe']);

        $response = $this->get('/');

        $response->assertOk();
    }

    public function test_sin_plantilla_configurada_usa_la_clasica_por_defecto(): void
    {
        $response = $this->get('/');

        $response->assertOk();
    }
}
