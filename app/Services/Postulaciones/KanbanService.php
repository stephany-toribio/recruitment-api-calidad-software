<?php

namespace App\Services\Postulaciones;

use App\Exceptions\BusinessException;
use App\Models\Empleo;
use App\Models\Empresa;
use App\Models\EstadoPostulacion;
use App\Models\HistorialPostulacion;
use App\Models\Postulacion;
use Illuminate\Support\Facades\DB;
use Throwable;

class KanbanService
{
    private const TRANSICIONES = [
        EstadoPostulacion::RECIBIDO => [
            'avanzar' => EstadoPostulacion::EN_REVISION,
        ],
        EstadoPostulacion::EN_REVISION => [
            'avanzar' => EstadoPostulacion::ENTREVISTA,
            'rechazar' => EstadoPostulacion::RECHAZADO,
        ],
        EstadoPostulacion::ENTREVISTA => [
            'rechazar' => EstadoPostulacion::RECHAZADO,
            'contratar' => EstadoPostulacion::CONTRATADO,
        ],
    ];

    public function __construct(
        private NotificacionPostulacionService $notificaciones
    ) {}

    public function avanzar(
        Empresa $empresa,
        int $vacanteId,
        int $postulacionId
    ): array {
        return $this->mover(
            $empresa,
            $vacanteId,
            $postulacionId,
            'avanzar'
        );
    }

    public function rechazar(
        Empresa $empresa,
        int $vacanteId,
        int $postulacionId,
        int $motivoId
    ): array {
        return $this->mover(
            $empresa,
            $vacanteId,
            $postulacionId,
            'rechazar',
            $motivoId
        );
    }

    public function contratar(
        Empresa $empresa,
        int $vacanteId,
        int $postulacionId
    ): array {
        return $this->mover(
            $empresa,
            $vacanteId,
            $postulacionId,
            'contratar'
        );
    }

    public function tablero(Empresa $empresa, int $vacanteId): array
    {
        return DB::transaction(function () use ($empresa, $vacanteId) {
            $vacante = $this->vacantePropia($empresa, $vacanteId);

            if ($vacante->trashed()) {
                throw new BusinessException(
                    'La vacante fue eliminada y no se puede consultar el tablero.',
                    409,
                    'VACANCY_DELETED'
                );
            }

            $postulaciones = Postulacion::query()
                ->where('empleo_id', $vacante->id)
                ->activas()
                ->with(['candidato', 'estado'])
                ->orderBy('fecha_postulacion')
                ->orderBy('id')
                ->get();

            $columnas = [];

            foreach (EstadoPostulacion::ORDEN as $nombre) {
                $columnas[] = [
                    'estado' => [
                        'codigo' => EstadoPostulacion::codigoDe($nombre),
                        'nombre' => $nombre,
                    ],
                    'postulaciones' => $postulaciones
                        ->filter(
                            fn (Postulacion $postulacion) =>
                                $postulacion->estado->nombre === $nombre
                        )
                        ->map(fn (Postulacion $postulacion) => [
                            'id' => $postulacion->id,
                            'candidato' => [
                                'nombre' => $postulacion->candidato->nombre,
                                'apellidos' => $postulacion->candidato->apellidos,
                            ],
                            'fecha_postulacion' => $postulacion
                                ->fecha_postulacion?->toISOString(),
                        ])
                        ->values()
                        ->all(),
                ];
            }

            return [
                'vacante' => $this->resumenVacante($vacante),
                'columnas' => $columnas,
            ];
        });
    }

    public function historial(Empresa $empresa, int $vacanteId): array
    {
        return DB::transaction(function () use ($empresa, $vacanteId) {
            $vacante = $this->vacantePropia($empresa, $vacanteId);

            // Incluye retiradas y permite vacantes finalizadas o eliminadas.
            $postulaciones = Postulacion::query()
                ->where('empleo_id', $vacante->id)
                ->with([
                    'candidato',
                    'estado',
                    'historial' => fn ($query) => $query
                        ->orderBy('fecha_cambio')
                        ->orderBy('id'),
                    'historial.estado',
                    'historial.motivoRechazo',
                ])
                ->orderByDesc('fecha_postulacion')
                ->orderByDesc('id')
                ->get();

            return [
                'vacante' => $this->resumenVacante($vacante),
                'postulaciones' => $postulaciones
                    ->map(fn (Postulacion $postulacion) => [
                        'id' => $postulacion->id,
                        'candidato' => [
                            'nombre' => $postulacion->candidato->nombre,
                            'apellidos' => $postulacion->candidato->apellidos,
                        ],
                        'fecha_postulacion' => $postulacion
                            ->fecha_postulacion?->toISOString(),
                        'estado' => [
                            'codigo' => $postulacion->estado->codigo(),
                            'nombre' => $postulacion->estado->nombre,
                        ],
                        'retirada' => $postulacion->estaRetirada(),
                        'retirada_en' => $postulacion
                            ->retirada_en?->toISOString(),
                        'movimientos' => $postulacion->historial
                            ->map(fn (HistorialPostulacion $movimiento) => [
                                'id' => $movimiento->id,
                                'estado' => [
                                    'codigo' => $movimiento->estado->codigo(),
                                    'nombre' => $movimiento->estado->nombre,
                                ],
                                'motivo_rechazo' => $movimiento
                                    ->motivoRechazo?->motivo,
                                'fecha_cambio' => $movimiento
                                    ->fecha_cambio?->toISOString(),
                            ])
                            ->values()
                            ->all(),
                    ])
                    ->values()
                    ->all(),
            ];
        });
    }

    private function vacantePropia(
        Empresa $empresa,
        int $vacanteId
    ): Empleo {
        $vacante = Empleo::withTrashed()
            ->whereKey($vacanteId)
            ->firstOrFail();

        if ((int) $vacante->empresa_id !== (int) $empresa->id) {
            throw new BusinessException(
                'No tienes permisos para realizar esta acción.',
                403,
                'FORBIDDEN'
            );
        }

        return $vacante;
    }

    private function resumenVacante(Empleo $vacante): array
    {
        $contratados = Postulacion::query()
            ->where('empleo_id', $vacante->id)
            ->where(
                'estado_postulacion_id',
                EstadoPostulacion::idDe(EstadoPostulacion::CONTRATADO)
            )
            ->count();

        return [
            'id' => $vacante->id,
            'titulo' => $vacante->titulo,
            'solo_lectura' => ! $vacante->permiteGestionKanban(),
            'max_contrataciones' => (int) $vacante->numero_vacantes,
            'contratados' => $contratados,
            'cupos_disponibles' => max(
                0,
                (int) $vacante->numero_vacantes - $contratados
            ),
        ];
    }

    private function mover(
        Empresa $empresa,
        int $vacanteId,
        int $postulacionId,
        string $accion,
        ?int $motivoId = null
    ): array {
        return DB::transaction(function () use (
            $empresa,
            $vacanteId,
            $postulacionId,
            $accion,
            $motivoId
        ) {
            // Orden común de bloqueo de EP-03: vacante y postulación.
            $vacante = Empleo::withTrashed()
                ->whereKey($vacanteId)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $vacante->empresa_id !== (int) $empresa->id) {
                throw new BusinessException(
                    'No tienes permisos para realizar esta acción.',
                    403,
                    'FORBIDDEN'
                );
            }

            $postulacion = Postulacion::query()
                ->where('empleo_id', $vacante->id)
                ->whereKey($postulacionId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($vacante->trashed()) {
                throw new BusinessException(
                    'La vacante fue eliminada y no se puede modificar.',
                    409,
                    'VACANCY_DELETED'
                );
            }

            if (! $vacante->permiteGestionKanban()) {
                throw new BusinessException(
                    'La vacante está finalizada; esta acción ya no está permitida.',
                    409,
                    'VACANCY_FINALIZED'
                );
            }

            if ($postulacion->estaRetirada()) {
                throw new BusinessException(
                    'El postulante se retiró del proceso.',
                    409,
                    'APPLICATION_WITHDRAWN'
                );
            }

            $postulacion->load('estado');

            $destino = self::TRANSICIONES[
                $postulacion->estado->nombre
            ][$accion] ?? null;

            if ($destino === null) {
                throw new BusinessException(
                    'El movimiento no está permitido desde la etapa actual.',
                    409,
                    'INVALID_TRANSITION'
                );
            }

            $destinoId = EstadoPostulacion::idDe($destino);

            if ($accion === 'contratar') {
                // La vacante bloqueada serializa las contrataciones.
                // Esta lectura obtiene los contratados actuales en MySQL.
                $contratados = Postulacion::query()
                    ->where('empleo_id', $vacante->id)
                    ->where('estado_postulacion_id', $destinoId)
                    ->lockForUpdate()
                    ->get(['id'])
                    ->count();

                if ($contratados >= (int) $vacante->numero_vacantes) {
                    throw new BusinessException(
                        'Ya se alcanzó el número máximo de contrataciones de la vacante.',
                        409,
                        'HIRING_LIMIT_REACHED'
                    );
                }
            }

            // No crea postulaciones ni modifica retirada_en.
            $postulacion->estado_postulacion_id = $destinoId;
            $postulacion->save();

            $movimiento = HistorialPostulacion::create([
                'postulacion_id' => $postulacion->id,
                'estado_postulacion_id' => $destinoId,
                'motivo_rechazo_id' => $accion === 'rechazar'
                    ? $motivoId
                    : null,
                'fecha_cambio' => now(),
            ]);

            $postulacion->load('estado');
            $movimiento->load('motivoRechazo');

            if (in_array($destino, EstadoPostulacion::TERMINALES, true)) {
                // Se ejecuta después del commit definitivo.
                DB::afterCommit(function () use ($postulacion) {
                    try {
                        $this->notificaciones->notificarDecision($postulacion);
                    } catch (Throwable $exception) {
                        // El fallo del correo no revierte la decisión.
                        report($exception);
                    }
                });
            }

            return [
                'id' => $postulacion->id,
                'estado' => [
                    'codigo' => $postulacion->estado->codigo(),
                    'nombre' => $postulacion->estado->nombre,
                ],
                'motivo_rechazo' => $movimiento->motivoRechazo?->motivo,
            ];
        });
    }
}