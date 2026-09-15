<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\WompiPaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Recibe la notificación de Wompi cuando una transacción de un Enlace de
 * Pago se resuelve (exitosa o fallida) — ver docs.wompi.sv/webhook. Es una
 * ruta pública (Wompi no manda cookies de sesión ni CSRF token), protegida
 * en su lugar verificando el header 'wompi_hash' contra un HMAC-SHA256 del
 * body con el API Secret configurado.
 */
class WompiWebhookController extends Controller
{
    public function __invoke(Request $request, WompiPaymentService $wompi): Response
    {
        $rawBody = $request->getContent();
        $signature = $request->header('wompi_hash');

        if (! $wompi->verifyWebhookSignature($rawBody, $signature)) {
            Log::warning('Webhook de Wompi con firma inválida, ignorado.', ['ip' => $request->ip()]);

            return response('firma inválida', 401);
        }

        $payload = $request->json()->all();
        $idEnlace = $payload['EnlacePago']['idEnlace'] ?? $payload['EnlacePago']['IdEnlace'] ?? null;
        $resultado = $payload['ResultadoTransaccion'] ?? null;

        if (! $idEnlace) {
            Log::warning('Webhook de Wompi sin idEnlace, ignorado.', ['payload' => $payload]);

            return response('ok', 200);
        }

        // payment_reference guarda el idEnlace generado al crear el enlace
        // de pago (ver AppointmentBookingController::redirectToWompiPaymentLink).
        $appointment = Appointment::where('payment_reference', $idEnlace)->first();

        if (! $appointment) {
            Log::warning("Webhook de Wompi para un enlace sin cita asociada: {$idEnlace}");

            return response('ok', 200);
        }

        // Idempotente: si Wompi reenvía el mismo evento, no vuelve a
        // notificar ni a sobreescribir un estado ya confirmado.
        if ($appointment->payment_status === 'confirmed') {
            return response('ok', 200);
        }

        if ($resultado === 'ExitosaAprobada') {
            $appointment->forceFill(['payment_status' => 'confirmed'])->save();
            Log::info("Pago confirmado vía Wompi para la cita #{$appointment->id}.");
        } else {
            $appointment->forceFill(['payment_status' => 'unpaid'])->save();
            Log::info("Pago con Wompi no exitoso ({$resultado}) para la cita #{$appointment->id}.");
        }

        return response('ok', 200);
    }
}
