<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    /**
     * Registro de auditoría: quién hizo qué y sobre qué. Exclusivo de
     * super_admin (ver el grupo de rutas en routes/admin.php).
     */
    public function index(Request $request): View
    {
        $activities = Activity::query()
            ->with(['causer', 'subject'])
            ->when($request->filled('user'), fn ($q) => $q->where('causer_id', $request->integer('user'))->where('causer_type', User::class))
            ->when($request->filled('log'), fn ($q) => $q->where('log_name', $request->string('log')))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.activity-log.index', [
            'activities' => $activities,
            'users' => User::orderBy('name')->get(['id', 'name']),
            'logNames' => Activity::query()->distinct()->pluck('log_name')->filter()->sort()->values(),
            'filters' => $request->only(['user', 'log']),
        ]);
    }
}
