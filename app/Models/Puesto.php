<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Puesto extends Model
{
    use HasFactory;

    protected $table = 'puestos';

    // estado: visible o no para el usuario. estado_catalogo: propuesto por un candidato (pendiente) o validado (aprobado).
    protected $fillable = [
        'nombre',
        'estado',
        'estado_catalogo',
    ];

    public function experiencias(): HasMany
    {
        return $this->hasMany(ExperienciaLaboral::class, 'puesto_id');
    }
}
