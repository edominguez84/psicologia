<?php

namespace App\Support;

use App\Models\ChatbotConversation;
use App\Models\ChatbotLead;

/**
 * Flujo de captura de nombre/correo/teléfono en modo FAQ (sin IA activa) —
 * ver ChatbotConversation::faq_capture_step/faq_capture_data. Sin IA no hay
 * forma de "conversar" para extraer estos datos con lenguaje natural, así
 * que se pide un dato a la vez, en orden fijo (nombre -> correo -> teléfono),
 * igual que el flujo por pasos de ChatbotWidget.vue, pero por texto libre.
 *
 * Regla de negocio confirmada: en este modo el sistema NUNCA agenda una cita
 * ni una llamada — solo captura el contacto como App\Models\ChatbotLead y
 * promete seguimiento posterior. Solo la IA (ChatbotAiService, tools
 * get_available_slots/book_appointment) puede agendar de verdad.
 */
class FaqLeadCapture
{
    /**
     * ¿Ya se le pidió o se le está pidiendo algo a este chat? (evita volver
     * a arrancar el flujo de captura si ya está en curso o ya terminó.)
     */
    public static function isActiveOrDone(ChatbotConversation $conversation): bool
    {
        return $conversation->faq_capture_step !== null || $conversation->lead_captured;
    }

    /**
     * Arranca el flujo pidiendo el primer dato (nombre). Devuelve el texto a
     * enviar.
     */
    public static function start(ChatbotConversation $conversation): string
    {
        $conversation->update(['faq_capture_step' => 'name', 'faq_capture_data' => []]);

        return 'Para darte seguimiento, ¿me compartes tu nombre completo?';
    }

    /**
     * Procesa la respuesta del paciente al paso actual del flujo. Devuelve
     * el siguiente mensaje a enviar (la siguiente pregunta, o la
     * confirmación final una vez guardado el lead).
     */
    public static function handle(ChatbotConversation $conversation, string $message): string
    {
        $data = $conversation->faq_capture_data ?? [];
        $step = $conversation->faq_capture_step;

        return match ($step) {
            'name' => self::handleName($conversation, $data, $message),
            'email' => self::handleEmail($conversation, $data, $message),
            'phone' => self::handlePhone($conversation, $data, $message),
            default => self::start($conversation),
        };
    }

    private static function handleName(ChatbotConversation $conversation, array $data, string $message): string
    {
        $name = trim($message);
        if ($name === '') {
            return '¿Me compartes tu nombre completo, por favor?';
        }

        $data['name'] = $name;
        $conversation->update(['faq_capture_step' => 'email', 'faq_capture_data' => $data]);

        return "Gracias, {$name}. ¿A qué correo electrónico te puedo escribir?";
    }

    private static function handleEmail(ChatbotConversation $conversation, array $data, string $message): string
    {
        $email = trim($message);
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Ese correo no parece válido — ¿me lo confirmas de nuevo?';
        }

        $data['email'] = $email;
        $conversation->update(['faq_capture_step' => 'phone', 'faq_capture_data' => $data]);

        return '¿Y un número de teléfono de contacto? (si prefieres no darlo, escribe "no")';
    }

    private static function handlePhone(ChatbotConversation $conversation, array $data, string $message): string
    {
        $phone = trim($message);
        $skip = in_array(mb_strtolower($phone), ['no', 'ninguno', 'prefiero no darlo', 'paso'], true);
        $data['phone'] = $skip ? null : $phone;

        ChatbotLead::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'transcript' => [[
                'from' => 'system',
                'text' => "Capturado en modo preguntas frecuentes ({$conversation->channel} #{$conversation->external_chat_id}).",
            ]],
        ]);

        $conversation->update(['faq_capture_step' => null, 'lead_captured' => true]);

        return '¡Listo, gracias! Guardé tus datos — te daremos seguimiento a tu consulta por ese correo o teléfono en cuanto podamos.';
    }
}
