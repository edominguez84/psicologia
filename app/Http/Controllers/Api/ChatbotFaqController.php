<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatbotFaq;

class ChatbotFaqController extends Controller
{
    /**
     * Preguntas activas para mostrar como botones en el widget del chat,
     * en el orden que definió el super_admin.
     */
    public function index()
    {
        return response()->json(
            ChatbotFaq::active()->ordered()->get(['id', 'question', 'answer'])
        );
    }
}
