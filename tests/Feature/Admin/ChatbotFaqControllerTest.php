<?php

namespace Tests\Feature\Admin;

use App\Models\ChatbotFaq;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatbotFaqControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_normal_no_puede_gestionar_preguntas(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/chatbot-faqs')->assertForbidden();
    }

    public function test_super_admin_puede_ver_el_listado(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $faq = ChatbotFaq::factory()->create(['question' => '¿Cuánto cuesta?']);

        // El texto se pinta en el navegador vía Alpine (x-text) a partir del
        // JSON embebido en x-data, así que se comprueba que ese dato viaje
        // en la respuesta en vez de buscar el texto ya renderizado.
        $response = $this->actingAs($superAdmin)->get('/admin/chatbot-faqs');

        $response->assertOk()->assertViewHas('faqs', function ($faqs) use ($faq) {
            return $faqs->contains('id', $faq->id);
        });
    }

    public function test_super_admin_puede_crear_una_pregunta(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($superAdmin)->post('/admin/chatbot-faqs', [
            'question' => '¿Hacen descuentos?',
            'answer' => 'Sí, consulta paquetes de sesiones.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('chatbot_faqs', [
            'question' => '¿Hacen descuentos?',
            'is_active' => 1,
        ]);
    }

    public function test_super_admin_puede_editar_una_pregunta(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $faq = ChatbotFaq::factory()->create();

        $response = $this->actingAs($superAdmin)->put("/admin/chatbot-faqs/{$faq->id}", [
            'question' => 'Pregunta editada',
            'answer' => 'Respuesta editada',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('chatbot_faqs', ['id' => $faq->id, 'question' => 'Pregunta editada']);
    }

    public function test_super_admin_puede_ocultar_y_reactivar_una_pregunta(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $faq = ChatbotFaq::factory()->create(['is_active' => true]);

        $this->actingAs($superAdmin)->patch("/admin/chatbot-faqs/{$faq->id}/toggle");
        $this->assertDatabaseHas('chatbot_faqs', ['id' => $faq->id, 'is_active' => 0]);

        $this->actingAs($superAdmin)->patch("/admin/chatbot-faqs/{$faq->id}/toggle");
        $this->assertDatabaseHas('chatbot_faqs', ['id' => $faq->id, 'is_active' => 1]);
    }

    public function test_super_admin_puede_reordenar_las_preguntas(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $first = ChatbotFaq::factory()->create(['position' => 0]);
        $second = ChatbotFaq::factory()->create(['position' => 1]);

        $this->actingAs($superAdmin)->put('/admin/chatbot-faqs-reorder', [
            'ids' => [$second->id, $first->id],
        ]);

        $this->assertSame(0, $second->fresh()->position);
        $this->assertSame(1, $first->fresh()->position);
    }

    public function test_super_admin_puede_eliminar_una_pregunta(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $faq = ChatbotFaq::factory()->create();

        $this->actingAs($superAdmin)->delete("/admin/chatbot-faqs/{$faq->id}");

        $this->assertDatabaseMissing('chatbot_faqs', ['id' => $faq->id]);
    }

    public function test_endpoint_publico_solo_devuelve_preguntas_activas_y_ordenadas(): void
    {
        // Las preguntas de fábrica insertadas por la migración conviven aquí
        // con posiciones bajas (1-3); se usan posiciones muy altas para que
        // las propias del test queden siempre al final, sin depender de
        // vaciar la tabla ni de conocer cuántas preguntas de fábrica hay.
        ChatbotFaq::factory()->create(['question' => 'Oculta', 'is_active' => false, 'position' => 900]);
        ChatbotFaq::factory()->create(['question' => 'Segunda', 'is_active' => true, 'position' => 902]);
        ChatbotFaq::factory()->create(['question' => 'Primera', 'is_active' => true, 'position' => 901]);

        $response = $this->getJson('/chatbot-faqs');

        $response->assertOk();
        $questions = collect($response->json())->pluck('question')->all();
        $this->assertFalse(in_array('Oculta', $questions, true));
        $tail = array_slice($questions, -2);
        $this->assertSame(['Primera', 'Segunda'], $tail);
    }
}
