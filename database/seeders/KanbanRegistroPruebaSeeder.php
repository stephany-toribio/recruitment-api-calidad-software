<?php

namespace Database\Seeders;

use App\Models\{AreaEstudio, Candidato, Empleo, Empresa, EstadoPostulacion, NivelPuesto, Postulacion, Usuario};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

class KanbanRegistroPruebaSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new RuntimeException('Solo se permite en local o testing.');
        }

        $variables = DB::transaction(function () {
            $this->call([EstadoPostulacionSeeder::class, MotivoRechazoSeeder::class]);
            $empresa = Empresa::whereHas('usuario', fn ($q) => $q->where('correo', 'empresa.registro.kanban@prueba.com'))->firstOrFail();
            $ana = Candidato::whereHas('usuario', fn ($q) => $q->where('correo', 'ana.registro.kanban@prueba.com'))->firstOrFail();
            $bruno = Candidato::whereHas('usuario', fn ($q) => $q->where('correo', 'bruno.registro.kanban@prueba.com'))->firstOrFail();
            $admin = Usuario::whereHas('rol', fn ($q) => $q->where('nombre', 'admin'))->firstOrFail();
            $nivel = NivelPuesto::whereNull('candidato_id')->where('estado', 'activo')->firstOrFail();
            $area = AreaEstudio::where('activo', true)->firstOrFail();
            $vacantes = [];
            $vars = [];

            foreach (['vacante_1', 'vacante_2', 'vacante_eliminada'] as $clave) {
                $titulo = 'QA HU06 Registro - '.$clave;
                $viejas = Empleo::withTrashed()->where('empresa_id', $empresa->id)->where('titulo', $titulo)->pluck('id');
                $postulaciones = Postulacion::whereIn('empleo_id', $viejas)->pluck('id');
                // Solo reinicia fixtures de estas cuentas y títulos QA.
                DB::table('notificaciones_postulacion')->whereIn('postulacion_id', $postulaciones)->delete();
                DB::table('historial_postulaciones')->whereIn('postulacion_id', $postulaciones)->delete();
                Postulacion::whereIn('id', $postulaciones)->delete();
                DB::table('pagos_vacante')->whereIn('empleo_id', $viejas)->delete();
                DB::table('revisiones_vacante')->whereIn('empleo_id', $viejas)->delete();
                DB::table('empleo_habilidades')->whereIn('empleo_id', $viejas)->delete();
                Empleo::withTrashed()->whereIn('id', $viejas)->forceDelete();

                $vacante = Empleo::create([
                    'empresa_id' => $empresa->id,
                    'titulo' => $titulo,
                    'descripcion' => 'Vacante QA para comprobar el tablero de postulantes, transiciones, cupos e historial.',
                    'nivel_puesto_id' => $nivel->id,
                    'area_estudio_id' => $area->id,
                    'tipo_empleo' => 'remoto',
                    'ubicacion' => null,
                    'salario_min' => 2500,
                    'salario_max' => 4000,
                    'fecha_inicio_vigencia' => now(Empleo::ZONA_VIGENCIA)->toDateString(),
                    'fecha_fin_vigencia' => now(Empleo::ZONA_VIGENCIA)->addDays(30)->toDateString(),
                    'estado_empleo' => Empleo::ACTIVA,
                    'numero_vacantes' => $clave === 'vacante_1' ? 1 : 2,
                ]);
                DB::table('revisiones_vacante')->insert([
                    'empleo_id' => $vacante->id, 'admin_id' => $admin->id,
                    'decision' => 'aprobada', 'created_at' => now(), 'updated_at' => now(),
                ]);
                DB::table('pagos_vacante')->insert([
                    'empleo_id' => $vacante->id, 'resultado' => 'exitoso',
                    'monto' => 200, 'moneda' => 'PEN', 'tarjeta_ultimos4' => '1111',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $vacantes[$clave] = $vacante;
                $vars[$clave] = $vacante->id;
            }

            foreach ([
                ['postulacion_recibida', $ana, 'vacante_1', EstadoPostulacion::RECIBIDO, false],
                ['postulacion_revision', $bruno, 'vacante_1', EstadoPostulacion::EN_REVISION, false],
                ['postulacion_entrevista', $ana, 'vacante_2', EstadoPostulacion::ENTREVISTA, false],
                ['postulacion_retirada', $bruno, 'vacante_2', EstadoPostulacion::EN_REVISION, true],
                ['postulacion_eliminada', $ana, 'vacante_eliminada', EstadoPostulacion::RECIBIDO, false],
            ] as [$clave, $candidato, $vacanteClave, $estado, $retirada]) {
                $postulacion = Postulacion::create([
                    'candidato_id' => $candidato->id,
                    'empleo_id' => $vacantes[$vacanteClave]->id,
                    'estado_postulacion_id' => EstadoPostulacion::idDe($estado),
                    'fecha_postulacion' => now(),
                    'retirada_en' => $retirada ? now() : null,
                ]);
                $vars[$clave] = $postulacion->id;
            }
            $vacantes['vacante_eliminada']->delete();
            return $vars;
        });

        $values = [];
        foreach ($variables as $key => $value) {
            $values[] = ['key' => $key, 'value' => (string) $value, 'type' => 'default', 'enabled' => true];
        }
        $path = storage_path('app/HU06_Kanban_QA.postman_environment.json');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode([
            'name' => 'HU06 Kanban QA - IDs',
            'values' => $values,
            '_postman_variable_scope' => 'environment',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        $this->command?->info('Datos QA preparados. Importa y selecciona: '.$path);
    }
}
