<?php

namespace Database\Seeders;

use App\Models\Empleo;
use App\Models\PagoVacante;
use App\Models\RevisionVacante;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class QaCargaVacantesSeeder extends Seeder
{
    public function run(): void
    {
        if (
            ! app()->environment('local')
            || DB::selectOne('SELECT DATABASE() AS nombre')->nombre !== 'portal_empleos_calidad_qa'
        ) {
            throw new RuntimeException('Solo corre en la base local aislada de QA.');
        }

        if (Empleo::where('titulo', 'like', 'Vacante carga %')->exists()) {
            $this->command?->warn('Las vacantes de carga ya existen; no se crean de nuevo.');
            return;
        }

        $plantilla = Empleo::query()
            ->whereHas('revision', fn ($q) => $q->where('decision', RevisionVacante::APROBADA))
            ->whereHas('pagos', fn ($q) => $q->where('resultado', PagoVacante::EXITOSO))
            ->with(['revision', 'habilidades'])
            ->first();

        if (! $plantilla) {
            throw new RuntimeException('No hay una vacante aprobada y pagada que usar como plantilla.');
        }

        $pagoBase = $plantilla->pagos()->where('resultado', PagoVacante::EXITOSO)->first();
        $hoy = now(Empleo::ZONA_VIGENCIA);
        $habilidades = $plantilla->habilidades
            ->mapWithKeys(fn ($h) => [$h->id => ['tipo_requisito' => $h->pivot->tipo_requisito]])
            ->all();

        DB::transaction(function () use ($plantilla, $pagoBase, $hoy, $habilidades): void {
            for ($i = 1; $i <= 300; $i++) {
                $e = new Empleo([
                    'empresa_id' => $plantilla->empresa_id,
                    'titulo' => sprintf('Vacante carga %03d', $i),
                    'descripcion' => 'Vacante ficticia para pruebas de carga QA-24.',
                    'nivel_puesto_id' => $plantilla->nivel_puesto_id,
                    'area_estudio_id' => $plantilla->area_estudio_id,
                    'tipo_empleo' => 'remoto',
                    'salario_min' => 3500,
                    'salario_max' => 5000,
                    'fecha_inicio_vigencia' => $hoy->toDateString(),
                    'fecha_fin_vigencia' => $hoy->copy()->addDays(90)->toDateString(),
                    'estado_empleo' => Empleo::ACTIVA,
                    'numero_vacantes' => 1000,
                ]);
                $e->save();

                $rev = $plantilla->revision->replicate();
                $rev->empleo_id = $e->id;
                $rev->save();

                $pago = $pagoBase->replicate();
                $pago->empleo_id = $e->id;
                $pago->save();

                if ($habilidades) {
                    $e->habilidades()->attach($habilidades);
                }
            }
        });

        $this->command?->info('300 vacantes de carga creadas.');
    }
}