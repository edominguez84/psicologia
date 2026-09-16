<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Manual del sistema descargable en PDF: explica la dinámica entre roles
 * (super_admin/admin/paciente) para llevar una cita de principio a fin.
 * Contenido estático (no datos de negocio en vivo, a diferencia de
 * Admin\ReportController) — mismo mecanismo de generación (dompdf) que el
 * informe del sistema.
 */
class SystemManualController extends Controller
{
    public function index(): View
    {
        return view('admin.system-manual.index');
    }

    public function downloadPdf(): Response
    {
        $pdf = Pdf::loadView('admin.system-manual.pdf')->setPaper('letter');

        return $pdf->download('manual-del-sistema.pdf');
    }
}
