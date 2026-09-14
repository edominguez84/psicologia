<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\SiteSettingsService;
use App\Support\ProfanityFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfanityFilterControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_no_puede_ver_la_configuracion(): void
    {
        $this->get('/admin/profanity-filter')->assertRedirect(route('login'));
    }

    public function test_administradora_no_super_recibe_403(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/profanity-filter')->assertForbidden();
    }

    public function test_super_admin_puede_agregar_palabras_extra_y_ajustar_el_umbral(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($superAdmin)->put('/admin/profanity-filter', [
            'extra_words' => "tonto\nbobo",
            'ban_threshold' => 3,
        ]);

        $response->assertRedirect();
        $config = app(SiteSettingsService::class)->get('profanity_filter');
        $this->assertSame(['tonto', 'bobo'], $config['extra_words']);
        $this->assertSame(3, $config['ban_threshold']);
    }

    public function test_una_palabra_extra_guardada_se_detecta_en_el_filtro(): void
    {
        app(SiteSettingsService::class)->set('profanity_filter', ['extra_words' => ['tonto'], 'ban_threshold' => 5]);

        $this->assertTrue(ProfanityFilter::containsProhibitedWord('Eres un tonto'));
        $this->assertFalse(ProfanityFilter::containsProhibitedWord('Eres una buena persona'));
    }
}
