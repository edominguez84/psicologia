<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppointmentSlot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppointmentSlotController extends Controller
{
    public function index(): View
    {
        return view('admin.appointment-slots.index', [
            'slots' => AppointmentSlot::withCount([
                'appointments as active_appointments_count' => fn ($q) => $q->whereIn('status', ['pending', 'approved']),
            ])->ordered()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ]);

        AppointmentSlot::create([
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'is_active' => true,
        ]);

        return back()->with('status', 'Horario añadido.');
    }

    public function toggle(AppointmentSlot $appointmentSlot): RedirectResponse
    {
        $appointmentSlot->update(['is_active' => ! $appointmentSlot->is_active]);

        return back()->with('status', $appointmentSlot->is_active ? 'Horario activado.' : 'Horario desactivado.');
    }

    public function destroy(AppointmentSlot $appointmentSlot): RedirectResponse
    {
        $appointmentSlot->delete();

        return back()->with('status', 'Horario eliminado.');
    }
}
