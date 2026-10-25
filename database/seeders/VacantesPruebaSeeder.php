<?php

namespace Database\Seeders;

use App\Models\Empleo;
use App\Models\Empresa;
use App\Models\Habilidad;
use App\Models\PagoVacante;
use App\Models\RevisionVacante;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Datos de prueba para la carpeta de Postman "18. Vacantes - Moderación y Pago Simulado (Dev 2)".
 * Mientras no exista el registro de vacantes (VAC-02), es la forma de tener vacantes para probar.
 *
 * No está en DatabaseSeeder: se ejecuta a mano antes de correr la carpeta, y se puede repetir
 * porque borra y vuelve a crear sus propias vacantes:
 *   docker compose exec app php artisan db:seed --class=VacantesPruebaSeeder
 */
class VacantesPruebaSeeder extends Seeder
{
    // IDs fijos: la colección de Postman los usa directamente
    public const APROBAR_Y_PAGAR = 9001;
    public const NO_APROBAR = 9002;
    public const MASIVA_1 = 9003;
    public const MASIVA_2 = 9004;
    public const DE_OTRA_EMPRESA = 9005;
    public const ELIMINADA = 9006;

    // Contraseña de las cuentas de prueba (solo local; cumple PasswordSegura)
    private const PASSWORD = 'EmpresaEp02#';

    public function run(): void
    {
        DB::transaction(function () {
            $empresa = $this->empresa('empresa.ep02@prueba.com', 'Empresa Prueba EP02 S.A.C.', '20999990001');
            $otra = $this->empresa('otra.ep02@prueba.com', 'Otra Empresa EP02 S.A.C.', '20999990002');

            $this->borrarVacantesAnteriores();

            $vacantes = [
                self::APROBAR_Y_PAGAR => [$empresa, 'Desarrollador Backend Laravel (EP02 aprobar y pagar)'],
                self::NO_APROBAR => [$empresa, 'Analista de Datos (EP02 no aprobar)'],
                self::MASIVA_1 => [$empresa, 'Analista QA (EP02 masiva 1)'],
                self::MASIVA_2 => [$empresa, 'Diseñador UX (EP02 masiva 2)'],
                self::DE_OTRA_EMPRESA => [$otra, 'Soporte TI (EP02 otra empresa)'],
                self::ELIMINADA => [$empresa, 'Community Manager (EP02 eliminada)'],
            ];

            foreach ($vacantes as $id => [$duena, $titulo]) {
                $this->crearVacante($id, $duena, $titulo);
            }

            Empleo::find(self::ELIMINADA)->delete();
        });

        $this->command?->info('Vacantes de prueba 9001-9006 listas. Empresa: empresa.ep02@prueba.com / ' . self::PASSWORD);
    }

    private function empresa(string $correo, string $razonSocial, string $ruc): Empresa
    {
        // updateOrCreate: al repetir el seeder la cuenta vuelve a quedar activa y sin bloqueos de login
        $usuario = Usuario::updateOrCreate(
            ['correo' => $correo],
            [
                'password' => self::PASSWORD,
                'rol_id' => Rol::where('nombre', 'empresa')->value('id'),
                'estado_cuenta' => 'activo',
                'intentos_fallidos' => 0,
                'bloqueado_hasta' => null,
            ]
        );

        return Empresa::updateOrCreate(
            ['usuario_id' => $usuario->id],
            [
                'encargado_empresa' => 'Encargado de Prueba',
                'razon_social' => $razonSocial,
                'sector' => 'Tecnología e Informática',
                'ruc' => $ruc,
                'sitio_web' => null,
                'descripcion' => 'Empresa creada para las pruebas de Postman de EP-02.',
            ]
        );
    }

    private function borrarVacantesAnteriores(): void
    {
        $ids = [
            self::APROBAR_Y_PAGAR,
            self::NO_APROBAR,
            self::MASIVA_1,
            self::MASIVA_2,
            self::DE_OTRA_EMPRESA,
            self::ELIMINADA,
        ];

        // Revisiones y pagos primero: sus FK a empleos son restrict. Las habilidades se borran en cascada.
        PagoVacante::whereIn('empleo_id', $ids)->delete();
        RevisionVacante::whereIn('empleo_id', $ids)->delete();
        Empleo::withTrashed()->whereIn('id', $ids)->forceDelete();
    }

    // Vacante pendiente de revisión, vigente desde hoy por 30 días, con una habilidad técnica y una blanda
    private function crearVacante(int $id, Empresa $empresa, string $titulo): void
    {
        $hoy = now(Empleo::ZONA_VIGENCIA);

        $vacante = new Empleo([
            'empresa_id' => $empresa->id,
            'titulo' => $titulo,
            'descripcion' => "## Sobre el puesto\nVacante de prueba para validar la moderación y el pago simulado de EP-02.",
            'nivel_experiencia' => 'semi_senior',
            'salario_min' => 3500,
            'salario_max' => 5000,
            'fecha_inicio_vigencia' => $hoy->toDateString(),
            'fecha_fin_vigencia' => $hoy->copy()->addDays(30)->toDateString(),
            'estado_empleo' => Empleo::ACTIVA,
            'numero_vacantes' => 2,
        ]);
        $vacante->id = $id;
        $vacante->save();

        $tecnica = Habilidad::where('activo', true)->where('tipo_habilidad', 'tecnica')->orderBy('id')->value('id');
        $blanda = Habilidad::where('activo', true)->where('tipo_habilidad', 'blanda')->orderBy('id')->value('id');

        $habilidades = [];

        if ($tecnica !== null) {
            $habilidades[$tecnica] = ['tipo_requisito' => 'must_have'];
        }

        if ($blanda !== null) {
            $habilidades[$blanda] = ['tipo_requisito' => 'nice_to_have'];
        }

        $vacante->habilidades()->attach($habilidades);
    }
}
