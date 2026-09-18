<?php

namespace Tests\Feature\Admin;

use App\Models\AdminNotification;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_no_puede_consultar_el_conteo(): void
    {
        $this->getJson('/admin/notifications/unread-count')->assertStatus(401);
    }

    public function test_super_admin_ve_el_conteo_de_todas_las_notificaciones(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        AdminNotification::factory()->count(3)->create(['read_at' => null]);
        AdminNotification::factory()->create(['read_at' => now()]);

        $response = $this->actingAs($superAdmin)->getJson('/admin/notifications/unread-count');

        $response->assertOk();
        $response->assertJson(['count' => 3]);
    }

    public function test_un_admin_normal_no_es_bloqueado_por_el_middleware_de_features(): void
    {
        // Las rutas de notificaciones no se registran en AdminPermissions,
        // así que cualquier admin autenticado debe poder consultarlas,
        // independientemente de sus features habilitadas.
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->getJson('/admin/notifications/unread-count')->assertOk();
    }

    public function test_el_conteo_de_un_admin_delegado_excluye_categorias_sin_permiso(): void
    {
        $adminRole = Role::where('slug', 'admin')->first();
        $adminRole->update(['permissions' => ['testimonials' => true]]);
        $admin = User::factory()->create(['role' => 'admin']);

        AdminNotification::factory()->create(['feature' => 'testimonials', 'read_at' => null]);
        AdminNotification::factory()->create(['feature' => 'payment-settings', 'read_at' => null]);

        $response = $this->actingAs($admin)->getJson('/admin/notifications/unread-count');

        $response->assertJson(['count' => 1]);
    }

    public function test_index_lista_las_notificaciones_no_leidas_primero(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $old = AdminNotification::factory()->create(['title' => 'leída', 'read_at' => now()->subDay()]);
        $new = AdminNotification::factory()->create(['title' => 'no leída', 'read_at' => null]);

        $response = $this->actingAs($superAdmin)->getJson('/admin/notifications');

        $response->assertOk();
        $titles = collect($response->json('notifications'))->pluck('title')->all();
        $this->assertSame(['no leída', 'leída'], $titles);
    }

    public function test_marcar_leida_actualiza_read_at_y_redirige_al_link(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $notification = AdminNotification::factory()->create(['link' => '/admin/appointments', 'read_at' => null]);

        $response = $this->actingAs($superAdmin)->post("/admin/notifications/{$notification->id}/read");

        $response->assertRedirect('/admin/appointments');
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_un_admin_delegado_no_puede_marcar_leida_una_notificacion_fuera_de_su_alcance(): void
    {
        $adminRole = Role::where('slug', 'admin')->first();
        $adminRole->update(['permissions' => ['testimonials' => true]]);
        $admin = User::factory()->create(['role' => 'admin']);
        $notification = AdminNotification::factory()->create(['feature' => 'payment-settings', 'read_at' => null]);

        $this->actingAs($admin)->post("/admin/notifications/{$notification->id}/read")->assertNotFound();
        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_marcar_todas_leidas_solo_afecta_las_visibles_para_ese_usuario(): void
    {
        $adminRole = Role::where('slug', 'admin')->first();
        $adminRole->update(['permissions' => ['testimonials' => true]]);
        $admin = User::factory()->create(['role' => 'admin']);

        $visible = AdminNotification::factory()->create(['feature' => 'testimonials', 'read_at' => null]);
        $fueraDeAlcance = AdminNotification::factory()->create(['feature' => 'payment-settings', 'read_at' => null]);

        $this->actingAs($admin)->postJson('/admin/notifications/mark-all-read')->assertOk();

        $this->assertNotNull($visible->fresh()->read_at);
        $this->assertNull($fueraDeAlcance->fresh()->read_at);
    }
}
