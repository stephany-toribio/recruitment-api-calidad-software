<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Puesto extends Model
{
    use HasFactory;

    protected $table = 'puestos';

    // candidato_id: NULL = catálogo global; con valor = personalizado del candidato que lo escribió (no lo ven otros).
    // estado: habilitado o deshabilitado por el administrador.
    protected $fillable = [
        'candidato_id',
        'nombre',
        'estado',
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
        return $this->hasMany(ExperienciaLaboral::class, 'puesto_id');
    }
}
