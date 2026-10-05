<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Carrera extends Model
{
    use HasFactory;

    protected $table = 'carreras';

    protected $fillable = [
        'tipo_estudio_id',
        'area_estudio_id',
        'pais_id',
        'nombre',
    ];

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

    public function candidatoEstudios(): HasMany
    {
        return $this->hasMany(CandidatoEstudio::class, 'carrera_id');
    }
}
