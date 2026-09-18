<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * La campana de notificaciones del panel de administración (ver
 * partials/notifications-bell.blade.php) — no se registra en
 * App\Support\AdminPermissions, así que cualquier admin autenticado puede
 * consultar su propio conteo/lista (EnsureAdminHasFeaturePermission no
 * encuentra ninguna feature asociada a estas rutas y las deja pasar); el
 * filtrado de qué categorías ve cada admin ocurre aquí dentro, vía
 * AdminNotification::scopeVisibleTo(), con el mismo criterio de permisos
 * por feature que ya usa el nav para mostrar/ocultar secciones.
 */
class NotificationController extends Controller
{
    public function unreadCount(Request $request): JsonResponse
    {
        $count = AdminNotification::visibleTo($request->user())->unread()->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Últimas notificaciones visibles para este admin, no leídas primero —
     * usado para poblar el dropdown al abrir la campana.
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = AdminNotification::visibleTo($request->user())
            ->orderByRaw('read_at IS NOT NULL')
            ->latest()
            ->limit(20)
            ->get(['id', 'title', 'body', 'link', 'read_at', 'created_at']);

        return response()->json(['notifications' => $notifications]);
    }

    public function markRead(Request $request, AdminNotification $notification): RedirectResponse|JsonResponse
    {
        // visibleTo() aquí evita que un admin marque como leída (o navegue
        // a) una notificación de una categoría que no tiene permitida.
        $visible = AdminNotification::visibleTo($request->user())->whereKey($notification->id)->exists();

        if (! $visible) {
            abort(404);
        }

        if (! $notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect($notification->link ?? route('admin.dashboard'));
    }

    public function markAllRead(Request $request): JsonResponse
    {
        AdminNotification::visibleTo($request->user())->unread()->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
