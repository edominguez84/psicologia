<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Mail\AccountCreatedByVoiceCall;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\VapiCallService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Recibe las tool-calls que el asistente de "Registro de paciente por voz"
 * invoca en vivo durante la llamada (ver docs.vapi.ai/tools/custom-tools) —
 * distinto de Webhooks\VapiWebhookController, que solo procesa el reporte
 * de fin de llamada de confirmación de citas. Ruta pública, sin CSRF
 * (excepción en bootstrap/app.php) — VAPI no manda cookies de sesión.
 *
 * Formato de VAPI para tool-calls: recibe
 * {"message":{"type":"tool-calls","toolCallList":[{"id":"...",
 * "function":{"name":"...","arguments":{...}}}]}} y espera de vuelta
 * {"results":[{"toolCallId":"...","result":"texto que el asistente lee
 * como resultado de la función"}]} — el 'result' es lo que el modelo usa
 * para decidir qué decirle al paciente a continuación, no se muestra tal
 * cual.
 *
 * Autenticidad: header 'X-Vapi-Secret' contra un secreto propio de esta
 * sección (VapiCallService::verifyVoiceRegistrationWebhookSecret) —
 * separado del secreto de 'vapi' porque es un asistente y un endpoint
 * distintos.
 */
class VapiToolCallController extends Controller
{
    public function __invoke(Request $request, VapiCallService $vapi, NotificationService $notifications): JsonResponse
    {
        if (! $vapi->verifyVoiceRegistrationWebhookSecret($request->header('X-Vapi-Secret'))) {
            Log::warning('Tool-call de VAPI (registro por voz) con secreto inválido, ignorada.', ['ip' => $request->ip()]);

            return response()->json([], 401);
        }

        $toolCalls = $request->input('message.toolCallList', []);
        $results = [];

        foreach ($toolCalls as $toolCall) {
            $toolCallId = $toolCall['id'] ?? null;
            $functionName = $toolCall['function']['name'] ?? null;
            $arguments = $toolCall['function']['arguments'] ?? [];

            $results[] = [
                'toolCallId' => $toolCallId,
                'result' => match ($functionName) {
                    'create_patient_account' => $this->createPatientAccount($arguments, $notifications),
                    default => $this->unknownTool($functionName),
                },
            ];
        }

        return response()->json(['results' => $results]);
    }

    /**
     * Crea la cuenta de paciente con los datos que el asistente recolectó y
     * confirmó por voz (nombre, correo ya deletreado/confirmado, teléfono).
     * Si ya existe una cuenta con ese correo o teléfono, no crea duplicado —
     * el 'result' se lo indica al asistente para que se lo diga al paciente
     * en la llamada.
     */
    private function createPatientAccount(array $arguments, NotificationService $notifications): string
    {
        $name = trim((string) ($arguments['name'] ?? ''));
        $email = trim((string) ($arguments['email'] ?? ''));
        $phoneNumber = trim((string) ($arguments['phone_number'] ?? ''));

        $missing = array_keys(array_filter([
            'nombre' => $name === '',
            'correo' => ! filter_var($email, FILTER_VALIDATE_EMAIL),
            'teléfono' => $phoneNumber === '',
        ]));

        if (! empty($missing)) {
            return 'Faltan o son inválidos estos datos: '.implode(', ', $missing).'. Pídelos de nuevo con naturalidad antes de volver a intentar — no llames de nuevo a esta función hasta tenerlos todos.';
        }

        $existing = User::where('email', $email)->orWhere('phone_number', $phoneNumber)->first();

        if ($existing) {
            return 'Ya existe una cuenta con ese correo o teléfono. No crees una cuenta nueva — infórmale al paciente que ya tiene una cuenta y que puede iniciar sesión en el sitio con ese correo (si olvidó su contraseña, puede recuperarla desde la página de inicio de sesión).';
        }

        $temporaryPassword = Str::password(14);

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'phone_number' => $phoneNumber,
            'password' => Hash::make($temporaryPassword),
            'role' => 'patient',
        ]);

        try {
            Mail::to($user->email)->send(new AccountCreatedByVoiceCall($user, $temporaryPassword));
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar el correo de cuenta creada por registro de voz: '.$e->getMessage());
        }

        $notifications->notify(
            type: 'voice_account_created',
            title: 'Cuenta creada por registro de voz',
            body: $user->name,
            link: route('admin.users.index'),
        );

        return 'Cuenta creada correctamente. Le llegará un correo con su contraseña temporal para entrar al sitio, donde podrá elegir el horario de su cita y el método de pago. Confírmaselo al paciente y despídete.';
    }

    private function unknownTool(?string $functionName): string
    {
        Log::warning('Tool-call de VAPI (registro por voz) con función desconocida.', ['function' => $functionName]);

        return 'Ocurrió un error interno al procesar esto. Continúa la conversación con normalidad.';
    }
}
