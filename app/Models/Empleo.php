<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Vacante publicada por una empresa (tabla empleos; en la API se llama "vacante").
 * Su aprobación vive en revisiones_vacante y su pago en pagos_vacante (módulo de Gian):
 * "publicada" no se guarda, es aprobada + un pago exitoso.
 */
class Empleo extends Model
{
    use HasFactory, SoftDeletes;

    // Niveles de experiencia de HU-03: código guardado => nombre para mostrar. Nadie copia esta lista.
    public const NIVELES = [
        'junior' => 'Junior',
        'semi_senior' => 'Semi-Senior',
        'senior' => 'Senior',
    ];

    // Valores de estado_empleo: disponibilidad que decide la empresa (HU-14). Eliminada = deleted_at.
    public const ACTIVA = 'activa';
    public const PAUSADA = 'pausada';
    public const FINALIZADA = 'finalizada';

    // No se guarda en estado_empleo: es la disponibilidad de una vacante con deleted_at
    public const ELIMINADA = 'eliminada';

    // La vigencia se cuenta por días completos en hora de Lima (la app está en UTC)
    public const ZONA_VIGENCIA = 'America/Lima';

    protected $table = 'empleos';

    // numero_vacantes = número máximo de contrataciones (en la API: max_contrataciones)
    protected $fillable = [
        'empresa_id',
        'titulo',
        'descripcion',
        'nivel_experiencia',
        'salario_min',
        'salario_max',
        'fecha_inicio_vigencia',
        'fecha_fin_vigencia',
        'estado_empleo',
        'tipo_empleo',
        'numero_vacantes',
    ];

    protected function casts(): array
    {
        return [
            'salario_min' => 'decimal:2',
            'salario_max' => 'decimal:2',
            'fecha_inicio_vigencia' => 'date',
            'fecha_fin_vigencia' => 'date',
            'numero_vacantes' => 'integer',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    // tipo_requisito: must_have (obligatoria) o nice_to_have (deseable)
    public function habilidades(): BelongsToMany
    {
        return $this->belongsToMany(Habilidad::class, 'empleo_habilidades', 'empleo_id', 'habilidad_id')
            ->withPivot('tipo_requisito')
            ->withTimestamps();
    }

    // Revisión vigente: la que no fue invalidada por una edición. Sin revisión vigente = pendiente de revisión
    public function revision(): HasOne
    {
        return $this->hasOne(RevisionVacante::class, 'empleo_id')->whereNull('invalidada_en');
    }

    // Historial de decisiones, incluidas las aprobaciones invalidadas por una edición
    public function revisiones(): HasMany
    {
        return $this->hasMany(RevisionVacante::class, 'empleo_id');
    }

    // Intentos de pago simulado; la vacante está pagada si alguno fue exitoso
    public function pagos(): HasMany
    {
        return $this->hasMany(PagoVacante::class, 'empleo_id');
    }

    // El modelo Postulacion lo crea Steph (solo lectura) mientras no se reparta HU-05
    public function postulaciones(): HasMany
    {
        return $this->hasMany(Postulacion::class, 'empleo_id');
    }

    // HU-06: el Kanban se puede gestionar mientras la vacante no esté finalizada ni eliminada
    public function permiteGestionKanban(): bool
    {
        return ! $this->trashed() && $this->estado_empleo !== self::FINALIZADA;
    }

    // estado.vigente: hoy (en Lima) está entre la fecha de inicio y la de fin, ambas incluidas
    public function estaVigente(): bool
    {
        $hoy = $this->hoy();

        return $this->fecha_inicio_vigencia->toDateString() <= $hoy
            && $hoy <= $this->fecha_fin_vigencia->toDateString();
    }

    // estado.disponibilidad: activa, pausada o finalizada; eliminada si tiene deleted_at
    public function estadoDisponibilidad(): string
    {
        return $this->trashed() ? self::ELIMINADA : $this->estado_empleo;
    }

    // estado.publicada_en: fecha del pago exitoso (publicar es aprobar + pagar)
    public function publicadaEn(): ?Carbon
    {
        return $this->pagos->firstWhere('resultado', PagoVacante::EXITOSO)?->created_at;
    }

    /**
     * estado.general: etiqueta que ve la empresa. Se aplica la primera regla que se cumpla,
     * en el orden acordado en EP2_distribucion.md.
     */
    public function estadoGeneral(): string
    {
        $hoy = $this->hoy();
        $revision = RevisionVacante::estadoDe($this->revision);

        return match (true) {
            $this->trashed() => self::ELIMINADA,
            $this->estado_empleo === self::FINALIZADA => self::FINALIZADA,
            $revision === RevisionVacante::NO_APROBADA => 'no_aprobada',
            $revision === RevisionVacante::PENDIENTE => 'pendiente_revision',
            PagoVacante::estadoDe($this->pagos) !== PagoVacante::PAGADO => 'pendiente_pago',
            $this->estado_empleo === self::PAUSADA => self::PAUSADA,
            $hoy > $this->fecha_fin_vigencia->toDateString() => 'vencida',
            $hoy < $this->fecha_inicio_vigencia->toDateString() => 'por_iniciar',
            default => 'publicada',
        };
    }

    // Fechas como texto Y-m-d: se comparan por día, sin que la hora o la zona de UTC las corran
    private function hoy(): string
    {
        return now(self::ZONA_VIGENCIA)->toDateString();
    }
}
