<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Support\AdminPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Gestión completa de roles de staff (crear, editar permisos, eliminar) —
 * reemplaza el antiguo RolePermissionsController, que solo ajustaba
 * permisos para los dos roles fijos 'admin'/'editor'. Ahora el super_admin
 * puede además dar de alta roles nuevos con su propio nombre (p. ej.
 * "Recepcionista"), cada uno con su propio subconjunto de las mismas 15
 * secciones del panel del catálogo AdminPermissions.
 *
 * Los roles marcados is_system=true (super_admin, admin, editor, patient,
 * user — los 5 que existían como enum fijo antes de esta migración) no se
 * pueden eliminar ni renombrar su slug, porque el código todavía asume esos
 * valores en puntos concretos (isSuperAdmin(), el registro público siempre
 * crea 'patient', etc.). super_admin tampoco tiene permisos editables aquí:
 * siempre ve todo el panel, sin excepción.
 */
class RoleManagementController extends Controller
{
    public function index(): View
    {
        return view('admin.roles.index', [
            'roles' => Role::withCount('users')->orderByDesc('is_system')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.roles.create', [
            'features' => AdminPermissions::all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $slug = $this->uniqueSlug($data['name']);
        $featureKeys = array_keys(AdminPermissions::all());
        $submitted = (array) $request->input('features', []);

        Role::create([
            'slug' => $slug,
            'name' => $data['name'],
            'is_system' => false,
            'is_staff' => true,
            'permissions' => collect($featureKeys)
                ->mapWithKeys(fn ($key) => [$key => in_array($key, $submitted, true)])
                ->all(),
        ]);

        return redirect()->route('admin.roles.index')->with('status', "Rol \"{$data['name']}\" creado.");
    }

    public function edit(Role $role): View
    {
        return view('admin.roles.edit', [
            'role' => $role,
            'features' => AdminPermissions::all(),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $featureKeys = array_keys(AdminPermissions::all());
        $submitted = (array) $request->input('features', []);

        $role->update([
            'name' => $data['name'],
            'permissions' => $role->slug === 'super_admin' ? null : collect($featureKeys)
                ->mapWithKeys(fn ($key) => [$key => in_array($key, $submitted, true)])
                ->all(),
        ]);

        return back()->with('status', 'Rol actualizado.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->is_system) {
            return back()->with('status', 'No puedes eliminar un rol del sistema.');
        }

        if (User::where('role', $role->slug)->exists()) {
            return back()->with('status', 'No puedes eliminar un rol que todavía tiene cuentas asignadas. Cambia esas cuentas a otro rol primero.');
        }

        $role->delete();

        return redirect()->route('admin.roles.index')->with('status', 'Rol eliminado.');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name, '_') ?: 'rol';
        $slug = $base;
        $suffix = 1;

        while (Role::where('slug', $slug)->exists()) {
            $slug = "{$base}_{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
