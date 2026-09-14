<?php

namespace Tests\Feature\Admin;

use App\Mail\LoginCode;
use App\Models\CustomSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ActivityLogControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_no_puede_ver_el_registro(): void
    {
        $this->get('/admin/activity-log')->assertRedirect(route('login'));
    }

    public function test_administradora_no_super_recibe_403(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/activity-log')->assertForbidden();
    }

    public function test_super_admin_puede_ver_el_registro(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($superAdmin)->get('/admin/activity-log')->assertOk();
    }

    public function test_crear_una_seccion_personalizada_genera_actividad(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $section = CustomSection::create([
            'title' => 'Prueba', 'body' => 'Texto', 'position' => 1, 'is_active' => true,
        ]);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => CustomSection::class,
            'subject_id' => $section->id,
        ]);

        $response = $this->actingAs($superAdmin)->get('/admin/activity-log');
        $response->assertOk();
        $response->assertSee('created', false);
    }

    public function test_completar_el_login_con_2fa_genera_actividad_de_tipo_auth(): void
    {
        Mail::fake();
        $user = User::factory()->create(['role' => 'admin']);

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $sent = null;
        Mail::assertSent(LoginCode::class, function ($mail) use (&$sent) {
            $sent = $mail->code;

            return true;
        });

        $this->post('/2fa/verify', ['code' => $sent]);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'auth',
            'causer_id' => $user->id,
            'description' => 'login',
        ]);
    }

    public function test_cerrar_sesion_genera_actividad_de_tipo_auth(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)->post('/logout');

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'auth',
            'causer_id' => $user->id,
            'description' => 'logout',
        ]);
    }

    public function test_un_intento_de_login_fallido_genera_actividad(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->post('/login', ['email' => $user->email, 'password' => 'contraseña-incorrecta']);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'auth',
            'description' => 'login_failed',
        ]);
    }

    public function test_filtrar_por_usuario(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $otherUser = User::factory()->create(['role' => 'admin']);

        activity('test')->causedBy($superAdmin)->log('acción de la super admin');
        activity('test')->causedBy($otherUser)->log('acción del otro usuario');

        $response = $this->actingAs($superAdmin)->get('/admin/activity-log?user='.$otherUser->id);

        $response->assertOk();
        $response->assertSee('acción del otro usuario');
        $response->assertDontSee('acción de la super admin');
    }
}
