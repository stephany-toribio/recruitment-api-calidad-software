<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Candidato extends Model
{
    use HasFactory;

    protected $table= 'candidatos';

    protected $fillable = [
        'usuario_id',
        'nombre',
        'apellidos',
        'dni',
        'fecha_nacimiento',
        'genero',
        'nivel_educativo',
        'disponibilidad',
        'celular',
    ]; 

    protected $casts = [
        'fecha_nacimiento' => 'date'
    ];

    //Relacion con usuario
    public function usuario():BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    //Relacion con habiliaddes (candidato_habilidades)
    public function habilidades(): BelongsToMany
    {
        return $this->belongsToMany(Habilidad::class, 'candidato_habilidades')
                    ->withTimeStamps();

    }

    //Relacion con carreras
    public function carreras(): HasMany
    {
        return $this->hasMany(CandidatoCarrera::class, 'candidato_id');
    }

    //Relacion con postulacion
    public function postulaciones():HasMany
    {
        return $this->hasMany(Postulacion::class, 'candidato_id'); 
    }

};
