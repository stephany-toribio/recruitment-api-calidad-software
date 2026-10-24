<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Postulacion extends Model
{
    protected $table = 'postulaciones';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'candidato_id' => 'integer',
            'empleo_id' => 'integer',
            'estado_postulacion_id' => 'integer',
            'porcentaje_match' => 'integer',
            'fecha_postulacion' => 'datetime',
        ];
    }
}
