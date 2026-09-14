<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TrustedDeviceService;
use App\Services\TwoFactorChallengeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    /**
     * Recupera el usuario "pendiente de 2FA" de esta sesión, o null si no hay
     * ninguno (p. ej. se accedió directo a la URL sin pasar por login).
     */
    private function pendingUser(Request $request): ?User
    {
        $userId = $request->session()->get('pending_2fa.user_id');

        return $userId ? User::find($userId) : null;
    }

    public function show(Request $request): View|RedirectResponse
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge', [
            'method' => $user->effectiveTwoFactorMethod(),
        ]);
    }

    public function verify(Request $request, TwoFactorChallengeService $twoFactor, TrustedDeviceService $trustedDevices): RedirectResponse
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('login');
        }

        $request->validate(['code' => ['required', 'string', 'size:6']]);

        $rateLimitKey = "2fa-code:{$user->id}";
        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return back()->withErrors([
                'code' => "Demasiados intentos. Vuelve a intentarlo en {$seconds} segundos.",
            ]);
        }

        $method = $user->effectiveTwoFactorMethod();

        if ($method === 'totp') {
            if (! $twoFactor->verifyTotp($user, $request->string('code'))) {
                RateLimiter::hit($rateLimitKey);

                return back()->withErrors(['code' => 'Código incorrecto.']);
            }
        } else {
            $result = $twoFactor->verifyCode($user, $request->string('code'));

            if ($result === 'expired') {
                RateLimiter::hit($rateLimitKey);

                return back()->withErrors([
                    'code' => 'Tu código había expirado. Pulsa "Reenviar código" para pedir uno nuevo.',
                ]);
            }

            if ($result !== 'ok') {
                RateLimiter::hit($rateLimitKey);

                return back()->withErrors(['code' => 'Código incorrecto.']);
            }
        }

        RateLimiter::clear($rateLimitKey);

        Auth::login($user, (bool) $request->session()->get('pending_2fa.remember', false));
        $request->session()->forget('pending_2fa');
        $request->session()->regenerate();
        $request->session()->put('last_activity_at', now());

        activity('auth')->causedBy($user)->log('login');

        $response = redirect()->intended(route($user->defaultRedirectRouteName(), absolute: false));

        if ($request->boolean('remember_device')) {
            $response->withCookie($trustedDevices->remember($user, $request));
        }

        return $response;
    }

    public function resend(Request $request, TwoFactorChallengeService $twoFactor): RedirectResponse
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('login');
        }

        $method = $user->effectiveTwoFactorMethod();
        if ($method === 'totp') {
            return back();
        }

        $rateLimitKey = "2fa-resend:{$user->id}";
        if (RateLimiter::tooManyAttempts($rateLimitKey, 1)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return back()->withErrors([
                'code' => "Espera {$seconds} segundos antes de pedir otro código.",
            ]);
        }

        RateLimiter::hit($rateLimitKey, 60);
        $twoFactor->sendCode($user, $method);

        return back()->with('status', 'Te enviamos un nuevo código.');
    }
}
