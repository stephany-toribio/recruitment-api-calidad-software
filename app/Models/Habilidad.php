<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Habilidad extends Model
{
    use HasFactory;

    protected $table = 'habilidades';

    protected $fillable = [
        'area_estudio_id',
        'nombre',
        'tipo_habilidad',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function areaEstudio(): BelongsTo
    {
        return $this->belongsTo(AreaEstudio::class, 'area_estudio_id');
    }

    public function scopeSeleccionables(
        Builder $query,
        array $areas = []
    ): Builder {
        return $query
            ->where('habilidades.activo', true)
            ->where(function (Builder $query) use ($areas): void {
                $query
                    ->where('habilidades.tipo_habilidad', 'blanda')
                    ->orWhere(function (Builder $query) use ($areas): void {
                        $query
                            ->where('habilidades.tipo_habilidad', 'tecnica')
                            ->when(
                                $areas,
                                fn (Builder $query) => $query->whereIn(
                                    'habilidades.area_estudio_id',
                                    $areas
                                )
                            )
                            ->whereHas(
                                'areaEstudio',
                                fn (Builder $query) => $query->where('activo', true)
                            );
                    });
            });
    }
}
