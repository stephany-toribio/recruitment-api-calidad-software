<?php

namespace App\Services\Vacantes;

use App\Models\Empleo;
use App\Models\PagoVacante;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class BusquedaVacantesService
{
    public const POR_PAGINA = 10;

    public function __construct(
        private DisponibilidadVacanteService $disponibilidad
    ) {}

    public function construirConsulta(array $filtros): Builder
    {
        $consulta = $this->disponibilidad->aplicar(
            Empleo::query()
        );

        if (isset($filtros['titulo']) && $filtros['titulo'] !== '') {
            $titulo = mb_strtolower($filtros['titulo'], 'UTF-8');

            $titulo = str_replace(
                ['!', '%', '_'],
                ['!!', '!%', '!_'],
                $titulo
            );

            $consulta->whereRaw(
                "LOWER(empleos.titulo) LIKE ? ESCAPE '!'",
                ['%'.$titulo.'%']
            );
        }

        if (! empty($filtros['habilidades'])) {
            $consulta->whereHas(
                'habilidades',
                fn (Builder $habilidades) => $habilidades->whereIn(
                    'habilidades.id',
                    $filtros['habilidades']
                )
            );
        }

        if (! empty($filtros['niveles_puesto'])) {
            $consulta->whereIn(
                'empleos.nivel_puesto_id',
                $filtros['niveles_puesto']
            );
        }

        if (isset($filtros['area_estudio_id'])) {
            $consulta->where(
                'empleos.area_estudio_id',
                $filtros['area_estudio_id']
            );
        }

        if (
            in_array(
                $filtros['solo_remoto'] ?? false,
                [true, 1, '1'],
                true
            )
        ) {
            $consulta->where('empleos.tipo_empleo', 'remoto');
        }

        if (isset($filtros['salario_minimo'])) {
            $consulta
                ->whereNotNull('empleos.salario_max')
                ->where(
                    'empleos.salario_max',
                    '>=',
                    $filtros['salario_minimo']
                );
        }

        return $consulta;
    }

    public function buscar(array $filtros): LengthAwarePaginator
    {
        $publicadaEn = PagoVacante::query()
            ->selectRaw('MIN(pagos_vacante.created_at)')
            ->whereColumn(
                'pagos_vacante.empleo_id',
                'empleos.id'
            )
            ->where(
                'pagos_vacante.resultado',
                PagoVacante::EXITOSO
            );

        return $this->construirConsulta($filtros)
            ->select('empleos.*')
            ->addSelect([
                'publicada_en' => $publicadaEn,
            ])
            ->withCount('postulaciones')
            ->with([
                'empresa:id,razon_social,sector',
                'nivelPuesto:id,nombre',
                'areaEstudio:id,nombre',
                'habilidades:id,nombre,tipo_habilidad',
            ])
            ->orderByDesc('publicada_en')
            ->orderByDesc('empleos.id')
            ->paginate(
                perPage: self::POR_PAGINA,
                columns: ['*'],
                pageName: 'page',
                page: (int) ($filtros['page'] ?? 1)
            );
    }
}
