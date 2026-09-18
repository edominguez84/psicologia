<?php

namespace App\Services;

use App\Models\AdminNotification;

/**
 * Punto único donde se crean las notificaciones de la campana del panel de
 * administración — invocado directamente desde los controladores/services
 * donde cada evento ya ocurre (el proyecto no usa el sistema de eventos de
 * Laravel en ningún otro lado, se mantiene el mismo estilo aquí en vez de
 * introducir Events/Listeners nuevos).
 */
class NotificationService
{
    public function notify(string $type, string $title, ?string $body = null, ?string $link = null, ?string $feature = null): AdminNotification
    {
        return AdminNotification::create([
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'link' => $link,
            'feature' => $feature,
        ]);
    }
}
