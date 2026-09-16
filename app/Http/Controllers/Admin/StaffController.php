<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStaffRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class StaffController extends Controller
{
    /**
     * Alta manual de cuentas por la super administradora — a diferencia de
     * UsersController (que gestiona transiciones sobre usuarios ya
     * existentes: banear, cambiar rol), este controlador crea cuentas desde
     * cero, con contraseña inicial. Reservado a 'super_admin' en las rutas.
     *
     * El <select> de rol ofrece los de staff (is_staff=true, incluidos los
     * que el super_admin cree desde /admin/roles) más 'patient' explícito —
     * el texto de la página ya prometía "administración, edición o
     * paciente" antes de que este catálogo fuera dinámico, así que se
     * conserva ese alcance en vez de reducirlo solo a staff.
     */
    public function create(): View
    {
        $roles = Role::query()
            ->where(fn ($q) => $q->where('is_staff', true)->orWhere('slug', 'patient'))
            ->orderByDesc('is_staff')
            ->orderBy('name')
            ->get();

        return view('admin.staff.create', [
            'roles' => $roles,
        ]);
    }

    public function store(StoreStaffRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = new User([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
        ]);
        // email_verified_at no es mass-assignable; se setea explícitamente
        // porque esta cuenta la da de alta la propia super administradora
        // (cuenta de confianza inmediata, sin necesidad de verificar correo).
        $user->email_verified_at = now();
        $user->save();

        $roleName = Role::where('slug', $data['role'])->value('name') ?? $data['role'];

        return redirect()->route('admin.users.index')
            ->with('status', "Cuenta {$roleName} creada: {$user->email}");
    }
}
