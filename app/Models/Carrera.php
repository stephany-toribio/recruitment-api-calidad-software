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
        'nombre',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function tipoEstudio(): BelongsTo
    {
        return $this->belongsTo(TipoEstudio::class, 'tipo_estudio_id');
    }

    public function candidatoEstudios(): HasMany
    {
        return $this->hasMany(CandidatoEstudio::class, 'carrera_id');
    }
}
