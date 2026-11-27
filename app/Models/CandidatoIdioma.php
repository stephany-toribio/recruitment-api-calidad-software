<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidatoIdioma extends Model
{
    use HasFactory;

    protected $table = 'candidato_idiomas';

    protected $fillable = [
        'candidato_id',
        'idioma_id',
        'nivel_escrito',
        'nivel_oral',
    ];

    public function candidato(): BelongsTo
    {
        return $this->belongsTo(Candidato::class, 'candidato_id');
    }

    public function idioma(): BelongsTo
    {
        return $this->belongsTo(Idioma::class, 'idioma_id');
    }
}
