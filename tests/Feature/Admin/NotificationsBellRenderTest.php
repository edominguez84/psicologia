<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifica que la campana de notificaciones (partials/notifications-bell)
 * quede incluida en el layout del admin y visible para un usuario
 * autenticado — complementa los tests de comportamiento de
 * Admin\NotificationController (este solo cubre que el partial se
 * renderice sin errores dentro de una página real del panel).
 */
class NotificationsBellRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_campana_de_notificaciones_aparece_en_el_dashboard_del_admin(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($superAdmin)->get('/admin');

        $response->assertOk();
        $response->assertSee('Notificaciones', false);
        $response->assertSee(route('admin.notifications.unread-count'), false);
    }
}
