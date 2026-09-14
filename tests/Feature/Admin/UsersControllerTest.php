<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsersControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_admin_no_puede_ver_la_lista(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get('/admin/users')->assertForbidden();
    }

    public function test_admin_puede_banear_a_otro_usuario(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $target = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->patch("/admin/users/{$target->id}/ban", ['reason' => 'Prueba']);

        $response->assertRedirect();
        $this->assertNotNull($target->fresh()->banned_at);
    }

    public function test_admin_puede_reactivar_a_un_usuario_baneado(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $target = User::factory()->create(['role' => 'admin', 'banned_at' => now()]);

        $this->actingAs($admin)->patch("/admin/users/{$target->id}/unban");

        $this->assertNull($target->fresh()->banned_at);
    }

    public function test_nadie_puede_banearse_a_si_misma(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)->patch("/admin/users/{$admin->id}/ban");

        $this->assertNull($admin->fresh()->banned_at);
    }

    public function test_no_se_puede_banear_al_ultimo_super_admin(): void
    {
        $onlySuperAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->patch("/admin/users/{$onlySuperAdmin->id}/ban");

        $this->assertNull($onlySuperAdmin->fresh()->banned_at);
    }

    public function test_no_se_puede_degradar_al_ultimo_super_admin(): void
    {
        $onlySuperAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->patch("/admin/users/{$onlySuperAdmin->id}/role", ['role' => 'editor']);

        $this->assertSame('super_admin', $onlySuperAdmin->fresh()->role->value);
    }

    public function test_se_puede_degradar_un_super_admin_si_hay_otro_activo(): void
    {
        $superAdmin1 = User::factory()->create(['role' => 'super_admin']);
        $superAdmin2 = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($superAdmin1)->patch("/admin/users/{$superAdmin2->id}/role", ['role' => 'admin']);

        $this->assertSame('admin', $superAdmin2->fresh()->role->value);
    }

    public function test_no_se_puede_banear_a_un_usuario_con_sesion_activa(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $target = User::factory()->create(['role' => 'patient']);
        \Illuminate\Support\Facades\DB::table('sessions')->insert([
            'id' => 'session-activa-de-prueba',
            'user_id' => $target->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => base64_encode(serialize([])),
            'last_activity' => now()->getTimestamp(),
        ]);

        $response = $this->actingAs($admin)->patch("/admin/users/{$target->id}/ban");

        $response->assertRedirect();
        $this->assertNull($target->fresh()->banned_at);
    }

    public function test_se_puede_banear_a_un_usuario_con_sesion_expirada_por_inactividad(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $target = User::factory()->create(['role' => 'patient']);
        \Illuminate\Support\Facades\DB::table('sessions')->insert([
            'id' => 'session-vieja-de-prueba',
            'user_id' => $target->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => base64_encode(serialize([])),
            'last_activity' => now()->subHours(2)->getTimestamp(),
        ]);

        $response = $this->actingAs($admin)->patch("/admin/users/{$target->id}/ban");

        $response->assertRedirect();
        $this->assertNotNull($target->fresh()->banned_at);
    }
}
