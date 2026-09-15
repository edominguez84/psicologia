<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactRequest;
use App\Mail\ContactReceived;
use App\Models\ContactMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function store(StoreContactRequest $request)
    {
        $data = $request->safe()->only([
            'name', 'email', 'phone', 'subject', 'message', 'preferred_contact', 'custom_fields', 'call_slot_id',
        ]);

        $message = ContactMessage::create([
            ...$data,
            'locale'     => app()->getLocale(),
            'ip'         => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);

        // Email de aviso a la psicóloga (no bloquea la respuesta si falla).
        try {
            $to = config('site.contact.email');
            if ($to && filter_var($to, FILTER_VALIDATE_EMAIL)) {
                Mail::to($to)->send(new ContactReceived($message));
            }
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar el email de contacto: '.$e->getMessage());
        }

        return response()->json([
            'ok'      => true,
            'message' => 'Gracias por escribir. Te responderé lo antes posible, normalmente el mismo día.',
        ]);
    }
}
