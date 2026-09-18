<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chatbot_conversations', function (Blueprint $table) {
            // Cuándo se envió el mensaje de despedida por inactividad (ver
            // App\Console\Commands\CloseInactiveChatbotConversations) — no se
            // reusa updated_at porque ese campo cambia por cualquier guardado
            // (incluido el propio cierre), y necesitamos distinguir "todavía
            // no se despidió" de "ya se despidió, no repetir".
            $table->timestamp('closed_at')->nullable()->after('faq_capture_data');
        });
    }

    public function down(): void
    {
        Schema::table('chatbot_conversations', function (Blueprint $table) {
            $table->dropColumn('closed_at');
        });
    }
};
