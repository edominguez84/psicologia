<?php

namespace Tests\Unit;

use App\Services\TwilioSmsService;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class TwilioSmsServiceTest extends TestCase
{
    private function configureCredentials(): void
    {
        config([
            'services.twilio.sid' => 'AC-test-sid',
            'services.twilio.token' => 'test-token',
            'services.twilio.from' => '+14483332827',
        ]);
    }

    public function test_lanza_excepcion_si_faltan_credenciales(): void
    {
        config([
            'services.twilio.sid' => null,
            'services.twilio.token' => null,
            'services.twilio.from' => null,
        ]);

        $this->expectException(RuntimeException::class);

        app(TwilioSmsService::class)->send('+50361079711', 'mensaje de prueba');
    }

    public function test_envia_el_sms_con_los_datos_correctos(): void
    {
        $this->configureCredentials();
        Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SM123', 'status' => 'queued'], 201)]);

        app(TwilioSmsService::class)->send('+50361079711', 'Tu código de acceso es 123456. Vence en 2 horas.');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.twilio.com/2010-04-01/Accounts/AC-test-sid/Messages.json'
                && $request['To'] === '+50361079711'
                && $request['From'] === '+14483332827'
                && $request['Body'] === 'Tu código de acceso es 123456. Vence en 2 horas.';
        });
    }

    public function test_lanza_excepcion_si_twilio_rechaza_el_envio(): void
    {
        $this->configureCredentials();
        Http::fake(['api.twilio.com/*' => Http::response(['message' => 'The number is unverified'], 400)]);

        $this->expectException(RuntimeException::class);

        app(TwilioSmsService::class)->send('+50361079711', 'mensaje de prueba');
    }
}
