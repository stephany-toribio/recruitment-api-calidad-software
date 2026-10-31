<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VacanteGuardada extends Model
{
    protected $table = 'vacantes_guardadas';

    protected $fillable = [
        'candidato_id',
        'empleo_id',
    ];

    protected function casts(): array
    {
        return [
            'candidato_id' => 'integer',
            'empleo_id' => 'integer',
        ];
    }

    public function candidato(): BelongsTo
    {
        return $this->belongsTo(Candidato::class, 'candidato_id');
    }

    public function empleo(): BelongsTo
    {
        return $this->belongsTo(Empleo::class, 'empleo_id')->withTrashed();
    }
}
