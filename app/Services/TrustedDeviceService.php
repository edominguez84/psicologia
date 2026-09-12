<?php

namespace App\Services;

use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;

class TrustedDeviceService
{
    private const COOKIE_NAME = 'trusted_device';

    public function __construct(private SecurityAvailability $availability)
    {
    }

    /**
     * ¿El dispositivo que hace esta petición ya es de confianza para $user?
     * La cookie va cifrada/firmada por el middleware nativo de Laravel
     * (EncryptCookies), así que su valor nunca es legible ni falsificable
     * fuera de la app; aun así el token se compara siempre hasheado.
     */
    public function isTrusted(User $user, Request $request): bool
    {
        $cookieValue = $request->cookie(self::COOKIE_NAME);
        if (! $cookieValue || ! str_contains($cookieValue, ':')) {
            return false;
        }

        [$deviceId, $rawToken] = explode(':', $cookieValue, 2);

        $device = TrustedDevice::where('id', $deviceId)
            ->where('user_id', $user->id)
            ->first();

        if (! $device || $device->isExpired() || ! Hash::check($rawToken, $device->token_hash)) {
            return false;
        }

        $device->update(['last_used_at' => now()]);

        return true;
    }

    /**
     * Crea el registro de dispositivo de confianza y devuelve la cookie a
     * adjuntar en la respuesta (duración configurable desde /admin/security).
     */
    public function remember(User $user, Request $request): Cookie
    {
        $rawToken = Str::random(64);
        $days = $this->availability->trustedDeviceDays();

        $device = TrustedDevice::create([
            'user_id' => $user->id,
            'token_hash' => Hash::make($rawToken),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'ip_address' => $request->ip(),
            'last_used_at' => now(),
            'expires_at' => now()->addDays($days),
        ]);

        return cookie(
            self::COOKIE_NAME,
            "{$device->id}:{$rawToken}",
            $days * 24 * 60,
            httpOnly: true,
            sameSite: 'lax',
        );
    }

    /**
     * Invalida todos los dispositivos de confianza de un usuario — se llama
     * al cambiar la contraseña, para que un dispositivo comprometido no siga
     * saltándose el 2FA tras rotar la clave.
     */
    public function forgetAll(User $user): void
    {
        $user->trustedDevices()->delete();
    }
}
