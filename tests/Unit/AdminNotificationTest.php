<?php

namespace Tests\Unit;

use App\Models\AdminNotification;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_scope_unread_solo_devuelve_las_no_leidas(): void
    {
        AdminNotification::factory()->create(['read_at' => null]);
        AdminNotification::factory()->create(['read_at' => now()]);

        $this->assertSame(1, AdminNotification::unread()->count());
    }

    public function test_super_admin_ve_todas_las_notificaciones_sin_importar_su_feature(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        AdminNotification::factory()->create(['feature' => 'testimonials']);
        AdminNotification::factory()->create(['feature' => null]);

        $this->assertSame(2, AdminNotification::visibleTo($superAdmin)->count());
    }

    public function test_admin_delegado_solo_ve_las_notificaciones_de_features_que_tiene_habilitadas(): void
    {
        $adminRole = Role::where('slug', 'admin')->first();
        $adminRole->update(['permissions' => ['testimonials' => true]]);
        $admin = User::factory()->create(['role' => 'admin']);

        AdminNotification::factory()->create(['feature' => 'testimonials', 'title' => 'visible']);
        AdminNotification::factory()->create(['feature' => 'payment-settings', 'title' => 'oculta']);
        AdminNotification::factory()->create(['feature' => null, 'title' => 'general']);

        $visibleTitles = AdminNotification::visibleTo($admin)->pluck('title')->all();

        $this->assertContains('visible', $visibleTitles);
        $this->assertContains('general', $visibleTitles);
        $this->assertNotContains('oculta', $visibleTitles);
    }
}
