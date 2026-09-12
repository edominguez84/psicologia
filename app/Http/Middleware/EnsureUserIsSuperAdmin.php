<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsSuperAdmin
{
    /**
     * Deja pasar solo a usuarios autenticados con role = 'super_admin'.
     * Se aplica después de 'auth' y 'admin' (que ya exigen sesión y acceso
     * general al panel), para secciones reservadas exclusivamente a la
     * super administradora, como el gestor de preguntas del chatbot.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Solo la super administradora puede acceder a esta sección.');
        }

        return $next($request);
    }
}
