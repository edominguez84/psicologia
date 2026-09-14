<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SiteSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;

class UsersController extends Controller
{
    public function __construct(private SiteSettingsService $settings)
    {
    }

    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::orderBy('name')->get(),
        ]);
    }

    public function ban(Request $request, User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return back()->with('status', 'No puedes banearte a ti misma.');
        }

        if ($this->wouldRemoveLastSuperAdmin($user)) {
            return back()->with('status', 'No puedes banear al único super administrador activo.');
        }

        if ($this->hasActiveSession($user)) {
            return back()->with('status', "No puedes suspender a {$user->name} mientras tiene una sesión activa. Espera a que se desconecte o a que su sesión expire por inactividad.");
        }

        $user->update([
            'banned_at' => now(),
            'banned_reason' => $request->input('reason'),
        ]);

        // Cierra de inmediato cualquier sesión activa de este usuario, sin
        // esperar a que el middleware la detecte en su siguiente petición.
        DB::table('sessions')->where('user_id', $user->id)->delete();

        return back()->with('status', "Se suspendió la cuenta de {$user->name}.");
    }

    public function unban(User $user): RedirectResponse
    {
        $user->update(['banned_at' => null, 'banned_reason' => null]);

        return back()->with('status', "Se reactivó la cuenta de {$user->name}.");
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $request->validate(['role' => ['required', new Enum(UserRole::class)]]);

        $newRole = UserRole::from($request->string('role')->toString());

        if ($user->id === Auth::id() && $newRole !== UserRole::SuperAdmin && $user->isSuperAdmin()) {
            return back()->with('status', 'No puedes quitarte tu propio rol de super administradora.');
        }

        if ($user->isSuperAdmin() && $newRole !== UserRole::SuperAdmin && $this->wouldRemoveLastSuperAdmin($user)) {
            return back()->with('status', 'No puedes degradar al único super administrador activo.');
        }

        $user->update(['role' => $newRole]);

        return back()->with('status', "Rol de {$user->name} actualizado a {$newRole->label()}.");
    }

    /**
     * ¿Tiene $user una sesión con actividad dentro del tiempo de inactividad
     * configurado ahora mismo? Mismo criterio que
     * App\Http\Middleware\EnsureSessionIsActive usa para cerrar sesiones
     * inactivas, pero consultado desde fuera (aquí se evalúa a otro
     * usuario, no al de la request actual) — se usa la columna
     * 'last_activity' nativa de la tabla 'sessions' del driver de sesión en
     * base de datos, que Laravel actualiza automáticamente en cada request
     * de esa sesión, en vez del payload interno de sesión de ese usuario.
     */
    private function hasActiveSession(User $user): bool
    {
        $timeoutMinutes = (int) ($this->settings->get('security')['inactivity_timeout_minutes'] ?? 30);
        $cutoff = now()->subMinutes($timeoutMinutes)->getTimestamp();

        return DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('last_activity', '>=', $cutoff)
            ->exists();
    }

    /**
     * ¿Banear/degradar a $user dejaría el sitio sin ningún super_admin activo?
     */
    private function wouldRemoveLastSuperAdmin(User $user): bool
    {
        if (! $user->isSuperAdmin()) {
            return false;
        }

        $activeSuperAdmins = User::where('role', UserRole::SuperAdmin)
            ->whereNull('banned_at')
            ->count();

        return $activeSuperAdmins <= 1;
    }
}
