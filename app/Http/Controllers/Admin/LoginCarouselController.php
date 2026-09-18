<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SiteSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Imágenes del carrusel de la plantilla de login "carousel" — mismo patrón
 * exacto que Admin\GalleryController (multi-imagen, reorder, alt text,
 * distinción entre imágenes de fábrica y subidas), guardando en
 * site_settings.login_carousel en vez de site_settings.gallery.
 */
class LoginCarouselController extends Controller
{
    /**
     * Imágenes de fábrica: las mismas 3 fotos de stock ya usadas como
     * placeholder de la galería de la landing — evita que el carrusel de
     * login se vea vacío antes de que el super_admin suba sus propias fotos.
     * Pública porque auth/login-carousel.blade.php también la usa como
     * fallback (mismo patrón que effectiveImages(), duplicado ahí porque el
     * login se renderiza sin pasar por este controlador).
     */
    public const DEFAULT_IMAGES = [
        ['path' => 'images/gallery/therapy-1.jpg', 'alt' => 'Sesión de terapia online'],
        ['path' => 'images/gallery/therapy-2.jpg', 'alt' => 'Espacio de acompañamiento'],
        ['path' => 'images/gallery/therapy-3.jpg', 'alt' => 'Terapia psicológica'],
    ];

    public function __construct(private SiteSettingsService $settings)
    {
    }

    private function effectiveImages(): array
    {
        $override = $this->settings->get('login_carousel');

        return $override['images'] ?? self::DEFAULT_IMAGES;
    }

    public function edit()
    {
        $images = collect($this->effectiveImages())
            ->map(fn ($img) => [
                ...$img,
                'url' => str_starts_with($img['path'], 'login-carousel/')
                    ? Storage::url($img['path'])
                    : asset($img['path']),
            ])
            ->values()
            ->all();

        return view('admin.login-carousel.edit', [
            'images' => $images,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'image' => ['required', 'image', 'max:2048', 'dimensions:max_width=2400,max_height=2400'],
            'alt' => ['nullable', 'string', 'max:150'],
        ], [
            'image.image' => 'Debe ser una imagen (PNG, JPG o similar).',
            'image.max' => 'La imagen no debe superar 2 MB.',
            'image.dimensions' => 'La imagen no debe superar 2400×2400 píxeles.',
        ]);

        $path = $request->file('image')->store('login-carousel', 'public');

        $images = $this->effectiveImages();
        $images[] = ['path' => $path, 'alt' => $request->input('alt', '')];

        $this->settings->set('login_carousel', ['images' => $images]);

        return back()->with('status', 'Imagen añadida al carrusel de login.');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'images' => ['required', 'array'],
            'images.*.path' => ['required', 'string'],
            'images.*.alt' => ['nullable', 'string', 'max:150'],
        ]);

        $this->settings->set('login_carousel', ['images' => $data['images']]);

        return back()->with('status', 'Carrusel de login actualizado.');
    }

    public function destroy(int $index)
    {
        $images = $this->effectiveImages();

        if (! array_key_exists($index, $images)) {
            return back()->with('status', 'Esa imagen ya no existe.');
        }

        $removed = $images[$index];

        if (str_starts_with($removed['path'], 'login-carousel/')) {
            Storage::disk('public')->delete($removed['path']);
        }

        unset($images[$index]);

        $this->settings->set('login_carousel', ['images' => array_values($images)]);

        return back()->with('status', 'Imagen eliminada del carrusel de login.');
    }
}
