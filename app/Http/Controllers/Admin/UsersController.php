<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;

class UsersController extends Controller
{
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
