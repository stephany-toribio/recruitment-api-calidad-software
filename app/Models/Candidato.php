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

    // Edad mínima para registrarse (la misma que valida el front en el registro)
    public const EDAD_MINIMA = 17;

    protected $table= 'candidatos';

    protected $fillable = [
        'usuario_id',
        'nombre',
        'apellidos',
        'dni',
        'fecha_nacimiento',
        'genero',
        'busca_primer_empleo',
        'celular',
    ];

    protected $casts = [
        'fecha_nacimiento'    => 'date',
        'busca_primer_empleo' => 'boolean',
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

    //Relacion con estudios (candidato_estudios)
    public function estudios(): HasMany
    {
        return $this->hasMany(CandidatoEstudio::class, 'candidato_id');
    }

    //Relacion con idiomas (candidato_idiomas)
    public function idiomas(): HasMany
    {
        return $this->hasMany(CandidatoIdioma::class, 'candidato_id');
    }

    //Relacion con experiencias laborales
    public function experiencias(): HasMany
    {
        return $this->hasMany(ExperienciaLaboral::class, 'candidato_id');
    }

    //Relacion con postulacion
    public function postulaciones():HasMany
    {
        return $this->hasMany(Postulacion::class, 'candidato_id');
    }

    public function vacantesGuardadas(): HasMany
    {
        return $this->hasMany(VacanteGuardada::class, 'candidato_id');
    }

};
