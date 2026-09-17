<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\VapiCallService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Recibe los eventos de servidor de VAPI (ver docs.vapi.ai/server-url) tras
 * una llamada de confirmación de cita. Ruta pública, sin CSRF (excepción en
 * bootstrap/app.php) — VAPI no manda cookies de sesión.
 *
 * Autenticidad: se compara el header 'X-Vapi-Secret' contra el secreto
 * configurado en /admin/vapi-settings (mismo criterio de secreto
 * compartido que TelegramWebhookController).
 *
 * Solo procesa el evento 'end-of-call-report' — es el único que interesa
 * para registrar el resultado; otros tipos (status-update, transcript
 * parcial, etc.) se ignoran con 200 sin hacer nada.
 *
 * Importante: esta llamada es un recordatorio de cortesía sobre una cita
 * YA aprobada por el super_admin — el resultado se guarda como información
 * (vapi_call_result), pero nunca cambia el status de la cita por sí solo.
 */
class VapiWebhookController extends Controller
{
    public function __invoke(Request $request, VapiCallService $vapi): Response
    {
        if (! $vapi->verifyWebhookSecret($request->header('X-Vapi-Secret'))) {
            Log::warning('Webhook de VAPI con secreto inválido, ignorado.', ['ip' => $request->ip()]);

            return response('secreto inválido', 401);
        }

        $type = $request->input('message.type');

        if ($type !== 'end-of-call-report') {
            return response('ok', 200);
        }

        $appointmentId = $request->input('message.call.metadata.appointment_id');
        $appointment = $appointmentId ? Appointment::find($appointmentId) : null;

        if (! $appointment) {
            Log::info('Webhook de VAPI end-of-call-report sin cita asociada, ignorado.', ['appointment_id' => $appointmentId]);

            return response('ok', 200);
        }

        $appointment->forceFill([
            'vapi_call_status' => 'completed',
            'vapi_call_result' => [
                'ended_reason' => $request->input('message.endedReason'),
                'summary' => $request->input('message.summary'),
                'transcript' => $request->input('message.artifact.transcript'),
            ],
        ])->save();

        return response('ok', 200);
    }
}
