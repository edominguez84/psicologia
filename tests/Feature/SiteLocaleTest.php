<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_sin_cookie_y_con_accept_language_en_espanol_muestra_el_contenido_en_espanol(): void
    {
        // El entorno de test (PHP CLI en este Windows) hereda un
        // Accept-Language en inglés del sistema por defecto — se fuerza
        // aquí explícitamente el header para probar el caso real de un
        // navegador cuyo idioma preferido es español.
        $response = $this->withHeader('Accept-Language', 'es-ES,es;q=0.9')->get('/');

        $response->assertOk();
        $response->assertSee('Recupera la calma que creías perdida');
    }

    public function test_sin_cookie_y_sin_preferencia_de_idioma_cae_a_espanol_por_defecto(): void
    {
        $response = $this->withHeader('Accept-Language', '')->get('/');

        $response->assertOk();
        $response->assertSee('Recupera la calma que creías perdida');
    }

    public function test_con_la_cookie_en_ingles_muestra_el_contenido_en_ingles(): void
    {
        $response = $this->withCookie('site_locale', 'en')->get('/');

        $response->assertOk();
        $response->assertSee('Find the calm you thought you had lost');
        $response->assertDontSee('Recupera la calma que creías perdida');
    }

    public function test_una_cookie_con_un_valor_invalido_se_ignora_y_usa_el_accept_language(): void
    {
        $response = $this->withCookie('site_locale', 'fr')
            ->withHeader('Accept-Language', 'es-ES,es;q=0.9')
            ->get('/');

        $response->assertOk();
        $response->assertSee('Recupera la calma que creías perdida');
    }

    public function test_el_switch_de_idioma_guarda_la_cookie_y_redirige_de_vuelta(): void
    {
        $response = $this->get('/idioma/en');

        $response->assertRedirect(route('home'));
        $response->assertCookie('site_locale', 'en');
    }

    public function test_un_idioma_no_soportado_devuelve_404(): void
    {
        $this->get('/idioma/fr')->assertNotFound();
    }

    public function test_la_pagina_de_privacidad_tambien_respeta_el_idioma_elegido(): void
    {
        $response = $this->withCookie('site_locale', 'en')->get('/privacidad');

        $response->assertOk();
        // El disclaimer del footer/legal usa config('site.footer.disclaimer'),
        // que sí cambia con el idioma aunque el título de la página legal en
        // sí (guardado en site_settings) siga en español.
        $response->assertSee('does not replace psychological or medical care', false);
    }
}
