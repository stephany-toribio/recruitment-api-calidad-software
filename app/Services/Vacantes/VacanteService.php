<?php

namespace App\Services\Vacantes;

use App\Exceptions\BusinessException;
use App\Models\Empleo;
use App\Models\Empresa;
use App\Models\PagoVacante;
use App\Models\RevisionVacante;
use Illuminate\Support\Facades\DB;

class VacanteService
{
    private const RELACIONES = [
        'nivelPuesto',
        'areaEstudio',
        'habilidades',
        'revision',
        'pagos',
    ];

    public function __construct(
        private RevisionVacanteService $revisionService
    ) {
    }

    public function registrar(Empresa $empresa, array $datos): Empleo
    {
        return DB::transaction(function () use ($empresa, $datos) {
            $vacante = Empleo::create([
                'empresa_id' => $empresa->id,
                ...$this->atributos($datos),
                'estado_empleo' => Empleo::ACTIVA,
            ]);

            $vacante->habilidades()->attach($this->habilidades($datos));

            return $vacante->load(self::RELACIONES);
        });
    }

    public function detalle(Empresa $empresa, Empleo $vacante): Empleo
    {
        $this->verificarPropiedad($empresa, $vacante);

        return $vacante->load(self::RELACIONES);
    }

    public function actualizar(Empresa $empresa, Empleo $vacante, array $datos): Empleo
    {
        return DB::transaction(function () use ($empresa, $vacante, $datos) {
            $bloqueada = Empleo::withTrashed()
                ->whereKey($vacante->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->verificarPropiedad($empresa, $bloqueada);

            if ($bloqueada->trashed()) {
                throw new BusinessException(
                    'La vacante fue eliminada y no se puede modificar.',
                    409,
                    'VACANCY_DELETED'
                );
            }

            $bloqueada->load(['revision', 'pagos']);

            if (PagoVacante::estadoDe($bloqueada->pagos) === PagoVacante::PAGADO) {
                throw new BusinessException(
                    'La vacante ya fue pagada y no se puede editar.',
                    409,
                    'VACANCY_ALREADY_PAID'
                );
            }

            $revision = RevisionVacante::estadoDe($bloqueada->revision);

            if ($revision === RevisionVacante::NO_APROBADA) {
                throw new BusinessException(
                    'La vacante no fue aprobada y no se puede editar.',
                    409,
                    'VACANCY_NOT_EDITABLE'
                );
            }

            $bloqueada->update($this->atributos($datos));
            $bloqueada->habilidades()->sync($this->habilidades($datos));

            if ($revision === RevisionVacante::APROBADA) {
                $this->revisionService->reabrirRevision($bloqueada);
            }

            return $bloqueada->load(self::RELACIONES);
        });
    }

    private function verificarPropiedad(Empresa $empresa, Empleo $vacante): void
    {
        if ((int) $vacante->empresa_id !== (int) $empresa->id) {
            throw new BusinessException(
                'No tienes permisos para realizar esta acción.',
                403,
                'FORBIDDEN'
            );
        }
    }

    private function atributos(array $datos): array
    {
        return [
            'titulo' => $datos['titulo'],
            'descripcion' => $datos['descripcion'],
            'nivel_puesto_id' => $datos['nivel_puesto_id'],
            'area_estudio_id' => $datos['area_estudio_id'],
            'tipo_empleo' => $datos['tipo_empleo'],
            'ubicacion' => $datos['ubicacion'] ?? null,
            'salario_min' => $datos['salario_min'] ?? null,
            'salario_max' => $datos['salario_max'] ?? null,
            'fecha_inicio_vigencia' => $datos['fecha_inicio_vigencia'],
            'fecha_fin_vigencia' => $datos['fecha_fin_vigencia'],
            'numero_vacantes' => $datos['max_contrataciones'],
        ];
    }

    private function habilidades(array $datos): array
    {
        $habilidades = [];

        foreach ($datos['habilidades'] as $habilidad) {
            $habilidades[$habilidad['habilidad_id']] = [
                'tipo_requisito' => $habilidad['requisito'],
            ];
        }

        return $habilidades;
    }
}
