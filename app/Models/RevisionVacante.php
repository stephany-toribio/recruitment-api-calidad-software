<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Decisión del administrador sobre una vacante (HU-22).
 * Una vacante sin revisión vigente (invalidada_en NULL) está pendiente de revisión.
 * Si la empresa edita una vacante aprobada, esa aprobación se invalida y queda como historial.
 */
class RevisionVacante extends Model
{
    use HasFactory;

    // Valores de `decision`: Luis y Steph comparan contra estas constantes, no contra texto suelto
    public const APROBADA = 'aprobada';
    public const NO_APROBADA = 'no_aprobada';

    // No se guarda: es el estado de una vacante que todavía no tiene fila de revisión
    public const PENDIENTE = 'pendiente';

    protected $table = 'revisiones_vacante';

    // La decisión y el motivo no se modifican; solo se marca invalidada_en cuando la empresa edita la vacante
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

    // Usuario con rol admin que tomó la decisión
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'admin_id');
    }

    public function estaAprobada(): bool
    {
        return $this->decision === self::APROBADA;
    }

    // Estado de revisión de una vacante (estado.revision): 'pendiente' si no tiene revisión vigente
    public static function estadoDe(?self $revision): string
    {
        return $revision?->decision ?? self::PENDIENTE;
    }
}
