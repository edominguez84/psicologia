<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Envío de SMS vía la API REST de Twilio — HTTP puro (Http::withBasicAuth),
 * mismo estilo que VapiCallService/WompiPaymentService; el proyecto no usa
 * el SDK oficial de Twilio en ningún lado.
 *
 * Usado hoy solo para el código de verificación de cuenta (ver
 * TwoFactorChallengeService::sendCode()) — no confundir con las llamadas de
 * voz de VAPI, que usan la misma cuenta/número de Twilio pero por otra vía
 * (VAPI llama directo a Twilio, este servicio nunca interviene ahí).
 */
class TwilioSmsService
{
    private const API_URL = 'https://api.twilio.com/2010-04-01/Accounts/%s/Messages.json';

    /**
     * $to ya debe venir en formato E.164 (ver App\Support\PhoneNumber::normalize()).
     * Lanza RuntimeException si Twilio rechaza el envío — el llamador decide
     * qué hacer con el fallo (ver TwoFactorChallengeService, que cae a email).
     */
    public function send(string $to, string $body): void
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from = config('services.twilio.from');

        if (! filled($sid) || ! filled($token) || ! filled($from)) {
            throw new RuntimeException('Twilio no tiene configuradas todas las credenciales necesarias (TWILIO_SID/TWILIO_TOKEN/TWILIO_FROM).');
        }

        $response = Http::withBasicAuth($sid, $token)
            ->asForm()
            ->post(sprintf(self::API_URL, $sid), [
                'To' => $to,
                'From' => $from,
                'Body' => $body,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Twilio rechazó el envío del SMS: '.$response->body());
        }
    }
}
