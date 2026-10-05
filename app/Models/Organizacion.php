<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Organización mencionada en la experiencia laboral. No es una Empresa registrada en el portal.
class Organizacion extends Model
{
    use HasFactory;

    protected $table = 'organizaciones';

    protected $fillable = [
        'nombre',
        'pais_id',
        'estado',
        'estado_catalogo',
    ];

    public function pais(): BelongsTo
    {
        return $this->belongsTo(Pais::class, 'pais_id');
    }

    public function experiencias(): HasMany
    {
        return $this->hasMany(ExperienciaLaboral::class, 'organizacion_id');
    }
}
