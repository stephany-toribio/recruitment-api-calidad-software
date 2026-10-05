<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoEstudio extends Model
{
    use HasFactory;

    // Reglas del estudio según su tipo 
    public const CON_CARRERA     = ['Universitario', 'Posgrado', 'Master', 'Doctorado']; 
    public const SIN_INSTITUCION = ['Secundario', 'Terciario'];                          

    protected $table = 'tipos_estudio';

    protected $fillable = [
        'nombre',
    ];

    public function carreras(): HasMany
    {
        return $this->hasMany(Carrera::class, 'tipo_estudio_id');
    }

    public function candidatoEstudios(): HasMany
    {
        return $this->hasMany(CandidatoEstudio::class, 'tipo_estudio_id');
    }
}
