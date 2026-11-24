<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class QaCleanupSeeder extends Seeder
{
    private const CUENTAS = [
        'admin@qa.example.test',
        'empresa1@qa.example.test',
        'empresa2@qa.example.test',
        'candidato1@qa.example.test',
        'candidato2@qa.example.test',
        'suspendido@qa.example.test',
    ];

    public function run(): void
    {
        $this->verificarEntorno();

        DB::transaction(function (): void {
            $usuarios = Usuario::whereIn('correo', self::CUENTAS)
                ->lockForUpdate()
                ->get();

            $usuarioIds = $usuarios->pluck('id');

            $empresaIds = DB::table('empresas')
                ->whereIn('usuario_id', $usuarioIds)
                ->pluck('id');

            $candidatoIds = DB::table('candidatos')
                ->whereIn('usuario_id', $usuarioIds)
                ->pluck('id');

            $dependencias = [
                ['empleos', 'empresa_id', $empresaIds],
                ['revisiones_vacante', 'admin_id', $usuarioIds],
                ['postulaciones', 'candidato_id', $candidatoIds],
                ['candidato_estudios', 'candidato_id', $candidatoIds],
                ['candidato_habilidades', 'candidato_id', $candidatoIds],
                ['candidato_idiomas', 'candidato_id', $candidatoIds],
                ['experiencias_laborales', 'candidato_id', $candidatoIds],
                ['vacantes_guardadas', 'candidato_id', $candidatoIds],
                ['niveles_puesto', 'candidato_id', $candidatoIds],
                ['organizaciones', 'candidato_id', $candidatoIds],
                ['puestos', 'candidato_id', $candidatoIds],
            ];

            foreach ($dependencias as [$tabla, $columna, $ids]) {
                if (DB::table($tabla)->whereIn($columna, $ids)->exists()) {
                    throw new RuntimeException(
                        'Limpieza cancelada: existen datos dependientes en '
                        .$tabla
                    );
                }
            }

            foreach ($usuarios as $usuario) {
                $usuario->tokens()->delete();
            }

            DB::table('password_reset_tokens')
                ->whereIn('usuario_id', $usuarioIds)
                ->delete();

            DB::table('candidatos')
                ->whereIn('usuario_id', $usuarioIds)
                ->delete();

            DB::table('empresas')
                ->whereIn('usuario_id', $usuarioIds)
                ->delete();

            DB::table('usuarios')
                ->whereIn('id', $usuarioIds)
                ->delete();

            $this->command?->info(
                'Usuarios QA eliminados: '.$usuarios->count()
            );
        });

        $this->command?->info(
            'Limpieza QA completada correctamente.'
        );
    }

    private function verificarEntorno(): void
    {
        if (
            app()->configurationIsCached()
            || ! app()->environment('local')
            || DB::connection()->getDriverName() !== 'mysql'
            || DB::connection()->getConfig('host') !== 'mysql'
            || DB::selectOne('SELECT DATABASE() AS nombre')->nombre
                !== 'portal_empleos_calidad_qa'
        ) {
            throw new RuntimeException(
                'Limpieza permitida únicamente en la base local de QA.'
            );
        }
    }
}
