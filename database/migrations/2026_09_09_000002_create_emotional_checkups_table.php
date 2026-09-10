<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emotional_checkups', function (Blueprint $table) {
            $table->id();
            // Cinco respuestas de 0 a 3 (nunca / a veces / a menudo / casi siempre)
            $table->unsignedTinyInteger('q1');
            $table->unsignedTinyInteger('q2');
            $table->unsignedTinyInteger('q3');
            $table->unsignedTinyInteger('q4');
            $table->unsignedTinyInteger('q5');
            $table->unsignedTinyInteger('score'); // suma 0-15
            $table->string('band'); // bajo | medio | alto
            $table->string('email')->nullable(); // opcional, si quiere recibir recursos
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emotional_checkups');
    }
};
