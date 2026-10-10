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

    // candidato_id: NULL = catálogo global; con valor = personalizada del candidato que la escribió (no la ven otros).
    // estado: habilitada o deshabilitada por el administrador.
    protected $fillable = [
        'candidato_id',
        'nombre',
        'pais_id',
        'estado',
    ];

    public function esPersonalizada(): bool
    {
        return $this->candidato_id !== null;
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(Candidato::class, 'candidato_id');
    }

    public function pais(): BelongsTo
    {
        return $this->belongsTo(Pais::class, 'pais_id');
    }

    public function experiencias(): HasMany
    {
        return $this->hasMany(ExperienciaLaboral::class, 'organizacion_id');
    }
}
