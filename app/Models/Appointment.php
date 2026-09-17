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
        'payment_method', 'amount', 'promotion_id',
        // status/admin_note/decided_at/decided_by NUNCA por mass-assignment:
        // se setean explícitamente en Admin\AppointmentController tras
        // decidir (aprobar/rechazar), o en el propio modelo al cancelar.
        // payment_status/payment_reference tampoco: se setean explícitamente
        // (el paciente nunca puede marcar su propio pago como "confirmed").
        // vapi_call_* tampoco: los setea App\Services\VapiCallService y
        // Webhooks\VapiWebhookController, nunca a partir de un request del
        // paciente.
    ];

    protected $casts = [
        'status' => AppointmentStatus::class,
        'decided_at' => 'datetime',
        'amount' => 'decimal:2',
        'vapi_called_at' => 'datetime',
        'vapi_call_result' => 'array',
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

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', AppointmentStatus::Pending);
    }

    /**
     * Citas aprobadas cuyo horario cae dentro de la ventana de aviso
     * configurada (site_settings.vapi.hours_before) y que todavía no
     * recibieron la llamada automática de VAPI — usado por
     * App\Console\Commands\SendAppointmentCallReminders. $windowStart y
     * $windowEnd delimitan ese rango de starts_at.
     */
    public function scopeReadyForVapiCall($query, \DateTimeInterface $windowStart, \DateTimeInterface $windowEnd)
    {
        return $query->where('status', AppointmentStatus::Approved)
            ->whereNull('vapi_call_status')
            ->whereHas('appointmentSlot', function ($slotQuery) use ($windowStart, $windowEnd) {
                $slotQuery->whereBetween('starts_at', [$windowStart, $windowEnd]);
            });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'admin_note', 'decided_at', 'decided_by', 'payment_status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
