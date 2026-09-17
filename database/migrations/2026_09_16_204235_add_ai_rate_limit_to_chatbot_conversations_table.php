<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chatbot_conversations', function (Blueprint $table) {
            // Cuenta mensajes de IA (no de FAQ, que no cuesta nada) por
            // conversación y por día calendario — protege el gasto de la
            // API de Anthropic ante abuso (alguien mandando cientos de
            // mensajes seguidos desde un mismo chat/sesión). Se resetea
            // solo comparando ai_message_count_date contra hoy, sin cron.
            $table->unsignedSmallInteger('ai_message_count')->default(0)->after('lead_captured');
            $table->date('ai_message_count_date')->nullable()->after('ai_message_count');
        });
    }

    public function down(): void
    {
        Schema::table('chatbot_conversations', function (Blueprint $table) {
            $table->dropColumn(['ai_message_count', 'ai_message_count_date']);
        });
    }
};
