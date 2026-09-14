<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Testimonial extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'user_id', 'text',
        // is_approved/approved_at/approved_by NUNCA por mass-assignment: se
        // setean explícitamente en Admin\TestimonialController tras revisar
        // el contenido, nunca desde el propio formulario del paciente.
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['text', 'is_approved', 'approved_at', 'approved_by'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
