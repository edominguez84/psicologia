<?php

namespace App\Models;

use App\Support\AdminPermissions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Rol asignable a un usuario — reemplaza el enum fijo App\Enums\UserRole
 * (conservado solo como referencia de los 5 slugs "de sistema": super_admin,
 * admin, editor, patient, user) por filas reales en base de datos, para que
 * el super_admin pueda crear roles nuevos de staff con su propio nombre y
 * permisos, además de ajustar los ya existentes.
 */
class Role extends Model
{
    use HasFactory;

    protected $fillable = ['slug', 'name', 'is_system', 'is_staff', 'permissions'];

    protected $casts = [
        'is_system' => 'boolean',
        'is_staff' => 'boolean',
        'permissions' => 'array',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role', 'slug');
    }

    /**
     * super_admin no guarda permissions (siempre ve todo, sin excepción) —
     * cualquier otro rol de staff sin fila de permisos guardada aún se
     * comporta como "todo habilitado", igual que antes de que este sistema
     * existiera.
     */
    public function permissionsOrDefault(): array
    {
        return $this->permissions ?? array_fill_keys(array_keys(AdminPermissions::all()), true);
    }

    public function hasFeature(string $feature): bool
    {
        return $this->permissionsOrDefault()[$feature] ?? true;
    }

    public function scopeStaff($query)
    {
        return $query->where('is_staff', true);
    }
}
