<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CronRunnerControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.cron_runner.secret' => 'test-secret']);
    }

    public function test_rechaza_sin_secreto(): void
    {
        $this->get('/cron/run-scheduler')->assertForbidden();
    }

    public function test_rechaza_con_secreto_incorrecto(): void
    {
        $this->get('/cron/run-scheduler?secret=incorrecto')->assertForbidden();
    }

    public function test_acepta_con_el_secreto_correcto_y_corre_el_scheduler(): void
    {
        $response = $this->get('/cron/run-scheduler?secret=test-secret');

        $response->assertOk();
        $response->assertSee('ok');
    }

    public function test_rechaza_si_no_hay_ningun_secreto_configurado(): void
    {
        config(['services.cron_runner.secret' => null]);

        $this->get('/cron/run-scheduler?secret=')->assertForbidden();
    }
}
