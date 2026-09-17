<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chatbot_conversations', function (Blueprint $table) {
            // Estado del flujo de captura de datos en modo FAQ (sin IA) —
            // ver App\Support\FaqLeadCapture. null = todavía no se le pidió
            // nada; 'name'/'email'/'phone' = esperando ese dato como
            // siguiente mensaje; 'done' = ya se guardó como ChatbotLead
            // (igual criterio que lead_captured, pero específico de este
            // flujo por pasos en vez del guardado directo que hace la IA).
            $table->string('faq_capture_step')->nullable()->after('lead_captured');
            $table->json('faq_capture_data')->nullable()->after('faq_capture_step');
        });
    }

    public function down(): void
    {
        Schema::table('chatbot_conversations', function (Blueprint $table) {
            $table->dropColumn(['faq_capture_step', 'faq_capture_data']);
        });
    }
};
