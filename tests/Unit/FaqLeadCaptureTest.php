<?php

namespace Tests\Unit;

use App\Models\ChatbotConversation;
use App\Models\ChatbotLead;
use App\Support\FaqLeadCapture;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqLeadCaptureTest extends TestCase
{
    use RefreshDatabase;

    public function test_start_pide_el_nombre_y_marca_el_primer_paso(): void
    {
        $conversation = ChatbotConversation::create(['channel' => 'telegram', 'external_chat_id' => '1']);

        $reply = FaqLeadCapture::start($conversation);

        $this->assertStringContainsString('nombre', $reply);
        $this->assertSame('name', $conversation->fresh()->faq_capture_step);
    }

    public function test_flujo_completo_captura_nombre_correo_y_telefono(): void
    {
        $conversation = ChatbotConversation::create(['channel' => 'telegram', 'external_chat_id' => '1', 'faq_capture_step' => 'name', 'faq_capture_data' => []]);

        $replyName = FaqLeadCapture::handle($conversation, 'Elian Domínguez');
        $this->assertStringContainsString('correo', $replyName);
        $this->assertSame('email', $conversation->fresh()->faq_capture_step);

        $replyEmail = FaqLeadCapture::handle($conversation->fresh(), 'elian@example.com');
        $this->assertStringContainsString('teléfono', $replyEmail);
        $this->assertSame('phone', $conversation->fresh()->faq_capture_step);

        $replyPhone = FaqLeadCapture::handle($conversation->fresh(), '77778888');
        $this->assertStringContainsString('Guardé', $replyPhone);

        $conversation->refresh();
        $this->assertNull($conversation->faq_capture_step);
        $this->assertTrue($conversation->lead_captured);

        $lead = ChatbotLead::first();
        $this->assertSame('Elian Domínguez', $lead->name);
        $this->assertSame('elian@example.com', $lead->email);
        $this->assertSame('77778888', $lead->phone);
    }

    public function test_rechaza_un_correo_invalido_y_vuelve_a_pedirlo(): void
    {
        $conversation = ChatbotConversation::create(['channel' => 'telegram', 'external_chat_id' => '1', 'faq_capture_step' => 'email', 'faq_capture_data' => ['name' => 'Elian']]);

        $reply = FaqLeadCapture::handle($conversation, 'no-es-un-correo');

        $this->assertStringContainsString('válido', $reply);
        $this->assertSame('email', $conversation->fresh()->faq_capture_step);
        $this->assertDatabaseCount('chatbot_leads', 0);
    }

    public function test_permite_omitir_el_telefono(): void
    {
        $conversation = ChatbotConversation::create([
            'channel' => 'telegram', 'external_chat_id' => '1', 'faq_capture_step' => 'phone',
            'faq_capture_data' => ['name' => 'Elian', 'email' => 'elian@example.com'],
        ]);

        FaqLeadCapture::handle($conversation, 'no');

        $lead = ChatbotLead::first();
        $this->assertNull($lead->phone);
    }

    public function test_is_active_or_done(): void
    {
        $notStarted = ChatbotConversation::create(['channel' => 'telegram', 'external_chat_id' => '1']);
        $this->assertFalse(FaqLeadCapture::isActiveOrDone($notStarted));

        $inProgress = ChatbotConversation::create(['channel' => 'telegram', 'external_chat_id' => '2', 'faq_capture_step' => 'name']);
        $this->assertTrue(FaqLeadCapture::isActiveOrDone($inProgress));

        $done = ChatbotConversation::create(['channel' => 'telegram', 'external_chat_id' => '3', 'lead_captured' => true]);
        $this->assertTrue(FaqLeadCapture::isActiveOrDone($done));
    }
}
