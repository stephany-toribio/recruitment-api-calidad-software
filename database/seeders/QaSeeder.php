<?php

namespace Database\Seeders;

use App\Models\Candidato;
use App\Models\Empresa;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class QaSeeder extends Seeder
{
    public function run(): void
    {
        $this->verificarEntorno();

        $password = (string) env('QA_FIXTURE_PASSWORD', '');

        if (strlen($password) < 12) {
            throw new RuntimeException(
                'Define QA_FIXTURE_PASSWORD con al menos 12 caracteres.'
            );
        }

        DB::transaction(function () use ($password): void {
            $this->call(RolSeeder::class);

            $this->crearUsuario(
                'admin@qa.example.test',
                'admin',
                $password
            );

            $this->crearEmpresa(
                'empresa1@qa.example.test',
                'Empresa QA Alfa',
                '20000000001',
                $password
            );

            $this->crearEmpresa(
                'empresa2@qa.example.test',
                'Empresa QA Beta',
                '20000000002',
                $password
            );

            $this->crearCandidato(
                'candidato1@qa.example.test',
                'Ana',
                'Prueba QA',
                '00000001',
                '900000001',
                $password
            );

            $this->crearCandidato(
                'candidato2@qa.example.test',
                'Bruno',
                'Prueba QA',
                '00000002',
                '900000002',
                $password
            );

            $this->crearCandidato(
                'suspendido@qa.example.test',
                'Carlos',
                'Prueba QA',
                '00000003',
                '900000003',
                $password,
                'suspendido'
            );
        });

        $this->command?->info(
            'Fixtures QA preparados correctamente.'
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
                'QaSeeder solo puede ejecutarse en la base local aislada de QA.'
            );
        }
    }

    private function crearUsuario(
        string $correo,
        string $rol,
        string $password,
        string $estado = 'activo'
    ): Usuario {
        $rolId = Rol::where('nombre', $rol)->firstOrFail()->id;

        $usuario = Usuario::firstOrNew([
            'correo' => $correo,
        ]);

        if (
            $usuario->exists
            && (int) $usuario->rol_id !== (int) $rolId
        ) {
            throw new RuntimeException(
                'La cuenta existente tiene un rol incompatible: '.$correo
            );
        }

        $usuario->fill([
            'password' => $password,
            'rol_id' => $rolId,
            'estado_cuenta' => $estado,
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
            'motivo_suspension' => $estado === 'suspendido'
                ? 'Fixture QA de cuenta suspendida'
                : null,
            'fecha_suspension' => $estado === 'suspendido'
                ? now()
                : null,
        ]);

        $usuario->save();

        $usuario->tokens()->delete();

        return $usuario;
    }

    private function crearEmpresa(
        string $correo,
        string $razonSocial,
        string $ruc,
        string $password
    ): void {
        $usuario = $this->crearUsuario(
            $correo,
            'empresa',
            $password
        );

        Empresa::updateOrCreate(
            ['usuario_id' => $usuario->id],
            [
                'encargado_empresa' => 'Responsable QA',
                'razon_social' => $razonSocial,
                'sector' => 'Tecnología e Informática',
                'descripcion' => 'Empresa ficticia para pruebas de software.',
                'ruc' => $ruc,
                'sitio_web' => null,
            ]
        );
    }

    private function crearCandidato(
        string $correo,
        string $nombre,
        string $apellidos,
        string $dni,
        string $celular,
        string $password,
        string $estado = 'activo'
    ): void {
        $usuario = $this->crearUsuario(
            $correo,
            'candidato',
            $password,
            $estado
        );

        Candidato::updateOrCreate(
            ['usuario_id' => $usuario->id],
            [
                'nombre' => $nombre,
                'apellidos' => $apellidos,
                'dni' => $dni,
                'celular' => $celular,
                'fecha_nacimiento' => '2000-01-01',
                'genero' => 'prefiero no decir',
                'busca_primer_empleo' => true,
            ]
        );
    }
}
