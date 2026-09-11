<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\EmotionalCheckup;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', [
            'unhandledContacts' => ContactMessage::whereNull('handled_at')->count(),
            'totalContacts' => ContactMessage::count(),
            'totalCheckups' => EmotionalCheckup::count(),
            'recentCheckups' => EmotionalCheckup::latest()->limit(5)->get(),
        ]);
    }
}
