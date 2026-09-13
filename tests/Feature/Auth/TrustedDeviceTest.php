<?php

namespace Tests\Feature\Auth;

use App\Mail\LoginCode;
use App\Models\User;
use App\Services\TrustedDeviceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TrustedDeviceTest extends TestCase
{
    use RefreshDatabase;

    public function test_recordar_dispositivo_crea_una_fila_y_una_cookie(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $sent = null;
        Mail::assertSent(LoginCode::class, function ($mail) use (&$sent) {
            $sent = $mail->code;

            return true;
        });

        $response = $this->post('/2fa/verify', ['code' => $sent, 'remember_device' => '1']);

        $this->assertAuthenticated();
        $this->assertDatabaseCount('trusted_devices', 1);
        $response->assertCookie('trusted_device');
    }

    public function test_dispositivo_de_confianza_salta_el_2fa_en_el_siguiente_login(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        // Se crea el dispositivo de confianza directamente vía el servicio
        // (en vez de descifrar la cookie real de una respuesta anterior, que
        // exigiría reimplementar en el test el formato interno de
        // EncryptCookies) para poder controlar el token en texto plano y
        // simular exactamente lo que el navegador reenviaría.
        $rawToken = 'raw-test-token-1234567890';
        $user->trustedDevices()->create([
            'token_hash' => Hash::make($rawToken),
            'expires_at' => now()->addDays(30),
        ]);
        $deviceId = $user->trustedDevices()->first()->id;

        // withCookie() (a diferencia de withUnencryptedCookie) cifra el valor
        // automáticamente con el mismo formato que usa EncryptCookies — se le
        // pasa el valor en texto plano, tal como lo tendría el navegador antes
        // de que el middleware lo cifrara al guardarlo.
        $response = $this->withCookie('trusted_device', "{$deviceId}:{$rawToken}")
            ->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->assertAuthenticated();
        $response->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_dispositivo_expirado_no_salta_el_2fa(): void
    {
        Mail::fake();
        $user = User::factory()->create();

        $rawToken = 'raw-test-token-expired';
        $user->trustedDevices()->create([
            'token_hash' => Hash::make($rawToken),
            'expires_at' => now()->subDay(),
        ]);
        $deviceId = $user->trustedDevices()->first()->id;

        $response = $this->withCookie('trusted_device', "{$deviceId}:{$rawToken}")
            ->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->assertGuest();
        $response->assertRedirect(route('2fa.challenge'));
    }

    public function test_isTrusted_reconoce_un_token_valido(): void
    {
        $user = User::factory()->create();
        $service = app(TrustedDeviceService::class);
        $rawToken = 'another-raw-token';
        $device = $user->trustedDevices()->create([
            'token_hash' => Hash::make($rawToken),
            'expires_at' => now()->addDays(30),
        ]);

        $request = \Illuminate\Http\Request::create('/login', 'POST');
        $request->cookies->set('trusted_device', "{$device->id}:{$rawToken}");

        $this->assertTrue($service->isTrusted($user, $request));
    }
}
