<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Carrera extends Model
{
    use HasFactory;

    protected $table = 'carreras';

    protected $fillable = [
        'nombre',
    ];

    public function candidatoCarreras(): HasMany
    {
        return $this->hasMany(CandidatoCarrera::class, 'carrera_id');
    }
}
