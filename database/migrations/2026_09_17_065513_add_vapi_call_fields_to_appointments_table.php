<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Estado de la llamada automática de confirmación (VAPI) —
            // ver App\Services\VapiCallService y
            // App\Console\Commands\SendAppointmentCallReminders.
            // null = todavía no le corresponde (fuera de la ventana
            // configurada, o la integración está apagada).
            $table->string('vapi_call_status')->nullable()->after('payment_reference');
            $table->string('vapi_call_id')->nullable()->after('vapi_call_status');
            $table->timestamp('vapi_called_at')->nullable()->after('vapi_call_id');
            // Resultado que reporta App\Http\Controllers\Webhooks\VapiWebhookController
            // al terminar la llamada: si el paciente confirmó, transcripción
            // resumida, razón de fin de llamada, etc. Es informativo — la
            // llamada nunca cambia el status de la cita por sí sola (la
            // aprobación humana ya ocurrió antes de que se dispare).
            $table->json('vapi_call_result')->nullable()->after('vapi_called_at');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['vapi_call_status', 'vapi_call_id', 'vapi_called_at', 'vapi_call_result']);
        });
    }
};
