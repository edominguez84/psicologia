<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SiteSetting extends Model
{
    use LogsActivity;

    protected $fillable = ['key', 'value'];

    protected $casts = [
        'value' => 'array',
    ];

    /**
     * Solo se registra qué 'key' cambió (apariencia, contenido, contacto,
     * etc.), no el JSON completo de 'value' — este puede ser grande y su
     * diff no es legible en la vista de auditoría; el propio panel donde se
     * edita cada sección ya permite ver el valor actual.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['key'])
            ->dontSubmitEmptyLogs();
    }
}
