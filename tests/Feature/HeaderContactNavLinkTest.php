<?php

namespace Tests\Feature;

use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El link "Contacto" del menú principal debe respetar la misma visibilidad
 * que ya controla si la sección de contacto se muestra en la home (ver
 * /admin/section-visibility) — mismo patrón que el resto de links del nav
 * (Sobre mí, Preguntas, etc.).
 */
class HeaderContactNavLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_link_de_contacto_aparece_por_defecto(): void
    {
        $response = $this->withHeader('Accept-Language', 'es-ES,es;q=0.9')->get('/');

        $response->assertOk();
        $response->assertSee('href="/#contacto"', false);
    }

    public function test_el_link_de_contacto_se_oculta_si_la_seccion_esta_oculta(): void
    {
        app(SiteSettingsService::class)->set('section_visibility', ['contact_section' => false]);

        $response = $this->withHeader('Accept-Language', 'es-ES,es;q=0.9')->get('/');

        $response->assertOk();
        $response->assertDontSee('href="/#contacto"', false);
    }
}
