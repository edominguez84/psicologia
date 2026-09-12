<?php

namespace App\Services;

use App\Mail\LoginCode as LoginCodeMail;
use App\Models\LoginCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorChallengeService
{
    public function __construct(private SecurityAvailability $availability)
    {
    }

    /**
     * Emite el reto adecuado según el método efectivo del usuario. Para
     * email/sms/whatsapp envía un código; para totp no hace nada (se verifica
     * en vivo con la app autenticadora, no hay nada que enviar).
     */
    public function issueChallenge(User $user): void
    {
        $method = $user->effectiveTwoFactorMethod();

        if ($method === 'totp') {
            return;
        }

        $this->sendCode($user, $method);
    }

    /**
     * Genera y envía un código nuevo, invalidando cualquiera previo sin usar.
     * sms/whatsapp caen a email mientras no haya credenciales de Twilio
     * configuradas — así nadie queda bloqueado por elegir un canal que aún no
     * está implementado de verdad.
     */
    public function sendCode(User $user, string $channel): void
    {
        $effectiveChannel = $channel;

        if (in_array($channel, ['sms', 'whatsapp'], true) && ! $this->availability->hasTwilioCredentials()) {
            Log::warning("2FA: canal '{$channel}' solicitado para el usuario {$user->id} sin credenciales de Twilio configuradas; se envía por email como respaldo.");
            $effectiveChannel = 'email';
        }

        // Invalida cualquier código previo no consumido (histórico auditable,
        // no se borra la fila).
        $user->loginCodes()->whereNull('consumed_at')->update(['consumed_at' => now()]);

        $code = (string) random_int(100000, 999999);
        $expiresAt = now()->addHours(2);

        LoginCode::create([
            'user_id' => $user->id,
            'code_hash' => Hash::make($code),
            'channel' => $effectiveChannel,
            'expires_at' => $expiresAt,
            'created_at' => now(),
        ]);

        // TODO: cuando haya credenciales Twilio configuradas, enviar por
        // sms/whatsapp real en vez de caer siempre a email aquí.
        Mail::to($user)->send(new LoginCodeMail($code, $expiresAt));
    }

    /**
     * Resultado de verificar un código: 'ok', 'expired' (ya se reenvió uno
     * nuevo automáticamente) o 'invalid'.
     */
    public function verifyCode(User $user, string $submitted): string
    {
        $loginCode = $user->loginCodes()
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if (! $loginCode) {
            return 'invalid';
        }

        if ($loginCode->isExpired()) {
            $loginCode->update(['consumed_at' => now()]);
            $this->sendCode($user, $loginCode->channel);

            return 'expired';
        }

        if (! Hash::check($submitted, $loginCode->code_hash)) {
            return 'invalid';
        }

        $loginCode->update(['consumed_at' => now()]);

        return 'ok';
    }

    public function verifyTotp(User $user, string $submitted): bool
    {
        if (! $user->two_factor_secret) {
            return false;
        }

        return (bool) (new Google2FA())->verifyKey($user->two_factor_secret, $submitted);
    }
}
