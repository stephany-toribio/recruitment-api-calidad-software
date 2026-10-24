<?php

namespace App\Services\Vacantes;

use App\Exceptions\BusinessException;
use App\Models\Empleo;
use App\Models\Empresa;
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

    public function registrar(Empresa $empresa, array $datos): Empleo
    {
        return DB::transaction(function () use ($empresa, $datos) {
            $vacante = Empleo::create([
                'empresa_id' => $empresa->id,
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
                'estado_empleo' => Empleo::ACTIVA,
            ]);

            $habilidades = [];

            foreach ($datos['habilidades'] as $habilidad) {
                $habilidades[$habilidad['habilidad_id']] = [
                    'tipo_requisito' => $habilidad['requisito'],
                ];
            }

            $vacante->habilidades()->attach($habilidades);

            return $vacante->load(self::RELACIONES);
        });
    }

    public function detalle(Empresa $empresa, Empleo $vacante): Empleo
    {
        if ((int) $vacante->empresa_id !== (int) $empresa->id) {
            throw new BusinessException(
                'No tienes permisos para realizar esta acción.',
                403,
                'FORBIDDEN'
            );
        }

        return $vacante->load(self::RELACIONES);
    }
}
