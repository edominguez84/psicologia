<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChatbotLeadRequest;
use App\Models\ChatbotLead;

class ChatbotLeadController extends Controller
{
    public function store(StoreChatbotLeadRequest $request)
    {
        $data = $request->safe()->only(['name', 'email', 'phone', 'transcript']);

        ChatbotLead::create([
            ...$data,
            'ip'         => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);

        return response()->json(['ok' => true]);
    }
}
