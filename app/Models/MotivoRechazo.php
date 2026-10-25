<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MotivoRechazo extends Model
{
    protected $table = 'motivos_rechazo';

    protected $fillable = [
        'motivo',
    ];
}
