<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialPostulacion extends Model
{
    protected $table = 'historial_postulaciones';

    protected $fillable = [
        'postulacion_id',
        'estado_postulacion_id',
        'motivo_rechazo_id',
        'fecha_cambio',
    ];

    protected function casts(): array
    {
        return [
            'fecha_cambio' => 'datetime',
        ];
    }

    public function postulacion(): BelongsTo
    {
        return $this->belongsTo(Postulacion::class, 'postulacion_id');
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(EstadoPostulacion::class, 'estado_postulacion_id');
    }

    public function motivoRechazo(): BelongsTo
    {
        return $this->belongsTo(MotivoRechazo::class, 'motivo_rechazo_id');
    }
}
