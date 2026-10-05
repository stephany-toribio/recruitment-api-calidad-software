<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AreaEstudio extends Model
{
    use HasFactory;

    protected $table = 'areas_estudio';

    protected $fillable = [
        'nombre',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function habilidades(): HasMany
    {
        return $this->hasMany(Habilidad::class, 'area_estudio_id');
    }

    public function carreras(): HasMany
    {
        return $this->hasMany(Carrera::class, 'area_estudio_id');
    }
}
