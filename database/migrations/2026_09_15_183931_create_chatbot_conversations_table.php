<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatbot_conversations', function (Blueprint $table) {
            $table->id();
            // 'telegram' hoy; 'whatsapp'/'facebook' cuando tengan integración
            // real — mismo external_chat_id sirve para identificar al
            // interlocutor en cualquier canal (chat_id de Telegram, número de
            // WhatsApp, PSID de Messenger).
            $table->string('channel');
            $table->string('external_chat_id');
            // Historial en el formato exacto que espera la API de mensajes de
            // Anthropic ([{role, content}, ...]) — así se manda directo sin
            // transformar en cada respuesta.
            $table->json('history')->nullable();
            $table->timestamps();

            $table->unique(['channel', 'external_chat_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_conversations');
    }
};
