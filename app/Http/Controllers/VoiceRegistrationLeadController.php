<?php

namespace App\Http\Controllers;

use App\Services\VapiCallService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Punto de entrada público del registro de paciente por llamada de voz: el
 * visitante solo deja su teléfono en el sitio (sin llenar ningún
 * formulario de registro) y el sistema le dispara una llamada saliente con
 * VAPI (ver VapiCallService::callForVoiceRegistration) — el propio
 * asistente recolecta nombre/correo/teléfono por voz y crea la cuenta (ver
 * Webhooks\VapiToolCallController). Exclusivo del rol público — no
 * requiere sesión, por eso vive en routes/web.php y no en routes/auth.php.
 */
class VoiceRegistrationLeadController extends Controller
{
    public function store(Request $request, VapiCallService $vapi): JsonResponse
    {
        if (! $vapi->isVoiceRegistrationUsable()) {
            return response()->json([
                'ok' => false,
                'message' => 'El registro por llamada no está disponible en este momento.',
            ], 503);
        }

        $data = $request->validate([
            'phone_number' => ['required', 'string', 'max:30'],
        ]);

        try {
            $vapi->callForVoiceRegistration($data['phone_number']);

            return response()->json([
                'ok' => true,
                'message' => 'Te estamos llamando ahora mismo — responde para crear tu cuenta por voz.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => 'No se pudo iniciar la llamada. Intenta de nuevo en unos minutos.',
            ], 502);
        }
    }
}
