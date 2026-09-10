<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmotionalCheckup extends Model
{
    protected $fillable = [
        'q1', 'q2', 'q3', 'q4', 'q5',
        'score', 'band', 'email', 'ip', 'user_agent',
    ];

    /**
     * Clasifica una puntuación 0-15 en una banda orientativa.
     */
    public static function bandFor(int $score): string
    {
        return match (true) {
            $score <= 4 => 'bajo',
            $score <= 9 => 'medio',
            default => 'alto',
        };
    }
}
