<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pais extends Model
{
    use HasFactory;

    protected $table = 'paises';

    protected $fillable = [
        'nombre',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function instituciones(): HasMany
    {
        return $this->hasMany(Institucion::class, 'pais_id');
    }

    public function organizaciones(): HasMany
    {
        return $this->hasMany(Organizacion::class, 'pais_id');
    }
}
