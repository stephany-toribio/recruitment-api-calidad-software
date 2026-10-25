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

            return $vacante->load([
                'habilidades',
                'revision',
                'pagos',
            ]);
        });
    }

    public function detalle(Empresa $empresa, Empleo $vacante): Empleo
    {
        $this->verificarPropiedad($empresa, $vacante);

        return $vacante->load([
            'habilidades',
            'revision',
            'pagos',
        ]);
    }

    /**
     * Se puede editar mientras no esté pagada. Si estaba aprobada, vuelve a pendiente de revisión
     * y no se puede pagar hasta que el admin la apruebe otra vez.
     */
    public function actualizar(Empresa $empresa, Empleo $vacante, array $datos): Empleo
    {
        return DB::transaction(function () use ($empresa, $vacante, $datos) {
            // Mismo bloqueo que la aprobación y el pago: una edición no se cruza con ellos
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

            // La no aprobación es definitiva (HU-22)
            if ($revision === RevisionVacante::NO_APROBADA) {
                throw new BusinessException(
                    'La vacante no fue aprobada y no se puede editar.',
                    409,
                    'VACANCY_NOT_EDITABLE'
                );
            }

            $bloqueada->update($this->atributos($datos));
            $bloqueada->habilidades()->sync($this->habilidades($datos));

            // revisiones_vacante es de Gian: se usa su servicio en lugar de escribir aquí
            if ($revision === RevisionVacante::APROBADA) {
                $this->revisionService->reabrirRevision($bloqueada);
            }

            return $bloqueada->load([
                'habilidades',
                'revision',
                'pagos',
            ]);
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

    // Campos que la empresa registra o edita (max_contrataciones se guarda en numero_vacantes)
    private function atributos(array $datos): array
    {
        return [
            'titulo' => $datos['titulo'],
            'descripcion' => $datos['descripcion'],
            'nivel_experiencia' => $datos['nivel_experiencia'],
            'salario_min' => $datos['salario_min'],
            'salario_max' => $datos['salario_max'],
            'fecha_inicio_vigencia' => $datos['fecha_inicio_vigencia'],
            'fecha_fin_vigencia' => $datos['fecha_fin_vigencia'],
            'numero_vacantes' => $datos['max_contrataciones'],
        ];
    }

    // Formato del pivote empleo_habilidades: [habilidad_id => ['tipo_requisito' => ...]]
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
