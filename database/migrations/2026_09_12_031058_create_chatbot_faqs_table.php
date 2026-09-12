<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatbot_faqs', function (Blueprint $table) {
            $table->id();
            // Texto del botón que ve el usuario en el chat.
            $table->string('question');
            $table->text('answer');
            // Orden de aparición en el chat (menor primero).
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Preguntas de fábrica (las mismas que antes estaban fijas en el
        // widget del chatbot), para que el chat no quede vacío recién
        // instalado; el super_admin puede editarlas o quitarlas después.
        $now = now();
        DB::table('chatbot_faqs')->insert([
            [
                'question' => '¿Cómo funciona la terapia?',
                'answer' => 'Trabajamos por videollamada, en sesiones de 50 minutos, con un plan adaptado a lo que necesitas trabajar (ansiedad, trauma, EMDR y más). La primera llamada de 15 minutos es gratuita para conocernos.',
                'position' => 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'question' => '¿Cuánto dura una sesión?',
                'answer' => 'Cada sesión dura 50 minutos. La frecuencia habitual es semanal, aunque se ajusta según tu proceso.',
                'position' => 2,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'question' => '¿Atienden en mi país?',
                'answer' => 'Sí, atiendo por videollamada a personas en Estados Unidos, Europa y Latinoamérica. Solo necesitas conexión a internet.',
                'position' => 3,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_faqs');
    }
};
