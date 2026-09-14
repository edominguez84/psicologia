<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SystemLogControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $logPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logPath = storage_path('logs/laravel.log');
    }

    protected function tearDown(): void
    {
        // No se borra un log real que ya existiera antes del test; solo se
        // restaura si este test fue quien lo creó desde cero.
        parent::tearDown();
    }

    public function test_invitado_no_puede_ver_los_logs(): void
    {
        $this->get('/admin/system-log')->assertRedirect(route('login'));
    }

    public function test_administradora_no_super_recibe_403(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/system-log')->assertForbidden();
    }

    public function test_super_admin_puede_ver_los_logs(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $existed = File::exists($this->logPath);
        $original = $existed ? File::get($this->logPath) : null;

        File::put($this->logPath, "[2026-01-01T10:00:00.000000+00:00] local.ERROR: Algo falló en el sistema\n");

        try {
            $response = $this->actingAs($superAdmin)->get('/admin/system-log');

            $response->assertOk();
            $response->assertSee('Algo falló en el sistema');
            $response->assertSee('error');
        } finally {
            $existed ? File::put($this->logPath, $original) : File::delete($this->logPath);
        }
    }

    public function test_reconoce_el_formato_real_de_fecha_de_monolog_de_este_proyecto(): void
    {
        // Regresión: el formato real que escribe este proyecto es
        // "Y-m-d H:i:s" (espacio, sin milisegundos ni offset) — la primera
        // versión del parser solo aceptaba un formato ISO8601 con "T" que
        // nunca aparece en storage/logs/laravel.log, así que ninguna
        // entrada se reconocía nunca.
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $existed = File::exists($this->logPath);
        $original = $existed ? File::get($this->logPath) : null;

        File::put($this->logPath, "[2026-09-13 22:02:14] local.DEBUG: Mensaje con formato real de Monolog\n");

        try {
            $response = $this->actingAs($superAdmin)->get('/admin/system-log');

            $response->assertOk();
            $response->assertSee('Mensaje con formato real de Monolog');
            $response->assertSee('debug');
        } finally {
            $existed ? File::put($this->logPath, $original) : File::delete($this->logPath);
        }
    }

    public function test_respeta_el_tope_maximo_de_lineas(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $existed = File::exists($this->logPath);
        $original = $existed ? File::get($this->logPath) : null;

        File::put($this->logPath, "[2026-01-01T10:00:00.000000+00:00] local.INFO: Entrada de prueba\n");

        try {
            $response = $this->actingAs($superAdmin)->get('/admin/system-log?lines=99999');

            $response->assertOk();
            $response->assertViewHas('lines', 500);
        } finally {
            $existed ? File::put($this->logPath, $original) : File::delete($this->logPath);
        }
    }
}
