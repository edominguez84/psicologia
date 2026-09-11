<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SiteSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GalleryController extends Controller
{
    public function __construct(private SiteSettingsService $settings)
    {
    }

    /**
     * Lista efectiva: lo guardado en site_settings si ya existe, si no las
     * imágenes de ejemplo de config/site.php (para poder editarlas/quitarlas
     * desde el primer uso, igual que ContentController con las secciones).
     */
    private function effectiveImages(): array
    {
        $override = $this->settings->get('gallery');

        return $override['images'] ?? config('site.gallery.images', []);
    }

    public function edit()
    {
        $images = collect($this->effectiveImages())
            ->map(fn ($img) => [
                ...$img,
                'url' => str_starts_with($img['path'], 'gallery/')
                    ? Storage::url($img['path'])
                    : asset($img['path']),
            ])
            ->values()
            ->all();

        return view('admin.gallery.edit', [
            'images' => $images,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'image' => ['required', 'image', 'max:2048', 'dimensions:max_width=2400,max_height=2400'],
            'alt'   => ['nullable', 'string', 'max:150'],
        ], [
            'image.image'      => 'Debe ser una imagen (PNG, JPG o similar).',
            'image.max'        => 'La imagen no debe superar 2 MB.',
            'image.dimensions' => 'La imagen no debe superar 2400×2400 píxeles.',
        ]);

        $path = $request->file('image')->store('gallery', 'public');

        $images = $this->effectiveImages();
        $images[] = ['path' => $path, 'alt' => $request->input('alt', '')];

        $this->settings->set('gallery', ['images' => $images]);

        return back()->with('status', 'Imagen añadida a la galería.');
    }

    /**
     * Guarda el orden y los textos alternativos tras usar los botones
     * subir/bajar del formulario (se envía el array completo, igual que hace
     * ContentController con los repetidores de contenido).
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'images'        => ['required', 'array'],
            'images.*.path' => ['required', 'string'],
            'images.*.alt'  => ['nullable', 'string', 'max:150'],
        ]);

        $this->settings->set('gallery', ['images' => $data['images']]);

        return back()->with('status', 'Galería actualizada.');
    }

    public function destroy(int $index)
    {
        $images = $this->effectiveImages();

        if (! array_key_exists($index, $images)) {
            return back()->with('status', 'Esa imagen ya no existe.');
        }

        $removed = $images[$index];

        // Solo se borra el archivo si fue subido por la administradora
        // (vive bajo storage/app/public/gallery/); las de ejemplo de fábrica
        // viven en public/images/gallery/ y solo se quitan del listado.
        if (str_starts_with($removed['path'], 'gallery/')) {
            Storage::disk('public')->delete($removed['path']);
        }

        unset($images[$index]);

        $this->settings->set('gallery', ['images' => array_values($images)]);

        return back()->with('status', 'Imagen eliminada de la galería.');
    }
}
