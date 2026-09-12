<?php

namespace Tests\Feature;

use App\Models\ChatbotLead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatbotLeadTest extends TestCase
{
    use RefreshDatabase;

    public function test_guarda_un_contacto_del_chatbot(): void
    {
        $response = $this->postJson('/chatbot-lead', [
            'name' => 'Ana Test',
            'email' => 'ana@example.com',
            'phone' => '555-1234',
            'transcript' => [
                ['from' => 'bot', 'text' => '¡Hola!'],
                ['from' => 'user', 'text' => 'Ana Test'],
            ],
        ]);

        $response->assertOk()->assertJson(['ok' => true]);
        $this->assertDatabaseHas('chatbot_leads', [
            'name' => 'Ana Test',
            'email' => 'ana@example.com',
            'phone' => '555-1234',
        ]);
        $this->assertCount(2, ChatbotLead::first()->transcript);
    }

    public function test_requiere_nombre_y_email_validos(): void
    {
        $response = $this->postJson('/chatbot-lead', ['name' => '', 'email' => 'no-es-email']);

        $response->assertStatus(422)->assertJsonValidationErrors(['name', 'email']);
    }

    public function test_honeypot_rechaza_el_envio(): void
    {
        $response = $this->postJson('/chatbot-lead', [
            'name' => 'Bot',
            'email' => 'bot@example.com',
            'website' => 'http://spam.example',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('chatbot_leads', 0);
    }
}
