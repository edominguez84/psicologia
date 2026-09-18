<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * App\Http\Middleware\EnsureSiteIsNotInMaintenance — corre en toda request
 * web (registrado en bootstrap/app.php), así que estos tests cubren tanto
 * "la landing se bloquea" como "las excepciones siguen funcionando".
 */
class MaintenanceModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_home_responde_normal_si_el_mantenimiento_esta_apagado(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('Página en mantenimiento');
    }

    public function test_la_home_muestra_la_pagina_de_mantenimiento_si_esta_activo(): void
    {
        app(SiteSettingsService::class)->set('maintenance', ['enabled' => true]);

        $response = $this->get('/');

        $response->assertStatus(503);
        $response->assertSee('Página en mantenimiento');
    }

    public function test_el_panel_admin_sigue_accesible_con_mantenimiento_activo(): void
    {
        app(SiteSettingsService::class)->set('maintenance', ['enabled' => true]);
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($superAdmin)->get('/admin');

        $response->assertOk();
        $response->assertDontSee('Página en mantenimiento');
    }

    public function test_el_login_sigue_accesible_con_mantenimiento_activo(): void
    {
        app(SiteSettingsService::class)->set('maintenance', ['enabled' => true]);

        $response = $this->get('/login');

        $response->assertOk();
        $response->assertDontSee('Página en mantenimiento');
    }

    public function test_el_webhook_de_wompi_sigue_respondiendo_con_mantenimiento_activo(): void
    {
        app(SiteSettingsService::class)->set('maintenance', ['enabled' => true]);

        $response = $this->postJson('/webhooks/wompi', []);

        // No debe ser 503 (bloqueado por mantenimiento) — el controlador
        // real puede rechazar el payload vacío con otro código, lo que
        // importa es que el middleware no lo interceptó.
        $response->assertStatus(401);
    }

    public function test_el_endpoint_del_cron_sigue_respondiendo_con_mantenimiento_activo(): void
    {
        app(SiteSettingsService::class)->set('maintenance', ['enabled' => true]);

        $response = $this->get('/cron/run-scheduler');

        $response->assertStatus(403); // secreto inválido — pero no 503.
    }

    public function test_health_check_sigue_respondiendo_con_mantenimiento_activo(): void
    {
        app(SiteSettingsService::class)->set('maintenance', ['enabled' => true]);

        $response = $this->get('/up');

        $response->assertOk();
    }

    public function test_un_formulario_publico_normal_si_queda_bloqueado(): void
    {
        app(SiteSettingsService::class)->set('maintenance', ['enabled' => true]);

        $response = $this->postJson('/contacto', []);

        $response->assertStatus(503);
    }
}
