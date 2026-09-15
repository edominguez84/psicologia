<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CallSlot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CallSlotController extends Controller
{
    public function index(): View
    {
        return view('admin.call-slots.index', [
            'slots' => CallSlot::withCount('contactMessages')->ordered()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ]);

        CallSlot::create([
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'is_active' => true,
        ]);

        return back()->with('status', 'Horario de llamada añadido.');
    }

    public function toggle(CallSlot $callSlot): RedirectResponse
    {
        $callSlot->update(['is_active' => ! $callSlot->is_active]);

        return back()->with('status', $callSlot->is_active ? 'Horario activado.' : 'Horario desactivado.');
    }

    public function destroy(CallSlot $callSlot): RedirectResponse
    {
        $callSlot->delete();

        return back()->with('status', 'Horario eliminado.');
    }
}
