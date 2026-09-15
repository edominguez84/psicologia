<?php

namespace Tests\Feature;

use App\Models\AppointmentSlot;
use App\Models\CallSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Agendar una cita" y "Reserva una llamada gratis" no deben ofrecerse si no
 * hay ningún cupo real detrás — evita que la paciente se registre o llene un
 * formulario para toparse después con que no hay horarios.
 */
class HomeAvailabilityButtonsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sin_horarios_de_cita_no_se_muestra_agendar_una_cita(): void
    {
        // El entorno de test (PHP CLI en Windows) hereda un Accept-Language
        // en inglés del sistema por defecto — se fuerza español explícito
        // porque estos asserts comparan el texto literal (ver SiteLocaleTest
        // para el mismo problema/solución).
        $response = $this->withHeader('Accept-Language', 'es-ES,es;q=0.9')->get('/');

        $response->assertOk();
        $response->assertDontSee('Agendar una cita');
    }

    public function test_con_un_horario_de_cita_disponible_se_muestra_agendar_una_cita(): void
    {
        AppointmentSlot::create(['starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour(), 'is_active' => true]);

        // El entorno de test (PHP CLI en Windows) hereda un Accept-Language
        // en inglés del sistema por defecto — se fuerza español explícito
        // porque estos asserts comparan el texto literal (ver SiteLocaleTest
        // para el mismo problema/solución).
        $response = $this->withHeader('Accept-Language', 'es-ES,es;q=0.9')->get('/');

        $response->assertOk();
        $response->assertSee('Agendar una cita');
    }

    public function test_un_horario_de_cita_inactivo_no_cuenta_como_disponible(): void
    {
        AppointmentSlot::create(['starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour(), 'is_active' => false]);

        // El entorno de test (PHP CLI en Windows) hereda un Accept-Language
        // en inglés del sistema por defecto — se fuerza español explícito
        // porque estos asserts comparan el texto literal (ver SiteLocaleTest
        // para el mismo problema/solución).
        $response = $this->withHeader('Accept-Language', 'es-ES,es;q=0.9')->get('/');

        $response->assertDontSee('Agendar una cita');
    }

    public function test_sin_horarios_de_llamada_no_se_muestra_el_boton_de_llamada_gratis(): void
    {
        // El entorno de test (PHP CLI en Windows) hereda un Accept-Language
        // en inglés del sistema por defecto — se fuerza español explícito
        // porque estos asserts comparan el texto literal (ver SiteLocaleTest
        // para el mismo problema/solución).
        $response = $this->withHeader('Accept-Language', 'es-ES,es;q=0.9')->get('/');

        $response->assertOk();
        $response->assertDontSee('Reserva una llamada gratis de 15 min');
    }

    public function test_con_un_horario_de_llamada_disponible_se_muestra_el_boton(): void
    {
        CallSlot::create(['starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addMinutes(15), 'is_active' => true]);

        // El entorno de test (PHP CLI en Windows) hereda un Accept-Language
        // en inglés del sistema por defecto — se fuerza español explícito
        // porque estos asserts comparan el texto literal (ver SiteLocaleTest
        // para el mismo problema/solución).
        $response = $this->withHeader('Accept-Language', 'es-ES,es;q=0.9')->get('/');

        $response->assertOk();
        $response->assertSee('Reserva una llamada gratis de 15 min');
    }
}
