<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_pagina_de_privacidad_responde_y_muestra_contenido_por_defecto(): void
    {
        $response = $this->get('/privacidad');

        $response->assertOk();
        $response->assertSee('Política de privacidad');
        $response->assertSee('Responsable del tratamiento', false);
    }

    public function test_la_pagina_de_condiciones_de_uso_responde_y_muestra_contenido_por_defecto(): void
    {
        $response = $this->get('/condiciones-de-uso');

        $response->assertOk();
        $response->assertSee('Condiciones de uso');
        $response->assertSee('Uso del sitio', false);
    }
}
