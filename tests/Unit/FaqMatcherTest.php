<?php

namespace Tests\Unit;

use App\Models\ChatbotFaq;
use App\Support\FaqMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqMatcherTest extends TestCase
{
    use RefreshDatabase;

    /**
     * La migración de chatbot_faqs siembra 3 preguntas de fábrica reales —
     * se desactivan aquí para que los casos de esta prueba no compitan con
     * ellas por coincidencia de palabras.
     */
    protected function setUp(): void
    {
        parent::setUp();
        ChatbotFaq::query()->update(['is_active' => false]);
    }

    public function test_encuentra_la_faq_con_mas_palabras_en_comun(): void
    {
        ChatbotFaq::create(['question' => '¿Ofrecen descuentos por paquete de sesiones?', 'answer' => 'Sí, con paquete.', 'is_active' => true]);
        ChatbotFaq::create(['question' => '¿Cuánto cuesta la consulta individual?', 'answer' => '$40.', 'is_active' => true]);

        $match = FaqMatcher::match('quiero saber si tienen descuentos por paquete de varias sesiones');

        $this->assertSame('Sí, con paquete.', $match->answer);
    }

    public function test_no_encuentra_nada_sin_coincidencia(): void
    {
        ChatbotFaq::create(['question' => '¿Ofrecen descuentos por paquete de sesiones?', 'answer' => 'Sí.', 'is_active' => true]);

        $this->assertNull(FaqMatcher::match('hola buenas tardes'));
    }

    public function test_ignora_faqs_inactivas(): void
    {
        ChatbotFaq::create(['question' => 'Pregunta exclusiva de esta prueba unitaria', 'answer' => 'Respuesta.', 'is_active' => false]);

        $this->assertNull(FaqMatcher::match('Pregunta exclusiva de esta prueba unitaria'));
    }

    public function test_menu_lista_las_preguntas_activas(): void
    {
        ChatbotFaq::create(['question' => '¿Ofrecen descuentos por paquete de sesiones?', 'answer' => 'Sí.', 'is_active' => true, 'position' => 1]);

        $this->assertStringContainsString('¿Ofrecen descuentos por paquete de sesiones?', FaqMatcher::menuText());
    }

    public function test_menu_avisa_si_no_hay_faqs_cargadas(): void
    {
        $this->assertStringContainsString('WhatsApp', FaqMatcher::menuText());
    }
}
