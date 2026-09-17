<?php

namespace Tests\Unit;

use App\Models\ChatbotConversation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatbotConversationTest extends TestCase
{
    use RefreshDatabase;

    public function test_incrementa_el_contador_del_dia_actual(): void
    {
        $conversation = ChatbotConversation::create(['channel' => 'telegram', 'external_chat_id' => '1']);

        $conversation->incrementAiMessageCount();
        $conversation->incrementAiMessageCount();

        $this->assertSame(2, $conversation->fresh()->ai_message_count);
        $this->assertTrue($conversation->fresh()->ai_message_count_date->isToday());
    }

    public function test_reinicia_el_contador_si_el_dia_guardado_no_es_hoy(): void
    {
        $conversation = ChatbotConversation::create([
            'channel' => 'telegram', 'external_chat_id' => '1',
            'ai_message_count' => 59, 'ai_message_count_date' => now()->subDay()->toDateString(),
        ]);

        $conversation->incrementAiMessageCount();

        $this->assertSame(1, $conversation->fresh()->ai_message_count);
    }

    public function test_alcanza_el_limite_diario(): void
    {
        $conversation = ChatbotConversation::create([
            'channel' => 'telegram', 'external_chat_id' => '1',
            'ai_message_count' => 5, 'ai_message_count_date' => now()->toDateString(),
        ]);

        $this->assertTrue($conversation->hasReachedDailyAiLimit(5));
        $this->assertFalse($conversation->hasReachedDailyAiLimit(6));
    }

    public function test_no_alcanza_el_limite_si_el_contador_es_de_otro_dia(): void
    {
        $conversation = ChatbotConversation::create([
            'channel' => 'telegram', 'external_chat_id' => '1',
            'ai_message_count' => 999, 'ai_message_count_date' => now()->subDay()->toDateString(),
        ]);

        $this->assertFalse($conversation->hasReachedDailyAiLimit(5));
    }

    public function test_una_conversacion_nueva_sin_fecha_no_alcanza_el_limite(): void
    {
        $conversation = ChatbotConversation::create(['channel' => 'telegram', 'external_chat_id' => '1']);

        $this->assertFalse($conversation->hasReachedDailyAiLimit(1));
    }
}
