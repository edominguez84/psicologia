<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\EmotionalCheckup;

class MessagesController extends Controller
{
    public function index()
    {
        return view('admin.messages.index', [
            'contactMessages' => ContactMessage::latest()->paginate(15, ['*'], 'contactos'),
            'checkups' => EmotionalCheckup::latest()->paginate(15, ['*'], 'chequeos'),
        ]);
    }

    public function handleContact(ContactMessage $contactMessage)
    {
        $contactMessage->update([
            'handled_at' => $contactMessage->handled_at ? null : now(),
        ]);

        return back()->with('status', $contactMessage->handled_at ? 'Marcado como atendido.' : 'Marcado como pendiente.');
    }
}
