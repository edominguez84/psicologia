<?php

namespace App\Support;

/**
 * Normalización de teléfonos a formato E.164 (+50370000000) — extraído de
 * VapiCallService::normalizePhoneNumber() (que ya lo usaba de forma privada
 * solo para sí mismo) para poder reusarlo también en TwilioSmsService, sin
 * duplicar el mismo regex una tercera vez.
 */
class PhoneNumber
{
    /**
     * Los teléfonos guardados en el sitio pueden venir sin el símbolo '+' o
     * con espacios/guiones (el registro no fuerza un formato estricto) — se
     * normaliza lo mejor posible antes de mandarlo a un proveedor externo
     * (VAPI, Twilio); si ya trae un '+', se respeta tal cual.
     */
    public static function normalize(string $phone): string
    {
        $digitsOnly = preg_replace('/[^0-9+]/', '', $phone);

        if (str_starts_with($digitsOnly, '+')) {
            return $digitsOnly;
        }

        // Sin código de país explícito, se asume El Salvador (+503) — el
        // sitio opera principalmente ahí (ver ElSalvadorLocations).
        return '+503'.$digitsOnly;
    }
}
