<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Analytics propio y mínimo (sin servicios externos, sin cookies de
 * terceros): un evento por visita a la home ('page_view') y por clic en un
 * link de red social ('social_click', con la red en meta.network). No
 * pretende ser un reemplazo de Google Analytics — solo alimenta las métricas
 * simples del dashboard de Admin\AnalyticsDashboardController.
 */
class AnalyticsEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['type', 'meta', 'session_id', 'created_at'];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    public function scopePageViews($query)
    {
        return $query->where('type', 'page_view');
    }

    public function scopeSocialClicks($query)
    {
        return $query->where('type', 'social_click');
    }
}
