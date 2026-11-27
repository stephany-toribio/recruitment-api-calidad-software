<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidatoEstudio extends Model
{
    use HasFactory;

    protected $table = 'candidato_estudios';

    protected $fillable = [
        'candidato_id',
        'tipo_estudio_id',
        'area_estudio_id',
        'pais_id',
        'institucion_id',
        'carrera_id',
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

    public function candidato(): BelongsTo
    {
        return $this->belongsTo(Candidato::class, 'candidato_id');
    }

    public function tipoEstudio(): BelongsTo
    {
        return $this->belongsTo(TipoEstudio::class, 'tipo_estudio_id');
    }

    public function areaEstudio(): BelongsTo
    {
        return $this->belongsTo(AreaEstudio::class, 'area_estudio_id');
    }

    public function pais(): BelongsTo
    {
        return $this->belongsTo(Pais::class, 'pais_id');
    }

    public function institucion(): BelongsTo
    {
        return $this->belongsTo(Institucion::class, 'institucion_id');
    }

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class, 'carrera_id');
    }
}
