<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_enviar_el_formulario_de_contacto_crea_el_mensaje_y_notifica_al_admin(): void
    {
        Mail::fake();

        $response = $this->postJson('/contacto', [
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'message' => 'Quisiera agendar una consulta lo antes posible.',
            'consent' => '1',
        ]);

        $response->assertOk();
        $response->assertJson(['ok' => true]);
        $this->assertDatabaseHas('contact_messages', ['name' => 'Juan Pérez', 'email' => 'juan@example.com']);
        $this->assertDatabaseHas('admin_notifications', ['type' => 'contact_message', 'feature' => 'messages']);
    }
}
