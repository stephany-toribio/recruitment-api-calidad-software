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
    // Solo cursos externos: únicamente área de estudio (sin país, institución, carrera, estado ni fechas).
    // Es exclusivo: no se combina con los demás tipos.
    public const SOLO_AREA       = ['Sin estudios formales'];

    // Máximo de estudios por candidato según el grupo de tipos 
    public const MAXIMOS_POR_GRUPO = [
        ['tipos' => ['Universitario'], 'maximo' => 3, 'descripcion' => 'carreras universitarias'],
        ['tipos' => ['Posgrado', 'Master', 'Doctorado'], 'maximo' => 5, 'descripcion' => 'posgrados (posgrado, maestría y doctorado)'],
        ['tipos' => ['Terciario'], 'maximo' => 1, 'descripcion' => 'estudio terciario'],
        ['tipos' => ['Secundario'], 'maximo' => 1, 'descripcion' => 'estudio secundario'],
        ['tipos' => ['Sin estudios formales'], 'maximo' => 3, 'descripcion' => 'áreas sin estudios formales'],
    ];

    protected $table = 'tipos_estudio';

    protected $fillable = [
        'nombre',
    ];

    
    public function requiereCarrera(): bool
    {
        return in_array($this->nombre, self::CON_CARRERA, true);
    }

    public function soloArea(): bool
    {
        return in_array($this->nombre, self::SOLO_AREA, true);
    }

    public function carreras(): HasMany
    {
        return $this->hasMany(Carrera::class, 'tipo_estudio_id');
    }

    public function candidatoEstudios(): HasMany
    {
        return $this->hasMany(CandidatoEstudio::class, 'tipo_estudio_id');
    }
}
