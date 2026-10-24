<?php

namespace App\Services\Vacantes;

use App\Exceptions\AprobacionMasivaRechazadaException;
use App\Exceptions\BusinessException;
use App\Models\Empleo;
use App\Models\Empresa;
use App\Models\RevisionVacante;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

// Revisión administrativa de vacantes. Escribe solo en revisiones_vacante; empleos se lee.
class RevisionVacanteService
{
    // Relaciones que muestra VacanteRevisionResource
    private const RELACIONES_ADMIN = ['empresa', 'habilidades', 'revision'];

    // Relaciones que usa VacanteEmpresaResource para el bloque estado
    private const RELACIONES_EMPRESA = ['habilidades', 'revision', 'pagos'];

    // Sin revisión vigente = pendiente (también las aprobadas que la empresa editó). SoftDeletes ya deja fuera las eliminadas.
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
     
     * @param  array<int, int>  $ids
     * @return array{aprobadas: array<int, int>, no_procesadas: array<int, mixed>}
     */
    public function aprobarMasivo(array $ids, int $adminId): array
    {
        return DB::transaction(function () use ($ids, $adminId) {
            // Se bloquean todas antes de revisarlas, para que nadie las decida mientras tanto
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
                // Con todo o nada siempre va vacío; se mantiene para que el formato no cambie si se pasa a parcial
                'no_procesadas' => [],
            ];
        });
    }

    /**
     * La empresa editó una vacante aprobada: la aprobación deja de valer y la vacante vuelve a pendiente.
     * No se borra la decisión anterior, queda como historial. La llama VacanteService::actualizar(),
     * dentro de su transacción y con la vacante ya bloqueada.
     */
    public function reabrirRevision(Empleo $vacante): void
    {
        RevisionVacante::where('empleo_id', $vacante->id)
            ->whereNull('invalidada_en')
            ->where('decision', RevisionVacante::APROBADA)
            ->update(['invalidada_en' => now()]);
    }

    // MOD-06: vacantes no eliminadas de la empresa del token, de la más reciente a la más antigua
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
            // Bloqueo de lectura: si otro admin decide o la empresa la edita o elimina a la vez, se espera el turno.
            // Si la eliminaron mientras tanto, firstOrFail responde 404.
            $bloqueada = Empleo::whereKey($vacante->getKey())->lockForUpdate()->firstOrFail();

            if ($bloqueada->revision()->exists()) {
                throw $this->yaRevisada();
            }

            $this->registrar($bloqueada, $adminId, $decision, $motivo);

            return $bloqueada->load(self::RELACIONES_ADMIN);
        });
    }

    // Sin UNIQUE en empleo_id (hay historial): evitar dos decisiones vigentes depende del lockForUpdate de quien llama
    private function registrar(Empleo $vacante, int $adminId, string $decision, ?string $motivo = null): void
    {
        RevisionVacante::create([
            'empleo_id' => $vacante->id,
            'admin_id' => $adminId,
            'decision' => $decision,
            'motivo' => $motivo,
        ]);
    }

    private function yaRevisada(): BusinessException
    {
        return new BusinessException('La vacante ya fue revisada.', 409, 'VACANCY_ALREADY_REVIEWED');
    }
}
