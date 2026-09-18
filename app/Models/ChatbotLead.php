<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotLead extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'email', 'phone', 'transcript', 'ip', 'user_agent',
    ];

    protected $casts = [
        'transcript' => 'array',
        'handled_at' => 'datetime',
    ];

    public function scopeUnhandled($query)
    {
        return $query->whereNull('handled_at');
    }
}
