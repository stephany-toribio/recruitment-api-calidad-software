<?php

namespace App\Services\Vacantes;

use App\Exceptions\AprobacionMasivaRechazadaException;
use App\Exceptions\BusinessException;
use App\Models\Empleo;
use App\Models\Empresa;
use App\Models\RevisionVacante;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class RevisionVacanteService
{
    private const RELACIONES_ADMIN = ['empresa', 'nivelPuesto', 'areaEstudio', 'habilidades', 'revision'];

    private const RELACIONES_EMPRESA = ['nivelPuesto', 'areaEstudio', 'habilidades', 'revision', 'pagos'];

    public function pendientes(): Collection
    {
        return Empleo::with(self::RELACIONES_ADMIN)
            ->whereDoesntHave('revision')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    public function detalle(Empleo $vacante): Empleo
    {
        return $vacante->load(self::RELACIONES_ADMIN);
    }

    public function aprobar(Empleo $vacante, int $adminId): Empleo
    {
        return $this->decidir($vacante, $adminId, RevisionVacante::APROBADA);
    }

    public function noAprobar(Empleo $vacante, int $adminId, string $motivo): Empleo
    {
        return $this->decidir($vacante, $adminId, RevisionVacante::NO_APROBADA, $motivo);
    }

    /**
     * MOD-04, todo o nada: si una vacante del lote no existe o ya fue revisada, no se aprueba ninguna.
     *
     * @param  array<int, int>  $ids
     * @return array{aprobadas: array<int, int>, no_procesadas: array<int, mixed>}
     */
    public function aprobarMasivo(array $ids, int $adminId): array
    {
        return DB::transaction(function () use ($ids, $adminId) {
            $vacantes = Empleo::whereIn('id', $ids)
                ->lockForUpdate()
                ->with('revision')
                ->get()
                ->keyBy('id');

            $errores = [];

            foreach ($ids as $i => $id) {
                $vacante = $vacantes->get($id);

                if ($vacante === null) {
                    $errores["vacantes.$i"] = ["La vacante $id no existe."];
                } elseif ($vacante->revision !== null) {
                    $errores["vacantes.$i"] = ["La vacante $id ya fue revisada."];
                }
            }

            if ($errores !== []) {
                throw new AprobacionMasivaRechazadaException($errores);
            }

            foreach ($vacantes as $vacante) {
                $this->registrar($vacante, $adminId, RevisionVacante::APROBADA);
            }

            return [
                'aprobadas' => array_values($ids),
                'no_procesadas' => [],
            ];
        });
    }

    public function vacantesDeEmpresa(int $usuarioId): Collection
    {
        $empresa = Empresa::where('usuario_id', $usuarioId)->firstOrFail();

        return Empleo::with(self::RELACIONES_EMPRESA)
            ->where('empresa_id', $empresa->id)
            ->latest()
            ->orderByDesc('id')
            ->get();
    }

    private function decidir(Empleo $vacante, int $adminId, string $decision, ?string $motivo = null): Empleo
    {
        return DB::transaction(function () use ($vacante, $adminId, $decision, $motivo) {
            // Bloqueo de lectura: si otro admin decide o la empresa la elimina a la vez, se espera el turno.
            // Si la eliminaron mientras tanto, firstOrFail responde 404.
            $bloqueada = Empleo::whereKey($vacante->getKey())->lockForUpdate()->firstOrFail();

            if ($bloqueada->revision()->exists()) {
                throw $this->yaRevisada();
            }

            $this->registrar($bloqueada, $adminId, $decision, $motivo);

            return $bloqueada->load(self::RELACIONES_ADMIN);
        });
    }

    private function registrar(Empleo $vacante, int $adminId, string $decision, ?string $motivo = null): void
    {
        try {
            RevisionVacante::create([
                'empleo_id' => $vacante->id,
                'admin_id' => $adminId,
                'decision' => $decision,
                'motivo' => $motivo,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw $this->yaRevisada();
        }
    }

    private function yaRevisada(): BusinessException
    {
        return new BusinessException('La vacante ya fue revisada.', 409, 'VACANCY_ALREADY_REVIEWED');
    }
}
