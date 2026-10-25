<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevisionVacante extends Model
{
    use HasFactory;

    public const APROBADA = 'aprobada';

    public const NO_APROBADA = 'no_aprobada';

    public const PENDIENTE = 'pendiente';

    protected $table = 'revisiones_vacante';

    protected $fillable = [
        'empleo_id',
        'admin_id',
        'decision',
        'motivo',
        'invalidada_en',
    ];

    protected function casts(): array
    {
        return [
            'invalidada_en' => 'datetime',
        ];
    }

    public function empleo(): BelongsTo
    {
        return $this->belongsTo(Empleo::class, 'empleo_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'admin_id');
    }

    public function estaAprobada(): bool
    {
        return $this->decision === self::APROBADA;
    }

    public static function estadoDe(?self $revision): string
    {
        return $revision?->decision ?? self::PENDIENTE;
    }
}
