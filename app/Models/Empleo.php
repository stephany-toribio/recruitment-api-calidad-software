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

class Empleo extends Model
{
    use HasFactory, SoftDeletes;

    public const ACTIVA = 'activa';

    public const PAUSADA = 'pausada';

    public const FINALIZADA = 'finalizada';

    public const ELIMINADA = 'eliminada';

    public const ZONA_VIGENCIA = 'America/Lima';

    protected $table = 'empleos';

    protected $fillable = [
        'empresa_id',
        'titulo',
        'descripcion',
        'requisitos',
        'beneficios',
        'nivel_puesto_id',
        'area_estudio_id',
        'tipo_empleo',
        'ubicacion',
        'salario_min',
        'salario_max',
        'fecha_inicio_vigencia',
        'fecha_fin_vigencia',
        'estado_empleo',
        'numero_vacantes',
    ];

    protected $hidden = [
        'nivel_puesto_dueno_id',
    ];

    protected function casts(): array
    {
        return [
            'requisitos' => 'array',
            'beneficios' => 'array',
            'nivel_puesto_id' => 'integer',
            'area_estudio_id' => 'integer',
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

    public function nivelPuesto(): BelongsTo
    {
        return $this->belongsTo(NivelPuesto::class, 'nivel_puesto_id');
    }

    public function areaEstudio(): BelongsTo
    {
        return $this->belongsTo(AreaEstudio::class, 'area_estudio_id');
    }

    public function habilidades(): BelongsToMany
    {
        return $this->belongsToMany(
            Habilidad::class,
            'empleo_habilidades',
            'empleo_id',
            'habilidad_id'
        )
            ->withPivot('tipo_requisito')
            ->withTimestamps();
    }

    public function revision(): HasOne
    {
        return $this->hasOne(RevisionVacante::class, 'empleo_id')->whereNull('invalidada_en');
    }

    public function revisiones(): HasMany
    {
        return $this->hasMany(RevisionVacante::class, 'empleo_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(PagoVacante::class, 'empleo_id');
    }

    public function postulaciones(): HasMany
    {
        return $this->hasMany(Postulacion::class, 'empleo_id');
    }

    public function permiteGestionKanban(): bool
    {
        return ! $this->trashed()
            && $this->estado_empleo !== self::FINALIZADA;
    }

    public function estaVigente(): bool
    {
        $hoy = $this->hoy();

        return $this->fecha_inicio_vigencia->toDateString() <= $hoy
            && $hoy <= $this->fecha_fin_vigencia->toDateString();
    }

    public function estadoDisponibilidad(): string
    {
        return $this->trashed()
            ? self::ELIMINADA
            : $this->estado_empleo;
    }

    public function publicadaEn(): ?Carbon
    {
        return $this->pagos
            ->where('resultado', PagoVacante::EXITOSO)
            ->sortBy('created_at')
            ->first()
            ?->created_at;
    }

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

    private function hoy(): string
    {
        return now(self::ZONA_VIGENCIA)->toDateString();
    }
}
