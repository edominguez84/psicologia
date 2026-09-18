<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            // Slug del tipo de evento (ej. 'appointment_pending',
            // 'payment_reported', 'vapi_call_failed') — informativo, no se
            // usa para lógica de negocio todavía.
            $table->string('type');
            $table->string('title');
            $table->string('body')->nullable();
            // URL ya resuelta (route(...)) a donde navega el admin al hacer
            // clic — null si la notificación no lleva a ningún lado.
            $table->string('link')->nullable();
            // Key de App\Support\AdminPermissions::all() que controla si un
            // admin delegado (no super_admin) ve esta notificación — null
            // significa "visible para cualquier admin sin importar sus
            // features" (usado en avisos informativos generales).
            $table->string('feature')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index('feature');
            $table->index('read_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notifications');
    }
};
