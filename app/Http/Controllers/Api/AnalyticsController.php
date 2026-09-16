<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class AnalyticsController extends Controller
{
    private const VALID_NETWORKS = ['facebook', 'instagram', 'tiktok', 'linkedin', 'youtube', 'x'];

    /**
     * Registra un clic en un link de red social del footer. Se llama vía
     * navigator.sendBeacon justo antes de que el navegador navegue afuera
     * (target="_blank") — ver partials/social-icons.blade.php. Sin CSRF
     * (excepción en bootstrap/app.php): sendBeacon no siempre logra mandar
     * el header X-CSRF-TOKEN de forma fiable, y esto no muta nada sensible.
     */
    public function socialClick(Request $request): Response
    {
        $network = $request->input('network');

        if (! in_array($network, self::VALID_NETWORKS, true)) {
            return response('', 204);
        }

        try {
            AnalyticsEvent::create([
                'type' => 'social_click',
                'meta' => ['network' => $network],
                'session_id' => $request->session()->getId(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('No se pudo registrar el clic de red social: '.$e->getMessage());
        }

        return response('', 204);
    }
}
