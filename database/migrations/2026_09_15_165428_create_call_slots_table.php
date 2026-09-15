<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_slots', function (Blueprint $table) {
            $table->id();
            // Mismo shape que appointment_slots (starts_at/ends_at/is_active)
            // a propósito, pero es un catálogo separado: la llamada gratis de
            // 15 min es un flujo distinto de las citas pagadas, sin cuenta ni
            // pago de por medio, así que no debe compartir cupo con ellas.
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('starts_at');
        });

        // El mensaje de contacto que reserva una llamada gratis referencia el
        // horario elegido, para poder marcarlo como tomado y para que el
        // admin vea qué horario acordó la paciente sin tener que leerlo del
        // texto libre del mensaje.
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->foreignId('call_slot_id')->nullable()->after('id')
                ->constrained('call_slots')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('call_slot_id');
        });

        Schema::dropIfExists('call_slots');
    }
};
