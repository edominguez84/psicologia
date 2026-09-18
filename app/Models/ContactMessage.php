<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ContactMessage extends Model
{
    use LogsActivity;

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
        // Solo se rellena cuando el mensaje viene del modal de "llamada
        // gratis" con un horario elegido — el formulario de contacto
        // general nunca lo manda.
        'call_slot_id',
    ];

    protected $casts = [
        'handled_at' => 'datetime',
        'custom_fields' => 'array',
    ];

    public function callSlot(): BelongsTo
    {
        return $this->belongsTo(CallSlot::class);
    }

    public function scopeUnhandled($query)
    {
        return $query->whereNull('handled_at');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['handled_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
