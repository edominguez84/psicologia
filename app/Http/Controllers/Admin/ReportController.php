<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\ContactMessage;
use App\Models\EmotionalCheckup;
use App\Models\Role;
use App\Models\Testimonial;
use App\Models\User;
use App\Support\SystemLogReader;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Vista previa del informe (mismo contenido que el PDF), exclusiva de
     * super_admin, con el botón de descarga.
     */
    public function index(): View
    {
        return view('admin.reports.index', $this->reportData());
    }

    public function downloadPdf(): Response
    {
        $pdf = Pdf::loadView('admin.reports.pdf', $this->reportData())->setPaper('letter');

        return $pdf->download('informe-sistema-'.now()->format('Y-m-d').'.pdf');
    }

    /**
     * Métricas + errores recientes del log, compartidos entre la vista
     * previa en pantalla y el documento PDF.
     */
    private function reportData(): array
    {
        return [
            'generatedAt' => now(),
            'appointmentsByStatus' => collect(AppointmentStatus::cases())
                ->mapWithKeys(fn ($status) => [$status->label() => Appointment::where('status', $status)->count()]),
            'unhandledContacts' => ContactMessage::whereNull('handled_at')->count(),
            'totalContacts' => ContactMessage::count(),
            'pendingTestimonials' => Testimonial::where('is_approved', false)->count(),
            'approvedTestimonials' => Testimonial::where('is_approved', true)->count(),
            'usersByRole' => Role::all()
                ->mapWithKeys(fn ($role) => [$role->name => User::where('role', $role->slug)->count()]),
            'bannedUsers' => User::whereNotNull('banned_at')->count(),
            'checkupsThisMonth' => EmotionalCheckup::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'errorEntries' => SystemLogReader::errorEntries(500),
        ];
    }
}
