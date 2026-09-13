<?php

namespace Tests\Feature\Auth;

use App\Models\OauthConnection;
use App\Models\User;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class SocialiteLoginTest extends TestCase
{
    use RefreshDatabase;

    private function enableGoogleOauth(): void
    {
        app(SiteSettingsService::class)->set('security', [
            'channels' => ['sms' => false, 'whatsapp' => false],
            'oauth' => ['google' => true, 'facebook' => false, 'microsoft' => false],
            'trusted_device_days' => 30,
        ]);
        config(['services.google.client_id' => 'test-client-id']);
        config(['services.google.client_secret' => 'test-client-secret']);
    }

    private function mockSocialiteUser(string $id, string $email): void
    {
        $socialiteUser = Mockery::mock(SocialiteUserContract::class);
        $socialiteUser->shouldReceive('getId')->andReturn($id);
        $socialiteUser->shouldReceive('getEmail')->andReturn($email);

        $provider = Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
        $provider->shouldReceive('user')->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    public function test_redirect_da_404_si_el_proveedor_no_esta_activo(): void
    {
        $this->get('/auth/google/redirect')->assertNotFound();
    }

    public function test_usuario_existente_con_email_coincidente_se_autentica_y_crea_la_conexion(): void
    {
        $this->enableGoogleOauth();
        $user = User::factory()->create(['role' => 'admin', 'email' => 'ana@example.com']);
        $this->mockSocialiteUser('google-123', 'ana@example.com');

        $response = $this->get('/auth/google/callback');

        $this->assertAuthenticated();
        $response->assertRedirect(route('admin.dashboard', absolute: false));
        $this->assertDatabaseHas('oauth_connections', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_user_id' => 'google-123',
        ]);
    }

    public function test_sin_cuenta_coincidente_no_autentica(): void
    {
        $this->enableGoogleOauth();
        $this->mockSocialiteUser('google-999', 'nadie@example.com');

        $response = $this->get('/auth/google/callback');

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    public function test_login_recurrente_usa_la_conexion_ya_enlazada(): void
    {
        $this->enableGoogleOauth();
        $user = User::factory()->create(['email' => 'otra@example.com']);
        OauthConnection::create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_user_id' => 'google-456',
        ]);
        $this->mockSocialiteUser('google-456', 'otra@example.com');

        $this->get('/auth/google/callback');

        $this->assertAuthenticated();
        $this->assertSame($user->id, auth()->id());
    }

    public function test_login_social_omite_el_reto_2fa(): void
    {
        $this->enableGoogleOauth();
        User::factory()->create(['email' => 'sin2fa@example.com']);
        $this->mockSocialiteUser('google-789', 'sin2fa@example.com');

        $this->get('/auth/google/callback');

        // A diferencia del login por contraseña, no debe existir ningún
        // estado "pending_2fa" — se autentica directo.
        $this->assertAuthenticated();
        $this->assertNull(session('pending_2fa'));
    }
}
