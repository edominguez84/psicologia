<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SiteSettingsService;
use Illuminate\Http\Request;

class SocialLinksController extends Controller
{
    /**
     * Redes soportadas: clave => etiqueta. Deben coincidir con las claves de
     * $paths en resources/views/partials/social-icons.blade.php.
     */
    public const NETWORKS = [
        'facebook'  => 'Facebook',
        'instagram' => 'Instagram',
        'tiktok'    => 'TikTok',
        'linkedin'  => 'LinkedIn',
        'youtube'   => 'YouTube',
        'x'         => 'X (Twitter)',
    ];

    public function __construct(private SiteSettingsService $settings)
    {
    }

    public function edit()
    {
        return view('admin.social.edit', [
            'networks' => self::NETWORKS,
            'links'    => $this->settings->get('social', []),
        ]);
    }

    public function update(Request $request)
    {
        $rules = [];
        foreach (self::NETWORKS as $key => $label) {
            $rules[$key] = ['nullable', 'url', 'max:255'];
        }

        $data = $request->validate($rules, [
            'url' => 'Debe ser una URL válida (ej. https://facebook.com/tu-pagina).',
        ]);

        // Solo se guardan las redes con URL; dejar el campo vacío oculta el icono.
        $links = collect($data)->filter()->all();

        $this->settings->set('social', $links);

        return back()->with('status', 'Redes sociales actualizadas.');
    }
}
