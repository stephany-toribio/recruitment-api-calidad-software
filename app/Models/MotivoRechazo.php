<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MotivoRechazo extends Model
{
    public const PERMITIDOS = [
        'Perfil no se ajusta',
        'Expectativa salarial alta',
        'Proceso cerrado',
    ];

    protected $table = 'motivos_rechazo';

    protected $fillable = [
        'motivo',
    ];
}