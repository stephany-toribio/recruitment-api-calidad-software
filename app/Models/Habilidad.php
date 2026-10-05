<?php

namespace App\Models;

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
}
