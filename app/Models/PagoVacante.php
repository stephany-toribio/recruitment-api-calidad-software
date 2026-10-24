<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

// Intento de pago simulado de una vacante (HU-03). La vacante está pagada si tiene un intento 'exitoso'.
class PagoVacante extends Model
{
    use HasFactory;

    // Valores de `resultado`
    public const EXITOSO = 'exitoso';
    public const FALLIDO = 'fallido';
    public const CANCELADO = 'cancelado';

    // Estados de pago de la vacante que no se guardan: se calculan con estadoDe()
    public const SIN_PAGO = 'sin_pago';
    public const PAGADO = 'pagado';

    protected $table = 'pagos_vacante';

    // monto lo calcula el servidor; de la tarjeta solo se guardan los últimos 4 dígitos
    protected $fillable = [
        'empleo_id',
        'resultado',
        'monto',
        'moneda',
        'tarjeta_ultimos4',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
        ];
    }

    public function empleo(): BelongsTo
    {
        return $this->belongsTo(Empleo::class, 'empleo_id');
    }

    public function fueExitoso(): bool
    {
        return $this->resultado === self::EXITOSO;
    }

    /**
     * Estado de pago de una vacante a partir de sus intentos (estado.pago):
     * 'pagado' si alguno fue exitoso; si no, el resultado del último intento; sin intentos, 'sin_pago'.
     */
    public static function estadoDe(Collection $pagos): string
    {
        if ($pagos->contains('resultado', self::EXITOSO)) {
            return self::PAGADO;
        }

        return $pagos->sortByDesc('id')->first()?->resultado ?? self::SIN_PAGO;
    }
}
