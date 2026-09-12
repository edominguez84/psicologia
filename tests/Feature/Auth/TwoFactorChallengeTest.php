<?php

namespace Tests\Feature\Auth;

use App\Mail\LoginCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorChallengeTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_correcto_envia_un_codigo_por_email(): void
    {
        Mail::fake();
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        Mail::assertSent(LoginCode::class);
    }

    public function test_codigo_correcto_autentica(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $sent = null;
        Mail::assertSent(LoginCode::class, function ($mail) use (&$sent) {
            $sent = $mail->code;

            return true;
        });

        $response = $this->post('/2fa/verify', ['code' => $sent]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_codigo_incorrecto_no_autentica(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $response = $this->post('/2fa/verify', ['code' => '000000']);

        $this->assertGuest();
        $response->assertSessionHasErrors('code');
    }

    public function test_codigo_expirado_dispara_reenvio_automatico_y_no_autentica(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        // Forzar la expiración del código recién generado.
        $user->loginCodes()->update(['expires_at' => now()->subMinute()]);

        $response = $this->post('/2fa/verify', ['code' => '123456']);

        $this->assertGuest();
        $response->assertSessionHasErrors('code');
        Mail::assertSent(LoginCode::class, 2); // el inicial + el reenvío automático
    }

    public function test_metodo_totp_no_envia_email_y_verifica_con_el_secreto(): void
    {
        Mail::fake();
        $secret = (new Google2FA())->generateSecretKey();
        $user = User::factory()->create([
            'two_factor_method' => 'totp',
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
        ]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        Mail::assertNotSent(LoginCode::class);

        $code = (new Google2FA())->getCurrentOtp($secret);
        $response = $this->post('/2fa/verify', ['code' => $code]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_sin_sesion_pendiente_redirige_a_login(): void
    {
        $response = $this->get('/2fa/challenge');

        $response->assertRedirect(route('login'));
    }
}
