<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SiteSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AboutPhotoController extends Controller
{
    public function __construct(private SiteSettingsService $settings)
    {
    }

    public function edit()
    {
        return view('admin.about-photo.edit', [
            'photo' => $this->settings->get('about_photo'),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'photo' => ['required', 'image', 'max:2048', 'dimensions:max_width=2000,max_height=2000'],
        ], [
            'photo.image'      => 'Debe ser una imagen (PNG, JPG o similar).',
            'photo.max'        => 'La imagen no debe superar 2 MB.',
            'photo.dimensions' => 'La imagen no debe superar 2000×2000 píxeles.',
        ]);

        $previous = $this->settings->get('about_photo');
        if (! empty($previous['path'])) {
            Storage::disk('public')->delete($previous['path']);
        }

        $path = $request->file('photo')->store('about', 'public');
        $this->settings->set('about_photo', ['path' => $path]);

        return back()->with('status', 'Foto de portada actualizada.');
    }

    public function destroy()
    {
        $photo = $this->settings->get('about_photo');
        if (! empty($photo['path'])) {
            Storage::disk('public')->delete($photo['path']);
        }

        $this->settings->forget('about_photo');

        return back()->with('status', 'Se restauró la foto por defecto.');
    }
}
