<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleManagementControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_no_puede_ver_el_listado(): void
    {
        $this->get('/admin/roles')->assertRedirect(route('login'));
    }

    public function test_un_administrador_normal_no_puede_gestionar_roles(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/roles')->assertForbidden();
        $this->actingAs($admin)->post('/admin/roles', ['name' => 'Recepcionista'])->assertForbidden();
    }

    public function test_la_migracion_siembra_los_5_roles_de_sistema(): void
    {
        $slugs = Role::pluck('slug')->sort()->values()->all();

        $this->assertSame(['admin', 'editor', 'patient', 'super_admin', 'user'], $slugs);
        $this->assertTrue(Role::where('slug', 'super_admin')->value('is_system'));
    }

    public function test_admin_nace_con_las_15_secciones_historicas_activas_y_el_resto_apagado(): void
    {
        $admin = Role::where('slug', 'admin')->first();
        $permissions = $admin->permissionsOrDefault();

        $this->assertTrue($permissions['theme']);
        $this->assertTrue($permissions['messages']);
        $this->assertTrue($permissions['security']);

        // Secciones antes exclusivas de super_admin: existen en el catálogo
        // (así el super_admin puede delegarlas puntualmente) pero nacen
        // apagadas para admin/editor, no todas encendidas de fábrica.
        $this->assertFalse($permissions['users']);
        $this->assertFalse($permissions['roles']);
        $this->assertFalse($permissions['chatbot-channels']);
        $this->assertFalse($permissions['payment-settings']);
    }

    public function test_la_pantalla_de_editar_permisos_lista_siempre_todas_las_secciones(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $adminRole = Role::where('slug', 'admin')->first();

        $response = $this->actingAs($superAdmin)->get("/admin/roles/{$adminRole->id}/edit");

        $response->assertOk();
        // Las 15 históricas y las nuevas delegables aparecen todas en el
        // formulario, aunque hoy admin no tenga estas últimas activas — el
        // super_admin debe poder tildarlas para delegar acceso temporal.
        $response->assertSee('Apariencia', false);
        $response->assertSee('Usuarios (banear, cambiar rol)', false);
        $response->assertSee('Credenciales de chatbot', false);
        $response->assertSee('Métodos de pago (Wompi, transferencia)', false);
    }

    public function test_super_admin_puede_delegarle_temporalmente_a_admin_una_seccion_antes_exclusiva(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $adminRole = Role::where('slug', 'admin')->first();
        $adminUser = User::factory()->create(['role' => 'admin']);

        // Antes de delegar, admin no puede entrar a canales del chatbot.
        $this->actingAs($adminUser)->get('/admin/chatbot-channels')->assertForbidden();

        // El super_admin le activa esa sección puntual, conservando el resto
        // de permisos históricos de admin.
        $historicFeatures = array_keys(array_filter($adminRole->permissionsOrDefault()));
        $response = $this->actingAs($superAdmin)->put("/admin/roles/{$adminRole->id}", [
            'name' => 'Administrador',
            'features' => array_merge($historicFeatures, ['chatbot-channels']),
        ]);

        $response->assertRedirect();
        $this->assertTrue($adminRole->fresh()->permissionsOrDefault()['chatbot-channels']);
        $this->actingAs($adminUser)->get('/admin/chatbot-channels')->assertOk();

        // El resto de secciones sensibles sigue apagado — la delegación es
        // puntual, no un "todo o nada".
        $this->assertFalse($adminRole->fresh()->permissionsOrDefault()['users']);
    }

    public function test_un_admin_delegado_no_puede_ascender_a_nadie_a_super_admin(): void
    {
        $adminRole = Role::where('slug', 'admin')->first();
        $adminRole->update(['permissions' => array_merge($adminRole->permissionsOrDefault(), ['users' => true])]);

        $adminUser = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create(['role' => 'patient']);

        $response = $this->actingAs($adminUser)
            ->patch("/admin/users/{$target->id}/role", ['role' => 'super_admin']);

        $response->assertRedirect();
        $this->assertSame('patient', $target->fresh()->role);
    }

    public function test_un_admin_delegado_no_puede_crear_una_cuenta_super_admin(): void
    {
        $adminRole = Role::where('slug', 'admin')->first();
        $adminRole->update(['permissions' => array_merge($adminRole->permissionsOrDefault(), ['staff' => true])]);

        $adminUser = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($adminUser)->post('/admin/staff', [
            'name' => 'Intento',
            'email' => 'intento-super@example.com',
            'password' => 'Contrasena#123',
            'role' => 'super_admin',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'intento-super@example.com']);
    }

    public function test_super_admin_puede_crear_un_rol_nuevo_con_permisos(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($superAdmin)->post('/admin/roles', [
            'name' => 'Recepcionista',
            'features' => ['messages', 'appointments'],
        ]);

        $response->assertRedirect();
        $role = Role::where('name', 'Recepcionista')->first();
        $this->assertNotNull($role);
        $this->assertSame('recepcionista', $role->slug);
        $this->assertFalse($role->is_system);
        $this->assertTrue($role->is_staff);
        $this->assertTrue($role->permissions['messages']);
        $this->assertTrue($role->permissions['appointments']);
        $this->assertFalse($role->permissions['theme']);
    }

    public function test_el_rol_nuevo_queda_disponible_para_crear_cuentas(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($superAdmin)->post('/admin/roles', ['name' => 'Recepcionista', 'features' => ['messages']]);

        $response = $this->actingAs($superAdmin)->post('/admin/staff', [
            'name' => 'Nueva Recepcionista',
            'email' => 'recepcionista@example.com',
            'password' => 'Contrasena#123',
            'role' => 'recepcionista',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'recepcionista@example.com', 'role' => 'recepcionista']);
    }

    public function test_una_cuenta_con_el_rol_nuevo_respeta_sus_permisos(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($superAdmin)->post('/admin/roles', ['name' => 'Recepcionista', 'features' => ['messages']]);

        $recepcionista = User::factory()->create(['role' => 'recepcionista']);

        $this->actingAs($recepcionista)->get('/admin/messages')->assertOk();
        $this->actingAs($recepcionista)->get('/admin/theme')->assertForbidden();
    }

    public function test_no_se_puede_eliminar_un_rol_del_sistema(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $adminRole = Role::where('slug', 'admin')->first();

        $response = $this->actingAs($superAdmin)->delete("/admin/roles/{$adminRole->id}");

        $response->assertRedirect();
        $this->assertDatabaseHas('roles', ['slug' => 'admin']);
    }

    public function test_no_se_puede_eliminar_un_rol_con_cuentas_asignadas(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($superAdmin)->post('/admin/roles', ['name' => 'Recepcionista', 'features' => []]);
        $role = Role::where('slug', 'recepcionista')->first();
        User::factory()->create(['role' => 'recepcionista']);

        $response = $this->actingAs($superAdmin)->delete("/admin/roles/{$role->id}");

        $response->assertRedirect();
        $this->assertDatabaseHas('roles', ['slug' => 'recepcionista']);
    }

    public function test_se_puede_eliminar_un_rol_personalizado_sin_cuentas(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($superAdmin)->post('/admin/roles', ['name' => 'Recepcionista', 'features' => []]);
        $role = Role::where('slug', 'recepcionista')->first();

        $response = $this->actingAs($superAdmin)->delete("/admin/roles/{$role->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('roles', ['slug' => 'recepcionista']);
    }

    public function test_super_admin_puede_editar_los_permisos_de_un_rol_existente(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $editorRole = Role::where('slug', 'editor')->first();

        $response = $this->actingAs($superAdmin)->put("/admin/roles/{$editorRole->id}", [
            'name' => 'Editor',
            'features' => ['theme'],
        ]);

        $response->assertRedirect();
        $this->assertTrue($editorRole->fresh()->permissions['theme']);
        $this->assertFalse($editorRole->fresh()->permissions['gallery']);
    }
}
