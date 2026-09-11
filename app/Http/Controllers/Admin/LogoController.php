<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SiteSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LogoController extends Controller
{
    public function __construct(private SiteSettingsService $settings)
    {
    }

    public function edit()
    {
        return view('admin.logo.edit', [
            'logo' => $this->settings->get('logo'),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'logo' => ['required', 'image', 'max:1024', 'dimensions:max_width=800,max_height=800'],
        ], [
            'logo.image' => 'Debe ser una imagen (PNG, JPG o similar).',
            'logo.max' => 'La imagen no debe superar 1 MB.',
            'logo.dimensions' => 'La imagen no debe superar 800×800 píxeles.',
        ]);

        // Elimina el logo anterior, si existía, antes de guardar el nuevo.
        $previous = $this->settings->get('logo');
        if (! empty($previous['path'])) {
            Storage::disk('public')->delete($previous['path']);
        }

        $path = $request->file('logo')->store('branding', 'public');
        $this->settings->set('logo', ['path' => $path]);

        return back()->with('status', 'Logo actualizado.');
    }

    public function destroy()
    {
        $logo = $this->settings->get('logo');
        if (! empty($logo['path'])) {
            Storage::disk('public')->delete($logo['path']);
        }

        $this->settings->forget('logo');

        return back()->with('status', 'Se restauró el logo por defecto.');
    }
}
