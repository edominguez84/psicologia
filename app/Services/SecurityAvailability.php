<?php

namespace App\Services;

/**
 * "Disponible de verdad" = lo que el admin activó (site_settings.security) Y
 * lo que realmente tiene credenciales configuradas en .env. El admin puede
 * "armar" un canal (ej. sms:true) sin credenciales: queda marcado como
 * pendiente de configuración, pero el sistema sigue usando email de respaldo
 * hasta que las credenciales existan de verdad — así SMS/WhatsApp/OAuth
 * quedan con toda la estructura lista sin arriesgar dejar a alguien sin poder
 * entrar por elegir un canal aún no implementado.
 */
class SecurityAvailability
{
    public function __construct(private SiteSettingsService $settings)
    {
    }

    private function security(): array
    {
        return $this->settings->get('security', [
            'channels' => config('site.security.default_channels'),
            'oauth' => config('site.security.default_oauth'),
            'totp_enabled' => config('site.security.totp_enabled'),
            'trusted_device_days' => config('site.security.trusted_device_days'),
        ]);
    }

    /**
     * Canales de 2FA realmente disponibles. Email siempre está disponible
     * (no depende de ninguna credencial externa).
     */
    public function channels(): array
    {
        $toggles = $this->security()['channels'] ?? [];

        return [
            'email' => true,
            'sms' => (bool) ($toggles['sms'] ?? false) && $this->hasTwilioCredentials(),
            'whatsapp' => (bool) ($toggles['whatsapp'] ?? false) && $this->hasTwilioCredentials(),
        ];
    }

    /**
     * Como channels(), pero incluye canales "armados" sin credenciales, para
     * que la UI de administración pueda mostrarlos como pendientes en vez de
     * simplemente ocultarlos.
     */
    public function armedChannels(): array
    {
        $toggles = $this->security()['channels'] ?? [];

        return [
            'email' => true,
            'sms' => (bool) ($toggles['sms'] ?? false),
            'whatsapp' => (bool) ($toggles['whatsapp'] ?? false),
        ];
    }

    public function oauth(): array
    {
        $toggles = $this->security()['oauth'] ?? [];

        return [
            'google' => (bool) ($toggles['google'] ?? false) && $this->hasOauthCredentials('google'),
            'facebook' => (bool) ($toggles['facebook'] ?? false) && $this->hasOauthCredentials('facebook'),
            'microsoft' => (bool) ($toggles['microsoft'] ?? false) && $this->hasOauthCredentials('microsoft'),
        ];
    }

    public function armedOauth(): array
    {
        $toggles = $this->security()['oauth'] ?? [];

        return [
            'google' => (bool) ($toggles['google'] ?? false),
            'facebook' => (bool) ($toggles['facebook'] ?? false),
            'microsoft' => (bool) ($toggles['microsoft'] ?? false),
        ];
    }

    public function hasAnyOauthAvailable(): bool
    {
        return in_array(true, $this->oauth(), true);
    }

    public function totpEnabled(): bool
    {
        return (bool) ($this->security()['totp_enabled'] ?? true);
    }

    public function trustedDeviceDays(): int
    {
        return (int) ($this->security()['trusted_device_days'] ?? 30);
    }

    public function hasTwilioCredentials(): bool
    {
        return filled(config('services.twilio.sid')) && filled(config('services.twilio.token'));
    }

    public function hasOauthCredentials(string $provider): bool
    {
        return filled(config("services.{$provider}.client_id"))
            && filled(config("services.{$provider}.client_secret"));
    }
}
