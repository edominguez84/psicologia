<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCheckupRequest;
use App\Models\EmotionalCheckup;

class CheckupController extends Controller
{
    public function store(StoreCheckupRequest $request)
    {
        $answers = array_map('intval', array_values($request->validated('answers')));
        $score   = array_sum($answers);
        $band    = EmotionalCheckup::bandFor($score);

        EmotionalCheckup::create([
            'q1'         => $answers[0],
            'q2'         => $answers[1],
            'q3'         => $answers[2],
            'q4'         => $answers[3],
            'q5'         => $answers[4],
            'score'      => $score,
            'band'       => $band,
            'email'      => $request->validated('email'),
            'ip'         => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);

        $result = config("site.checkup.results.$band");

        return response()->json([
            'ok'     => true,
            'score'  => $score,
            'max'    => 15,
            'band'   => $band,
            'result' => $result,
        ]);
    }
}
