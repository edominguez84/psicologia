<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\AnalyticsEvent;
use App\Models\Appointment;
use App\Models\User;
use App\Support\ElSalvadorLocations;
use Illuminate\View\View;

/**
 * Dashboard analítico exclusivo de super_admin: métricas de negocio (embudo
 * de registro→cita, demografía, citas por estado) + analytics propio simple
 * (visitas a la home, clics en redes sociales) — ver App\Models\AnalyticsEvent.
 * Todas las series se calculan aquí y se pasan ya listas para ApexCharts
 * (arrays de labels/valores), la vista no vuelve a tocar Eloquent.
 */
class AnalyticsDashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.analytics.dashboard', [
            'siteVisits' => $this->siteVisitsSummary(),
            'socialClicks' => $this->socialClicksChart(),
            'conversionFunnel' => $this->conversionFunnel(),
            'genderBreakdown' => $this->genderBreakdown(),
            'departmentBreakdown' => $this->departmentBreakdown(),
            'municipalityBreakdown' => $this->municipalityBreakdown(),
            'appointmentsByMonth' => $this->appointmentsByMonthChart(),
        ]);
    }

    private function siteVisitsSummary(): array
    {
        return [
            'total' => AnalyticsEvent::pageViews()->count(),
            'last30Days' => AnalyticsEvent::pageViews()->where('created_at', '>=', now()->subDays(30))->count(),
        ];
    }

    /**
     * Serie para un gráfico de dona: cuántos clics recibió cada red social,
     * solo las que tienen al menos un clic (una red sin clics no aparece,
     * en vez de mostrar un 0 vacío en el gráfico).
     */
    private function socialClicksChart(): array
    {
        $labels = [
            'facebook' => 'Facebook', 'instagram' => 'Instagram', 'tiktok' => 'TikTok',
            'linkedin' => 'LinkedIn', 'youtube' => 'YouTube', 'x' => 'X (Twitter)',
        ];

        $counts = AnalyticsEvent::socialClicks()->get()
            ->countBy(fn ($event) => $event->meta['network'] ?? 'desconocido');

        $series = collect($labels)
            ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'count' => $counts[$key] ?? 0])
            ->filter(fn ($row) => $row['count'] > 0)
            ->values();

        return [
            'labels' => $series->pluck('label')->all(),
            'data' => $series->pluck('count')->all(),
            'total' => $counts->sum(),
        ];
    }

    /**
     * Embudo simple: cuántos pacientes se registraron en total vs. cuántos
     * de esos tienen al menos una cita (cualquier estado) — para ver la tasa
     * de conversión real de registro a solicitud de cita.
     */
    private function conversionFunnel(): array
    {
        $registered = User::where('role', 'patient')->count();
        $withAppointment = User::where('role', 'patient')->whereHas('appointments')->count();

        return [
            'registered' => $registered,
            'withAppointment' => $withAppointment,
            'rate' => $registered > 0 ? round($withAppointment / $registered * 100, 1) : 0,
        ];
    }

    /**
     * Hombres vs. mujeres, tanto entre todos los registrados como entre los
     * que llegaron a agendar una cita — dos series para comparar si la
     * proporción cambia entre "se registra" y "agenda".
     */
    private function genderBreakdown(): array
    {
        $labels = ['male' => 'Hombres', 'female' => 'Mujeres', 'other' => 'Otro'];

        $registered = User::where('role', 'patient')
            ->whereNotNull('sex')
            ->selectRaw('sex, count(*) as total')
            ->groupBy('sex')
            ->pluck('total', 'sex');

        $withAppointment = User::where('role', 'patient')
            ->whereNotNull('sex')
            ->whereHas('appointments')
            ->selectRaw('sex, count(*) as total')
            ->groupBy('sex')
            ->pluck('total', 'sex');

        return [
            'labels' => array_values($labels),
            'registered' => collect($labels)->keys()->map(fn ($key) => $registered[$key] ?? 0)->all(),
            'withAppointment' => collect($labels)->keys()->map(fn ($key) => $withAppointment[$key] ?? 0)->all(),
        ];
    }

    /**
     * Top departamentos por cantidad de pacientes registrados (los 14 de
     * El Salvador, solo los que tienen al menos un registro).
     */
    private function departmentBreakdown(): array
    {
        $departmentLabels = collect(ElSalvadorLocations::all())->map(fn ($d) => $d['label']);

        $counts = User::where('role', 'patient')
            ->whereNotNull('department')
            ->selectRaw('department, count(*) as total')
            ->groupBy('department')
            ->orderByDesc('total')
            ->pluck('total', 'department');

        $rows = $counts->map(fn ($total, $key) => [
            'label' => $departmentLabels[$key] ?? $key,
            'total' => $total,
        ])->values();

        return [
            'labels' => $rows->pluck('label')->all(),
            'data' => $rows->pluck('total')->all(),
        ];
    }

    /**
     * Top 10 municipios con más registros (puede haber ~262 municipios en
     * total — mostrar todos saturaría el gráfico, así que se recorta a los
     * más relevantes).
     */
    private function municipalityBreakdown(): array
    {
        $rows = User::where('role', 'patient')
            ->whereNotNull('municipality')
            ->selectRaw('municipality, count(*) as total')
            ->groupBy('municipality')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return [
            'labels' => $rows->pluck('municipality')->all(),
            'data' => $rows->pluck('total')->all(),
        ];
    }

    /**
     * Citas aprobadas vs. canceladas por mes, últimos 6 meses (incluido el
     * actual) — dos series sobre el mismo eje de meses para un gráfico de
     * barras agrupadas.
     */
    private function appointmentsByMonthChart(): array
    {
        $months = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i)->startOfMonth());

        $approved = $this->countByMonth($months, AppointmentStatus::Approved);
        $cancelled = $this->countByMonth($months, AppointmentStatus::Cancelled);

        return [
            'labels' => $months->map(fn ($m) => ucfirst($m->translatedFormat('M Y')))->all(),
            'approved' => $approved,
            'cancelled' => $cancelled,
        ];
    }

    /**
     * Agrupa en PHP (no en SQL) a propósito: DATE_FORMAT es de MySQL y no
     * existe en SQLite (el motor que usa la suite de tests) — con el
     * volumen de citas de una consulta acotada a 6 meses, traer las filas y
     * agrupar con Carbon es más simple que mantener dos variantes de query
     * por motor de base de datos.
     */
    private function countByMonth($months, AppointmentStatus $status): array
    {
        $counts = Appointment::where('status', $status)
            ->where('created_at', '>=', $months->first())
            ->pluck('created_at')
            ->countBy(fn ($date) => $date->format('Y-m'));

        return $months->map(fn ($m) => $counts[$m->format('Y-m')] ?? 0)->all();
    }
}
