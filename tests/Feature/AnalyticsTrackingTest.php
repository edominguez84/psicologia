<?php

namespace Tests\Feature;

use App\Models\AnalyticsEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitar_la_home_registra_un_page_view(): void
    {
        $this->get('/');

        $this->assertDatabaseCount('analytics_events', 1);
        $this->assertDatabaseHas('analytics_events', ['type' => 'page_view']);
    }

    public function test_visitar_la_home_dos_veces_en_la_misma_sesion_solo_cuenta_una_vez(): void
    {
        // withSession([]) hace que ambas requests de este test compartan la
        // misma sesión de navegador simulada — el flag
        // analytics_page_view_recorded que queda en ella tras la primera
        // visita evita que la segunda cuente como una visita nueva.
        $this->withSession([])->get('/');
        $this->withSession([])->get('/');

        $this->assertDatabaseCount('analytics_events', 1);
    }

    public function test_registra_un_clic_de_red_social_valido(): void
    {
        $response = $this->postJson('/analytics/social-click', ['network' => 'instagram']);

        $response->assertNoContent();
        $this->assertDatabaseHas('analytics_events', ['type' => 'social_click']);
        $event = AnalyticsEvent::first();
        $this->assertSame('instagram', $event->meta['network']);
    }

    public function test_ignora_una_red_social_no_reconocida(): void
    {
        $response = $this->postJson('/analytics/social-click', ['network' => 'red-inventada']);

        $response->assertNoContent();
        $this->assertDatabaseCount('analytics_events', 0);
    }
}
