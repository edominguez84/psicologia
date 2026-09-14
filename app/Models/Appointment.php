<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Appointment extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'user_id', 'appointment_slot_id', 'patient_note',
        // status/admin_note/decided_at/decided_by NUNCA por mass-assignment:
        // se setean explícitamente en Admin\AppointmentController tras
        // decidir (aprobar/rechazar), o en el propio modelo al cancelar.
    ];

    protected $casts = [
        'status' => AppointmentStatus::class,
        'decided_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function appointmentSlot(): BelongsTo
    {
        return $this->belongsTo(AppointmentSlot::class);
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', AppointmentStatus::Pending);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'admin_note', 'decided_at', 'decided_by'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
