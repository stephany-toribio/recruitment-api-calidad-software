<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExperienciaLaboral extends Model
{
    use HasFactory;

    protected $table = 'experiencias_laborales';

    protected $fillable = [
        'candidato_id',
        'puesto_id',
        'organizacion_id',
        'pais_id',
        'nivel_puesto_id',
        'fecha_inicio',
        'fecha_fin',
        'descripcion',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
    ];

    public function candidato(): BelongsTo
    {
        return $this->belongsTo(Candidato::class, 'candidato_id');
    }

    public function puesto(): BelongsTo
    {
        return $this->belongsTo(Puesto::class, 'puesto_id');
    }

    public function organizacion(): BelongsTo
    {
        return $this->belongsTo(Organizacion::class, 'organizacion_id');
    }

    public function nivelPuesto(): BelongsTo
    {
        return $this->belongsTo(NivelPuesto::class, 'nivel_puesto_id');
    }

    public function pais(): BelongsTo
    {
        return $this->belongsTo(Pais::class, 'pais_id');
    }
}
