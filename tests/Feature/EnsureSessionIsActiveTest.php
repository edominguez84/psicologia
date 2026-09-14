<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnsureSessionIsActiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_una_sesion_dentro_del_tiempo_configurado_sigue_activa(): void
    {
        app(SiteSettingsService::class)->set('security', ['inactivity_timeout_minutes' => 30]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin);
        $this->withSession(['last_activity_at' => now()->subMinutes(10)]);

        $response = $this->get('/admin');

        $response->assertOk();
        $this->assertAuthenticated();
    }

    public function test_una_sesion_que_supera_el_tiempo_configurado_se_cierra(): void
    {
        app(SiteSettingsService::class)->set('security', ['inactivity_timeout_minutes' => 30]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin);
        $this->withSession(['last_activity_at' => now()->subMinutes(31)]);

        $response = $this->get('/admin');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_sin_configuracion_previa_usa_30_minutos_por_defecto(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin);
        $this->withSession(['last_activity_at' => now()->subMinutes(31)]);

        $response = $this->get('/admin');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_session_ping_refresca_la_ultima_actividad(): void
    {
        $patient = User::factory()->create(['role' => 'patient']);

        $response = $this->actingAs($patient)
            ->withSession(['last_activity_at' => now()->subMinutes(29)])
            ->post('/session/ping');

        $response->assertNoContent();
        $this->assertAuthenticated();
    }

    public function test_un_invitado_no_se_ve_afectado_por_el_middleware(): void
    {
        $response = $this->get('/');

        $response->assertOk();
    }
}
