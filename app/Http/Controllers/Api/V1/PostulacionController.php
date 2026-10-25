<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\BusinessException;
use App\Http\Requests\Postulaciones\PostularVacanteRequest;
use App\Http\Requests\Postulaciones\RetirarPostulacionRequest;
use App\Http\Resources\Api\V1\PostulacionCreadaResource;
use App\Http\Resources\Api\V1\PostulacionRetiradaResource;
use App\Models\Candidato;
use App\Services\Postulaciones\PostulacionService;
use App\Services\Postulaciones\RetiroPostulacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostulacionController extends BaseApiController
{
    public function __construct(
        private PostulacionService $postulaciones,
        private RetiroPostulacionService $retiros
    ) {}

    public function store(
        PostularVacanteRequest $request
    ): JsonResponse {
        $candidato = $this->candidatoAutenticado($request);
        $datos = $request->validated();

        $postulacion = $this->postulaciones->postular(
            $candidato,
            (int) $datos['vacante_id']
        );

        return $this->successResponse(
            (new PostulacionCreadaResource($postulacion))
                ->resolve($request),
            'Postulación registrada.',
            201
        );
    }

    public function retirar(
        RetirarPostulacionRequest $request,
        string $postulacion
    ): JsonResponse {
        $candidato = $this->candidatoAutenticado($request);

        $postulacionId = filter_var(
            $postulacion,
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 1,
                    'max_range' => PHP_INT_MAX,
                ],
            ]
        );

        if ($postulacionId === false) {
            throw new BusinessException(
                'El recurso solicitado no fue encontrado.',
                404,
                'RESOURCE_NOT_FOUND'
            );
        }

        $resultado = $this->retiros->retirar(
            $candidato,
            $postulacionId
        );

        return $this->successResponse(
            (new PostulacionRetiradaResource($resultado))
                ->resolve($request),
            'Te retiraste de la postulación.'
        );
    }

    private function candidatoAutenticado(
        Request $request
    ): Candidato {
        $candidato = $request->user()?->candidato;

        if ($candidato === null) {
            throw new BusinessException(
                'No tienes permisos para realizar esta acción.',
                403,
                'FORBIDDEN'
            );
        }

        return $candidato;
    }
}
