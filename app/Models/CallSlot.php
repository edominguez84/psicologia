<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Horario para la llamada gratuita de 15 min — catálogo separado de
 * AppointmentSlot (citas pagadas): la llamada gratis no exige cuenta ni
 * pago, así que no debe competir por el mismo cupo que las citas.
 */
class CallSlot extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['starts_at', 'ends_at', 'is_active'];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function contactMessages(): HasMany
    {
        return $this->hasMany(ContactMessage::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('starts_at', '>=', now());
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('starts_at');
    }

    /**
     * Horarios que se pueden ofrecer: activos, futuros, y que nadie haya
     * reservado ya (un mensaje de contacto con este call_slot_id). A
     * diferencia de AppointmentSlot::scopeAvailable(), aquí no hay estados
     * pending/approved que filtrar — basta con que ningún mensaje lo
     * referencie todavía.
     */
    public function scopeAvailable($query)
    {
        return $query->active()->upcoming()->whereDoesntHave('contactMessages');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['starts_at', 'ends_at', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
