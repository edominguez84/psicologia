<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SiteSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FaviconController extends Controller
{
    public function __construct(private SiteSettingsService $settings)
    {
    }

    public function edit()
    {
        return view('admin.favicon.edit', [
            'favicon' => $this->settings->get('favicon'),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'favicon' => ['required', 'image', 'mimes:png,jpg,jpeg,ico', 'max:512', 'dimensions:max_width=512,max_height=512'],
        ], [
            'favicon.image' => 'Debe ser una imagen.',
            'favicon.mimes' => 'Debe ser PNG, JPG o ICO.',
            'favicon.max' => 'El archivo no debe superar 512 KB.',
            'favicon.dimensions' => 'La imagen no debe superar 512×512 píxeles.',
        ]);

        $previous = $this->settings->get('favicon');
        if (! empty($previous['path'])) {
            Storage::disk('public')->delete($previous['path']);
        }

        $path = $request->file('favicon')->store('branding', 'public');
        $this->settings->set('favicon', ['path' => $path]);

        return back()->with('status', 'Icono del sitio actualizado.');
    }

    public function destroy()
    {
        $favicon = $this->settings->get('favicon');
        if (! empty($favicon['path'])) {
            Storage::disk('public')->delete($favicon['path']);
        }

        $this->settings->forget('favicon');

        return back()->with('status', 'Se restauró el icono por defecto.');
    }
}
