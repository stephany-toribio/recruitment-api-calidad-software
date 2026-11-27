<?php

namespace Database\Seeders;

use App\Models\AreaEstudio;
use App\Models\Candidato;
use App\Models\Empleo;
use App\Models\Empresa;
use App\Models\EstadoPostulacion;
use App\Models\Habilidad;
use App\Models\NivelPuesto;
use App\Models\PagoVacante;
use App\Models\Postulacion;
use App\Models\RevisionVacante;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Datos fijos para probar postulaciones en Postman. No está en DatabaseSeeder: se ejecuta a mano
 * y se puede repetir, porque primero borra lo que creó la vez anterior.
 *   docker compose exec app php artisan db:seed --class=PostulacionesPruebaSeeder
 */
class PostulacionesPruebaSeeder extends Seeder
{
    public const VACANTE_PUBLICADA_1 = 9101;

    public const VACANTE_PUBLICADA_2 = 9102;

    public const VACANTE_ELIMINADA = 9103;

    public const POSTULACION_RECIBIDA = 9201;

    public const POSTULACION_EN_REVISION = 9202;

    public const POSTULACION_ENTREVISTA = 9203;

    public const POSTULACION_RETIRADA = 9204;

    public const POSTULACION_VACANTE_ELIMINADA = 9205;

    private const PASSWORD = 'PruebaPostulacion2026#';

    private const VACANTES = [
        self::VACANTE_PUBLICADA_1,
        self::VACANTE_PUBLICADA_2,
        self::VACANTE_ELIMINADA,
    ];

    public function run(): void
    {
        DB::transaction(function () {
            $this->borrarDatosAnteriores();

            $adminId = $this->adminId();
            $empresa = $this->empresa();
            $candidatoUno = $this->candidato('candidato.uno.postulaciones@prueba.com', 'Ana', 'Torres Ríos', '90000001', '900091001');
            $candidatoDos = $this->candidato('candidato.dos.postulaciones@prueba.com', 'Bruno', 'Salas Vega', '90000002', '900091002');

            [$tecnicaUno, $tecnicaDos, $blanda] = $this->habilidades();

            $this->vacante(self::VACANTE_PUBLICADA_1, $empresa, $adminId, 'Desarrollador Backend', [
                [$tecnicaUno, 'must_have'],
                [$tecnicaDos, 'must_have'],
                [$blanda, 'nice_to_have'],
            ]);
            $this->vacante(self::VACANTE_PUBLICADA_2, $empresa, $adminId, 'Analista de Datos', [
                [$tecnicaUno, 'must_have'],
                [$blanda, 'nice_to_have'],
            ]);
            $this->vacante(self::VACANTE_ELIMINADA, $empresa, $adminId, 'Soporte TI', [
                [$tecnicaUno, 'must_have'],
            ]);

            // Uno tiene una de las dos habilidades obligatorias de la primera vacante; el otro, la otra
            $candidatoUno->habilidades()->sync(array_filter([$tecnicaUno, $blanda]));
            $candidatoDos->habilidades()->sync(array_filter([$tecnicaDos]));

            $this->postulacion(self::POSTULACION_RECIBIDA, $candidatoUno, self::VACANTE_PUBLICADA_1, EstadoPostulacion::RECIBIDO);
            $this->postulacion(self::POSTULACION_EN_REVISION, $candidatoDos, self::VACANTE_PUBLICADA_1, EstadoPostulacion::EN_REVISION);
            $this->postulacion(self::POSTULACION_ENTREVISTA, $candidatoUno, self::VACANTE_PUBLICADA_2, EstadoPostulacion::ENTREVISTA);
            $this->postulacion(self::POSTULACION_RETIRADA, $candidatoDos, self::VACANTE_PUBLICADA_2, EstadoPostulacion::EN_REVISION, retirada: true);
            $this->postulacion(self::POSTULACION_VACANTE_ELIMINADA, $candidatoUno, self::VACANTE_ELIMINADA, EstadoPostulacion::RECIBIDO);

            Empleo::find(self::VACANTE_ELIMINADA)->delete();
        });

        $this->command?->info('Postulaciones de prueba listas. Contraseña de las cuentas: '.self::PASSWORD);
        $this->command?->info('Empresa: empresa.postulaciones@prueba.com');
        $this->command?->info('Candidatos: candidato.uno.postulaciones@prueba.com, candidato.dos.postulaciones@prueba.com');
    }

    // Revisiones y pagos se borran antes que las vacantes porque su FK a empleos es restrict
    private function borrarDatosAnteriores(): void
    {
        Postulacion::whereIn('empleo_id', self::VACANTES)->delete();
        PagoVacante::whereIn('empleo_id', self::VACANTES)->delete();
        RevisionVacante::whereIn('empleo_id', self::VACANTES)->delete();
        Empleo::withTrashed()->whereIn('id', self::VACANTES)->forceDelete();
    }

    private function usuario(string $correo, string $rol): Usuario
    {
        return Usuario::updateOrCreate(
            ['correo' => $correo],
            [
                'password' => self::PASSWORD,
                'rol_id' => Rol::firstOrCreate(['nombre' => $rol])->id,
                'estado_cuenta' => 'activo',
                'intentos_fallidos' => 0,
                'bloqueado_hasta' => null,
            ]
        );
    }

    private function adminId(): int
    {
        return Usuario::whereHas('rol', fn ($rol) => $rol->where('nombre', 'admin'))->orderBy('id')->value('id')
            ?? $this->usuario('admin.postulaciones@prueba.com', 'admin')->id;
    }

    private function empresa(): Empresa
    {
        $usuario = $this->usuario('empresa.postulaciones@prueba.com', 'empresa');

        return Empresa::updateOrCreate(
            ['usuario_id' => $usuario->id],
            [
                'encargado_empresa' => 'Encargado de Prueba',
                'razon_social' => 'Empresa Postulaciones Prueba S.A.C.',
                'sector' => 'Tecnología e Informática',
                'ruc' => '20999990101',
                'sitio_web' => null,
                'descripcion' => 'Empresa creada para probar postulaciones.',
            ]
        );
    }

    private function candidato(string $correo, string $nombre, string $apellidos, string $dni, string $celular): Candidato
    {
        $usuario = $this->usuario($correo, 'candidato');

        return Candidato::updateOrCreate(
            ['usuario_id' => $usuario->id],
            [
                'nombre' => $nombre,
                'apellidos' => $apellidos,
                'dni' => $dni,
                'fecha_nacimiento' => '1998-05-20',
                'genero' => 'prefiero no decir',
                'busca_primer_empleo' => false,
                'celular' => $celular,
            ]
        );
    }

    /**
     * @return array{0: ?int, 1: ?int, 2: ?int}
     */
    private function habilidades(): array
    {
        $tecnicas = Habilidad::where('activo', true)->where('tipo_habilidad', 'tecnica')->orderBy('id')->limit(2)->pluck('id');
        $blanda = Habilidad::where('activo', true)->where('tipo_habilidad', 'blanda')->orderBy('id')->value('id');

        return [$tecnicas->get(0), $tecnicas->get(1), $blanda];
    }

    /**
     * Vacante aprobada y con un pago exitoso, es decir, publicada.
     *
     * @param  array<int, array{0: ?int, 1: string}>  $habilidades  [habilidad_id, tipo_requisito]
     */
    private function vacante(int $id, Empresa $empresa, int $adminId, string $titulo, array $habilidades): void
    {
        $hoy = now(Empleo::ZONA_VIGENCIA)->startOfDay();

        $vacante = new Empleo([
            'empresa_id' => $empresa->id,
            'titulo' => $titulo,
            'descripcion' => 'Vacante de prueba para revisar postulaciones, el tablero de selección y el perfil de los postulantes.',
            'nivel_puesto_id' => $this->nivelPuestoId(),
            'area_estudio_id' => $this->areaEstudioId(),
            'tipo_empleo' => 'remoto',
            'ubicacion' => null,
            'salario_min' => 3500,
            'salario_max' => 5000,
            'fecha_inicio_vigencia' => $hoy->toDateString(),
            'fecha_fin_vigencia' => $hoy->copy()->addDays(30)->toDateString(),
            'estado_empleo' => Empleo::ACTIVA,
            'numero_vacantes' => 2,
        ]);
        $vacante->id = $id;
        $vacante->save();

        RevisionVacante::create([
            'empleo_id' => $vacante->id,
            'admin_id' => $adminId,
            'decision' => RevisionVacante::APROBADA,
        ]);

        PagoVacante::create([
            'empleo_id' => $vacante->id,
            'resultado' => PagoVacante::EXITOSO,
            'monto' => $this->monto($vacante),
            'moneda' => config('pago_vacantes.moneda'),
            'tarjeta_ultimos4' => '1111',
        ]);

        // Si el catálogo no tiene alguna habilidad, se omite en lugar de fallar
        $pivote = [];

        foreach ($habilidades as [$habilidadId, $requisito]) {
            if ($habilidadId !== null) {
                $pivote[$habilidadId] = ['tipo_requisito' => $requisito];
            }
        }

        $vacante->habilidades()->attach($pivote);
    }

    private function monto(Empleo $vacante): float
    {
        $tarifa = (float) config('pago_vacantes.tarifa_por_puesto');

        return config('pago_vacantes.unidad_cobro') === 'vacante'
            ? $tarifa
            : $tarifa * $vacante->numero_vacantes;
    }

    // Una vacante solo acepta niveles globales (sin candidato)
    private function nivelPuestoId(): int
    {
        return NivelPuesto::whereNull('candidato_id')->where('estado', 'activo')->orderBy('orden')->value('id')
            ?? NivelPuesto::create(['nombre' => 'Nivel de prueba', 'estado' => 'activo'])->id;
    }

    private function areaEstudioId(): int
    {
        return AreaEstudio::where('activo', true)->orderBy('id')->value('id')
            ?? AreaEstudio::firstOrCreate(['nombre' => 'Área de prueba'], ['activo' => true])->id;
    }

    private function postulacion(int $id, Candidato $candidato, int $vacanteId, string $estado, bool $retirada = false): void
    {
        $postulacion = new Postulacion([
            'candidato_id' => $candidato->id,
            'empleo_id' => $vacanteId,
            'estado_postulacion_id' => EstadoPostulacion::idDe($estado),
            'fecha_postulacion' => now(),
            'retirada_en' => $retirada ? now() : null,
        ]);
        $postulacion->id = $id;
        $postulacion->save();
    }
}
