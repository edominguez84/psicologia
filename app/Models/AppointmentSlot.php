<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AppointmentSlot extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['starts_at', 'ends_at', 'is_active'];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
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
     * Slots que el paciente puede elegir: activos, futuros, y sin ninguna
     * cita en curso (pending o approved) — así nunca se le muestra un
     * horario ya ocupado. Rechazadas/canceladas no cuentan como "ocupado".
     */
    public function scopeAvailable($query)
    {
        return $query->active()->upcoming()->whereDoesntHave('appointments', function ($q) {
            $q->whereIn('status', [AppointmentStatus::Pending->value, AppointmentStatus::Approved->value]);
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['starts_at', 'ends_at', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
