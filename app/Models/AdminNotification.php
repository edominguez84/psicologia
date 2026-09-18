<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Notificación persistente del panel de administración (la campana del
 * layout admin) — ver App\Services\NotificationService para cómo se crean,
 * y Admin\NotificationController para cómo se listan/marcan leídas.
 */
class AdminNotification extends Model
{
    use HasFactory;

    protected $fillable = ['type', 'title', 'body', 'link', 'feature', 'read_at'];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Filtra las notificaciones que le corresponde ver a $user: super_admin
     * ve todas, sin excepción (mismo criterio que el resto del panel). Un
     * admin delegado solo ve las que no tienen 'feature' (avisos generales)
     * o cuya feature tiene habilitada en su rol — mismo cálculo que ya usa
     * el nav para mostrar/ocultar secciones (Role::permissionsOrDefault()).
     */
    public function scopeVisibleTo($query, User $user)
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        $role = Role::where('slug', $user->role)->first();

        // Sin fila de rol guardada, el resto del panel se comporta como
        // "todo habilitado" (ver EnsureAdminHasFeaturePermission::isEnabled())
        // — mismo criterio aquí, no se oculta nada.
        if (! $role) {
            return $query;
        }

        $enabledFeatures = array_keys(array_filter($role->permissionsOrDefault()));

        return $query->where(function ($q) use ($enabledFeatures) {
            $q->whereNull('feature')->orWhereIn('feature', $enabledFeatures);
        });
    }
}
