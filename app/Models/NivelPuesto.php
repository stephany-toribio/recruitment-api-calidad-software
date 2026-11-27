<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Nivel del puesto en la experiencia laboral (Trainee, Junior, Senior...)
class NivelPuesto extends Model
{
    use HasFactory;

    protected $table = 'niveles_puesto';

    // candidato_id: NULL = catálogo global; con valor = personalizado del candidato que lo escribió (no lo ven otros).
    // orden: jerarquía de los globales (los personalizados no tienen). estado: habilitado o deshabilitado por el admin.
    protected $fillable = [
        'candidato_id',
        'nombre',
        'orden',
        'estado',
    ];

    protected $casts = [
        'orden' => 'integer',
    ];

    public function esPersonalizado(): bool
    {
        return $this->candidato_id !== null;
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(Candidato::class, 'candidato_id');
    }

    public function experiencias(): HasMany
    {
        return $this->hasMany(ExperienciaLaboral::class, 'nivel_puesto_id');
    }
}
