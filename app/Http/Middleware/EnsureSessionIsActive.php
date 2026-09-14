<?php

namespace App\Http\Middleware;

use App\Services\SiteSettingsService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defensa en profundidad para el cierre de sesión por inactividad: el aviso
 * con cuenta regresiva (ver resources/js/inactivity.js) es el mecanismo
 * principal, pero corre en el navegador — alguien con JavaScript
 * deshabilitado, o una pestaña dormida que nunca llega a ejecutar el timer,
 * no debe poder mantener una sesión autenticada abierta indefinidamente. Este
 * middleware cierra la sesión en el servidor si pasó más tiempo del
 * configurado desde la última actividad registrada.
 *
 * "Actividad" aquí es cualquier request autenticado a una ruta protegida
 * (incluyendo el ping que dispara el modal al pulsar "Seguir conectado") —
 * no hace falta trackear cada movimiento de mouse en el servidor, eso ya lo
 * hace el cliente antes de decidir cuándo mandar el ping.
 */
class EnsureSessionIsActive
{
    public function __construct(private SiteSettingsService $settings)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $timeoutMinutes = (int) ($this->settings->get('security')['inactivity_timeout_minutes'] ?? 30);
        $lastActivity = $request->session()->get('last_activity_at');

        if ($lastActivity && now()->diffInMinutes($lastActivity, absolute: true) >= $timeoutMinutes) {
            activity('auth')->causedBy(Auth::user())->log('logout_by_inactivity');

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('status', 'Tu sesión se cerró por inactividad. Vuelve a iniciar sesión.');
        }

        $request->session()->put('last_activity_at', now());

        return $next($request);
    }
}
