<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidatoCarrera extends Model
{
    use HasFactory;

    protected $table = 'candidato_carreras';

    protected $fillable = [
        'candidato_id',
        'carrera_id',
        'area',
        'institucion',
        'estado',
        'ciclo_actual',
        'fecha_inicio',
        'fecha_fin',
    ];

    protected $casts = [
        'ciclo_actual' => 'integer',
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
    ];

    /**
     * Relación con el Candidato.
     */
    public function candidato(): BelongsTo
    {
        return $this->belongsTo(Candidato::class, 'candidato_id');
    }

    /**
     * Relación con la Carrera.
     */
    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class, 'carrera_id');
    }
}
