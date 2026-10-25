<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Postulacion extends Model
{
    protected $table = 'postulaciones';

    protected $fillable = [
        'candidato_id',
        'empleo_id',
        'estado_postulacion_id',
        'fecha_postulacion',
        'retirada_en',
    ];

    protected function casts(): array
    {
        return [
            'candidato_id' => 'integer',
            'empleo_id' => 'integer',
            'estado_postulacion_id' => 'integer',
            'porcentaje_match' => 'integer',
            'fecha_postulacion' => 'datetime',
            'retirada_en' => 'datetime',
        ];
    }

    public function candidato(): BelongsTo
    {
        return $this->belongsTo(Candidato::class, 'candidato_id');
    }

    // withTrashed: la postulación conserva su vacante aunque esta se haya eliminado
    public function empleo(): BelongsTo
    {
        return $this->belongsTo(Empleo::class, 'empleo_id')->withTrashed();
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(EstadoPostulacion::class, 'estado_postulacion_id');
    }

    public function historial(): HasMany
    {
        return $this->hasMany(HistorialPostulacion::class, 'postulacion_id');
    }

    public function notificaciones(): HasMany
    {
        return $this->hasMany(NotificacionPostulacion::class, 'postulacion_id');
    }

    public function scopeActivas(Builder $query): Builder
    {
        return $query->whereNull('retirada_en');
    }

    public function estaRetirada(): bool
    {
        return $this->retirada_en !== null;
    }

    public function esTerminal(): bool
    {
        return in_array($this->estado->nombre, EstadoPostulacion::TERMINALES, true);
    }

    public function puedeRetirarse(): bool
    {
        return ! $this->estaRetirada() && ! $this->esTerminal();
    }
}
