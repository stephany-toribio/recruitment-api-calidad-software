<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificacionPostulacion extends Model
{
    public const CONTRATADO = 'contratado';

    public const RECHAZADO = 'rechazado';

    public const ENVIADO = 'enviado';

    public const FALLIDO = 'fallido';

    protected $table = 'notificaciones_postulacion';

    protected $fillable = [
        'postulacion_id',
        'tipo',
        'destinatario',
        'resultado',
        'error',
    ];

    public function postulacion(): BelongsTo
    {
        return $this->belongsTo(Postulacion::class, 'postulacion_id');
    }
}
