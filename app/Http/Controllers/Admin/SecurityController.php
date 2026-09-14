<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SecurityAvailability;
use App\Services\SiteSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SecurityController extends Controller
{
    public function __construct(private SiteSettingsService $settings)
    {
    }

    public function edit(SecurityAvailability $availability): View
    {
        $current = $this->settings->get('security', [
            'channels' => config('site.security.default_channels'),
            'oauth' => config('site.security.default_oauth'),
            'trusted_device_days' => config('site.security.trusted_device_days'),
            'inactivity_timeout_minutes' => 30,
        ]);

        return view('admin.security.edit', [
            'current' => $current,
            'armedChannels' => $availability->armedChannels(),
            'armedOauth' => $availability->armedOauth(),
            'realChannels' => $availability->channels(),
            'realOauth' => $availability->oauth(),
            'hasTwilio' => $availability->hasTwilioCredentials(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'channels' => ['array'],
            'channels.sms' => ['nullable', 'boolean'],
            'channels.whatsapp' => ['nullable', 'boolean'],
            'oauth' => ['array'],
            'oauth.google' => ['nullable', 'boolean'],
            'oauth.facebook' => ['nullable', 'boolean'],
            'oauth.microsoft' => ['nullable', 'boolean'],
            'trusted_device_days' => ['required', 'integer', 'min:1', 'max:365'],
            'inactivity_timeout_minutes' => ['required', 'integer', 'min:1', 'max:240'],
        ]);

        $this->settings->set('security', [
            'channels' => [
                'sms' => (bool) ($data['channels']['sms'] ?? false),
                'whatsapp' => (bool) ($data['channels']['whatsapp'] ?? false),
            ],
            'oauth' => [
                'google' => (bool) ($data['oauth']['google'] ?? false),
                'facebook' => (bool) ($data['oauth']['facebook'] ?? false),
                'microsoft' => (bool) ($data['oauth']['microsoft'] ?? false),
            ],
            'trusted_device_days' => (int) $data['trusted_device_days'],
            'inactivity_timeout_minutes' => (int) $data['inactivity_timeout_minutes'],
        ]);

        return back()->with('status', 'Ajustes de seguridad actualizados.');
    }
}
