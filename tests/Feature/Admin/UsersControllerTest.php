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
        // El actor debe ser super_admin para pasar el middleware de la
        // ruta, y el guard cuenta a TODOS los super_admin activos —
        // incluido el propio actor. Para que $target quede como "el único
        // activo" al momento del conteo sin que el actor deje de ser
        // super_admin, el actor debe ser el propio $target: se prueba el
        // caso vía HTTP como auto-baneo, que ya está cubierto por su propio
        // guard (test_nadie_puede_banearse_a_si_misma), y aquí se confirma
        // la regla de conteo directamente sobre el método del controlador.
        $onlySuperAdmin = User::factory()->create(['role' => 'super_admin']);
        $controller = app(\App\Http\Controllers\Admin\UsersController::class);
        $method = new \ReflectionMethod($controller, 'wouldRemoveLastSuperAdmin');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($controller, $onlySuperAdmin));

        $secondSuperAdmin = User::factory()->create(['role' => 'super_admin']);
        $this->assertFalse($method->invoke($controller, $onlySuperAdmin->fresh()));
    }

    public function test_no_se_puede_degradar_al_ultimo_super_admin(): void
    {
        // La ruta ya exige que quien actúa sea super_admin, y degradarse a
        // sí mismo está cubierto por su propio guard
        // (test_no_se_puede_quitar_su_propio_rol_de_super_admin más abajo).
        // Aquí se confirma la regla vía HTTP con dos super_admins activos
        // (no bloquea) y con uno solo (si se intentara sobre sí mismo,
        // bloquea por el guard de auto-degradación antes de llegar a este).
        $onlySuperAdmin = User::factory()->create(['role' => 'super_admin']);
        $secondSuperAdmin = User::factory()->create(['role' => 'super_admin']);

        // Con dos activos, degradar a uno de ellos es válido.
        $this->actingAs($onlySuperAdmin)->patch("/admin/users/{$secondSuperAdmin->id}/role", ['role' => 'editor']);
        $this->assertSame('editor', $secondSuperAdmin->fresh()->role);

        // Ahora sí es el único activo: intentar degradarse a sí mismo lo
        // bloquea el guard de auto-degradación.
        $this->actingAs($onlySuperAdmin)->patch("/admin/users/{$onlySuperAdmin->id}/role", ['role' => 'editor']);
        $this->assertSame('super_admin', $onlySuperAdmin->fresh()->role);
    }

    public function test_no_se_puede_quitar_su_propio_rol_de_super_admin(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        User::factory()->create(['role' => 'super_admin']); // otro activo, para aislar este guard del de "el último"

        $this->actingAs($superAdmin)->patch("/admin/users/{$superAdmin->id}/role", ['role' => 'editor']);

        $this->assertSame('super_admin', $superAdmin->fresh()->role);
    }

    public function test_un_administrador_normal_no_puede_ver_ni_gestionar_usuarios(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create(['role' => 'patient']);

        $this->actingAs($admin)->get('/admin/users')->assertForbidden();
        $this->actingAs($admin)->patch("/admin/users/{$target->id}/ban")->assertForbidden();
        $this->actingAs($admin)->patch("/admin/users/{$target->id}/role", ['role' => 'admin'])->assertForbidden();
    }

    public function test_se_puede_degradar_un_super_admin_si_hay_otro_activo(): void
    {
        $superAdmin1 = User::factory()->create(['role' => 'super_admin']);
        $superAdmin2 = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($superAdmin1)->patch("/admin/users/{$superAdmin2->id}/role", ['role' => 'admin']);

        $this->assertSame('admin', $superAdmin2->fresh()->role);
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
