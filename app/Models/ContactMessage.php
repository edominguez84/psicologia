<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'preferred_contact',
        'custom_fields',
        'locale',
        'ip',
        'user_agent',
        'handled_at',
    ];

    protected $casts = [
        'handled_at' => 'datetime',
        'custom_fields' => 'array',
    ];
}
