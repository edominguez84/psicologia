<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SiteSettingsService;
use Illuminate\Http\Request;

class ContactSettingsController extends Controller
{
    public function __construct(private SiteSettingsService $settings)
    {
    }

    public function edit()
    {
        return view('admin.contact.edit', [
            'contact' => config('site.contact'),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'whatsapp' => ['required', 'string', 'regex:/^[0-9]{8,15}$/'],
            'whatsapp_show' => ['required', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:180'],
            'area' => ['required', 'string', 'max:160'],
            'response' => ['required', 'string', 'max:160'],
        ], [
            'whatsapp.regex' => 'Solo dígitos, con código de país, sin espacios ni símbolos (ej. 50370257845).',
        ]);

        $this->settings->set('contact', $data);

        return back()->with('status', 'Datos de contacto actualizados.');
    }
}
