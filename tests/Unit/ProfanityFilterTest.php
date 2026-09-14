<?php

namespace Tests\Unit;

use App\Support\ProfanityFilter;
use Tests\TestCase;

class ProfanityFilterTest extends TestCase
{
    public function test_detecta_una_palabra_prohibida_de_la_lista_base(): void
    {
        $this->assertTrue(ProfanityFilter::containsProhibitedWord('Este servicio es una mierda'));
    }

    public function test_detecta_una_palabra_prohibida_en_mayusculas(): void
    {
        $this->assertTrue(ProfanityFilter::containsProhibitedWord('Eres un IDIOTA'));
    }

    public function test_no_marca_falso_positivo_por_palabra_contenida_dentro_de_otra(): void
    {
        // "cojo" no debería activar por contener parte de otra palabra
        // prohibida, y "concha" no debe confundirse con nada de la lista.
        $this->assertFalse(ProfanityFilter::containsProhibitedWord('El perro cojea un poco.'));
    }

    public function test_texto_normal_no_se_marca(): void
    {
        $this->assertFalse(ProfanityFilter::containsProhibitedWord('Estoy muy agradecida con el trato recibido.'));
    }
}
