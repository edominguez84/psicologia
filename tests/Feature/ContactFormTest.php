<?php

namespace Tests\Feature;

use App\Mail\ContactReceived;
use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_guarda_un_mensaje_valido_y_envia_email(): void
    {
        Mail::fake();
        config(['site.contact.email' => 'psicologa@example.com']);

        $response = $this->postJson('/contacto', [
            'name' => 'Ana López',
            'email' => 'ana@example.com',
            'phone' => '600111222',
            'subject' => 'Consulta sobre EMDR',
            'message' => 'Me gustaría saber más sobre las sesiones online.',
            'preferred_contact' => 'email',
            'consent' => true,
            'website' => '',
        ]);

        $response->assertOk()->assertJson(['ok' => true]);
        $this->assertDatabaseCount('contact_messages', 1);
        $this->assertDatabaseHas('contact_messages', ['email' => 'ana@example.com']);
        Mail::assertSent(ContactReceived::class);
    }

    public function test_rechaza_sin_consentimiento(): void
    {
        $response = $this->postJson('/contacto', [
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'message' => 'Un mensaje suficientemente largo.',
            'consent' => false,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('consent');
        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_rechaza_si_el_honeypot_viene_lleno(): void
    {
        $response = $this->postJson('/contacto', [
            'name' => 'Bot',
            'email' => 'bot@example.com',
            'message' => 'Mensaje de spam automático.',
            'consent' => true,
            'website' => 'http://spam.example',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('website');
        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_guarda_los_campos_personalizados_enviados(): void
    {
        Mail::fake();

        $response = $this->postJson('/contacto', [
            'name' => 'Ana López',
            'email' => 'ana@example.com',
            'message' => 'Me gustaría saber más sobre las sesiones online.',
            'consent' => true,
            'custom_fields' => [
                ['label' => '¿Cómo nos conociste?', 'value' => 'Instagram'],
            ],
        ]);

        $response->assertOk();
        $saved = ContactMessage::first();
        $this->assertSame('¿Cómo nos conociste?', $saved->custom_fields[0]['label']);
        $this->assertSame('Instagram', $saved->custom_fields[0]['value']);
    }

    public function test_rechaza_si_falta_un_campo_personalizado_obligatorio(): void
    {
        app(\App\Services\SiteSettingsService::class)->set('contact_form', [
            'fields' => [],
            'custom_fields' => [
                ['key' => 'abc', 'label' => '¿Cómo nos conociste?', 'required' => true],
            ],
        ]);

        $response = $this->postJson('/contacto', [
            'name' => 'Ana López',
            'email' => 'ana@example.com',
            'message' => 'Me gustaría saber más sobre las sesiones online.',
            'consent' => true,
            'custom_fields' => [
                ['label' => '¿Cómo nos conociste?', 'value' => ''],
            ],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('custom_fields');
        $this->assertDatabaseCount('contact_messages', 0);
    }
}
