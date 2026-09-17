<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chatbot_conversations', function (Blueprint $table) {
            // Evita que la IA vuelva a pedir nombre/correo/teléfono en cada
            // mensaje una vez que ya los capturó y guardó como
            // App\Models\ChatbotLead en esta misma conversación.
            $table->boolean('lead_captured')->default(false)->after('history');
        });
    }

    public function down(): void
    {
        Schema::table('chatbot_conversations', function (Blueprint $table) {
            $table->dropColumn('lead_captured');
        });
    }
};
